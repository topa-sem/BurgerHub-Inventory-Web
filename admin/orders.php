<?php
// /admin/orders.php
session_start();
require "../config/db_conn.php";

$manage_order_id = null;
$manage_order = null;
$order_items = [];
$supplier_ingredients = [];
$all_suppliers = [];
$pending_orders = [];
$past_orders = [];

// --- HELPER FUNCTION ---
function updateOrderTotal($conn, $order_id) {
    $stmt = $conn->prepare("
        SELECT SUM(quantity_ordered * unit_price) AS total
        FROM ORDER_ITEM
        WHERE order_id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    $stmt = $conn->prepare("UPDATE `ORDER` SET total_amount = ? WHERE order_id = ?");
    $stmt->bind_param("di", $total, $order_id);
    $stmt->execute();
    $stmt->close();
}

// --- HANDLE POST ACTIONS ---

if (isset($_GET['manage_order_id'])) {
    $manage_order_id = intval($_GET['manage_order_id']);

    // --- (MANAGE VIEW) ACTIONS ---

    // ACTION: Add item to order
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item_to_order'])) {
        $ingredient_id = intval($_POST['ingredient_id']);
        $quantity = floatval($_POST['quantity']);
        $supplier_id = intval($_POST['supplier_id']);

        if ($ingredient_id > 0 && $quantity > 0) {
            // 1. Get price from SUPPLIER_INGREDIENT
            $stmt = $conn->prepare("
                SELECT price 
                FROM SUPPLIER_INGREDIENT 
                WHERE supplier_id = ? AND ingredient_id = ?
            ");
            $stmt->bind_param("ii", $supplier_id, $ingredient_id);
            $stmt->execute();
            $price = $stmt->get_result()->fetch_assoc()['price'] ?? 0;
            $stmt->close();

            // 2. Add to ORDER_ITEM (or update existing)
            $stmt = $conn->prepare("
                INSERT INTO ORDER_ITEM (order_id, ingredient_id, supplier_id, quantity_ordered, unit_price)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    quantity_ordered = quantity_ordered + VALUES(quantity_ordered),
                    unit_price = VALUES(unit_price)
            ");
            $stmt->bind_param("iiidd", $manage_order_id, $ingredient_id, $supplier_id, $quantity, $price);
            $stmt->execute();
            $stmt->close();

            // 3. Update total
            updateOrderTotal($conn, $manage_order_id);
        }
        header("Location: orders.php?manage_order_id=" . $manage_order_id);
        exit();
    }

    // ACTION: Remove item from order
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item_from_order'])) {
        $order_item_id = intval($_POST['order_item_id']);
        $stmt = $conn->prepare("DELETE FROM ORDER_ITEM WHERE order_item_id = ?");
        $stmt->bind_param("i", $order_item_id);
        $stmt->execute();
        $stmt->close();

        updateOrderTotal($conn, $manage_order_id);
        header("Location: orders.php?manage_order_id=" . $manage_order_id);
        exit();
    }

    // ACTION: Cancel order
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
        $stmt = $conn->prepare("UPDATE `ORDER` SET status = 'Cancelled' WHERE order_id = ?");
        $stmt->bind_param("i", $manage_order_id);
        $stmt->execute();
        $stmt->close();

        header("Location: orders.php");
        exit();
    }

    // ACTION: Mark order as completed
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_order'])) {
        $conn->begin_transaction();
        try {
            // 1. Get all items in the order
            $stmt_items = $conn->prepare("SELECT ingredient_id, quantity_ordered FROM ORDER_ITEM WHERE order_id = ?");
            $stmt_items->bind_param("i", $manage_order_id);
            $stmt_items->execute();
            $items_to_add = $stmt_items->get_result();

            // 2. Update stock quantities
            $stmt_update_stock = $conn->prepare("
                UPDATE INGREDIENT 
                SET current_stock_quantity = current_stock_quantity + ? 
                WHERE ingredient_id = ?
            ");
            while ($item = $items_to_add->fetch_assoc()) {
                $stmt_update_stock->bind_param("di", $item['quantity_ordered'], $item['ingredient_id']);
                $stmt_update_stock->execute();
            }

            $stmt_items->close();
            $stmt_update_stock->close();

            // 3. Mark order as completed
            $stmt_complete = $conn->prepare("UPDATE `ORDER` SET status = 'Completed' WHERE order_id = ?");
            $stmt_complete->bind_param("i", $manage_order_id);
            $stmt_complete->execute();
            $stmt_complete->close();

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Order completion failed: " . $e->getMessage());
        }

        header("Location: orders.php");
        exit();
    }

} else {
    // --- (LIST VIEW) ACTIONS ---

    // ACTION: Create new order
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_new_order'])) {
        $supplier_id = intval($_POST['supplier_id']);
        $user_id = intval($_SESSION['user_id']);

        if ($supplier_id > 0 && $user_id > 0) {
            $stmt = $conn->prepare("
                INSERT INTO `ORDER` (user_id, supplier_id, order_date, total_amount, status)
                VALUES (?, ?, NOW(), 0, 'Pending')
            ");
            $stmt->bind_param("ii", $user_id, $supplier_id);
            $stmt->execute();
            $new_order_id = $conn->insert_id;
            $stmt->close();

            header("Location: orders.php?manage_order_id=" . $new_order_id);
            exit();
        }
    }
}

// --- GATHER DATA FOR PAGE DISPLAY ---

if ($manage_order_id) {
    // --- (MANAGE VIEW) DATA ---

    // 1. Order details
    $stmt = $conn->prepare("
        SELECT O.*, S.name AS supplier_name 
        FROM `ORDER` O
        JOIN SUPPLIER S ON O.supplier_id = S.supplier_id
        WHERE O.order_id = ?
    ");
    $stmt->bind_param("i", $manage_order_id);
    $stmt->execute();
    $manage_order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$manage_order) {
        header("Location: orders.php");
        exit();
    }

    // 2. Ingredients offered by supplier
    $stmt = $conn->prepare("
        SELECT I.ingredient_id, I.name, I.unit, SI.price
        FROM SUPPLIER_INGREDIENT SI
        JOIN INGREDIENT I ON SI.ingredient_id = I.ingredient_id
        WHERE SI.supplier_id = ?
        ORDER BY I.name
    ");
    $stmt->bind_param("i", $manage_order['supplier_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $supplier_ingredients[] = $row;
    $stmt->close();

    // 3. Items in this order
    $stmt = $conn->prepare("
        SELECT OI.*, I.name AS ingredient_name, I.unit
        FROM ORDER_ITEM OI
        JOIN INGREDIENT I ON OI.ingredient_id = I.ingredient_id
        WHERE OI.order_id = ?
    ");
    $stmt->bind_param("i", $manage_order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $order_items[] = $row;
    $stmt->close();

} else {
    // --- (LIST VIEW) DATA ---

    // 1. All suppliers
    $stmt = $conn->prepare("SELECT supplier_id, name FROM SUPPLIER ORDER BY name");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $all_suppliers[] = $row;
    $stmt->close();

    // 2. Pending orders
    $stmt = $conn->prepare("
        SELECT O.*, S.name AS supplier_name, U.username 
        FROM `ORDER` O
        JOIN SUPPLIER S ON O.supplier_id = S.supplier_id
        JOIN USER U ON O.user_id = U.user_id
        WHERE O.status = 'Pending'
        ORDER BY O.order_date DESC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $pending_orders[] = $row;
    $stmt->close();

    // 3. Past orders
    $stmt = $conn->prepare("
        SELECT O.*, S.name AS supplier_name, U.username 
        FROM `ORDER` O
        JOIN SUPPLIER S ON O.supplier_id = S.supplier_id
        JOIN USER U ON O.user_id = U.user_id
        WHERE O.status IN ('Completed', 'Cancelled')
        ORDER BY O.order_date DESC
        LIMIT 10
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $past_orders[] = $row;
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Orders - Admin</title>
    <link rel="stylesheet" href="../style/dashboard.css">
</head>
<body>
<div class="container">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <?php include 'includes/header.php'; ?>

        <?php if ($manage_order_id): ?>
            <h2>Manage Order #<?php echo htmlspecialchars($manage_order['order_id']); ?></h2>
            <div class="order-header">
                <div><strong>Supplier:</strong> <?php echo htmlspecialchars($manage_order['supplier_name']); ?></div>
                <div><strong>Date:</strong> <?php echo date('d M Y', strtotime($manage_order['order_date'])); ?></div>
                <div class="total-amount"><strong>Total: RM <?php echo number_format($manage_order['total_amount'], 2); ?></strong></div>
                <a href="orders.php" class="back-link">&larr; Back to all orders</a>
            </div>

            <div class="order-grid">
                <div class="panel">
                    <h3>Items in this Order</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="col-qty">Qty</th>
                                <th class="col-price">Unit Price</th>
                                <th class="col-price">Subtotal</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($order_items)): ?>
                                <tr><td colspan="5">No items added yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['ingredient_name']); ?></td>
                                    <td><?php echo rtrim(rtrim($item['quantity_ordered'], '0'), '.'); ?> <?php echo htmlspecialchars($item['unit']); ?></td>
                                    <td>RM <?php echo number_format($item['unit_price'], 2); ?></td>
                                    <td>RM <?php echo number_format($item['unit_price'] * $item['quantity_ordered'], 2); ?></td>
                                    <td>
                                        <form method="POST" class="inline-form">
                                            <input type="hidden" name="order_item_id" value="<?php echo intval($item['order_item_id']); ?>">
                                            <button type="submit" name="remove_item_from_order" class="action-btn btn-delete">&times;</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="panel">
                    <h3>Add Ingredient to Order</h3>
                    <form method="POST" class="form-vertical">
                        <input type="hidden" name="supplier_id" value="<?php echo intval($manage_order['supplier_id']); ?>">
                        
                        <label for="ingredient_select">Ingredient:</label>
                        <select id="ingredient_select" name="ingredient_id" required>
                            <option value="">-- Choose ingredient --</option>
                            <?php foreach ($supplier_ingredients as $ing): ?>
                                <option value="<?php echo intval($ing['ingredient_id']); ?>">
                                    <?php echo htmlspecialchars($ing['name']); ?> 
                                    (RM <?php echo number_format($ing['price'], 2); ?>/<?php echo htmlspecialchars($ing['unit']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label for="quantity_input">Quantity:</label>
                        <input id="quantity_input" type="number" step="0.01" min="0.01" name="quantity" required>

                        <button type="submit" name="add_item_to_order" class="btn">Add Item</button>
                    </form>
                </div>
            </div>

            <div class="panel finalize-panel">
                <h3>Finalize Order</h3>
                <p>Once the order arrives, mark it as completed to add items to your inventory. This cannot be undone.</p>
                <form method="POST" class="inline-form" onsubmit="return confirm('Are you sure this order is complete? This will add stock to your inventory.');">
                    <button type="submit" name="complete_order" class="action-btn btn-complete">Mark as Completed</button>
                </form>
                <form method="POST" class="inline-form" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                    <button type="submit" name="cancel_order" class="action-btn btn-cancel">Cancel Order</button>
                </form>
            </div>

        <?php else: ?>
            <h2>Create New Order</h2>
            <div class="panel">
                <form method="POST" class="form-vertical">
                    <label for="supplier_select">Select a supplier:</label>
                    <select id="supplier_select" name="supplier_id" required>
                        <option value="">-- Choose supplier --</option>
                        <?php foreach ($all_suppliers as $supplier): ?>
                            <option value="<?php echo intval($supplier['supplier_id']); ?>"><?php echo htmlspecialchars($supplier['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="create_new_order" class="btn">Start New Order</button>
                </form>
            </div>

            <h2>Pending Orders</h2>
            <div class="panel">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Supplier</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Placed By</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pending_orders)): ?>
                            <tr><td colspan="7">No pending orders.</td></tr>
                        <?php else: ?>
                            <?php foreach ($pending_orders as $order): ?>
                            <tr>
                                <td>#<?php echo intval($order['order_id']); ?></td>
                                <td><?php echo htmlspecialchars($order['supplier_name']); ?></td>
                                <td><?php echo date('d M Y', strtotime($order['order_date'])); ?></td>
                                <td>RM <?php echo number_format($order['total_amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($order['username']); ?></td>
                                <td><span class="status-badge status-pending"><?php echo htmlspecialchars($order['status']); ?></span></td>
                                <td><a href="orders.php?manage_order_id=<?php echo intval($order['order_id']); ?>" class="action-btn btn-manage">Manage</a></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <h2>Order History</h2>
            <div class="panel">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Supplier</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Placed By</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($past_orders)): ?>
                            <tr><td colspan="6">No completed orders.</td></tr>
                        <?php else: ?>
                            <?php foreach ($past_orders as $order): ?>
                            <tr>
                                <td>#<?php echo intval($order['order_id']); ?></td>
                                <td><?php echo htmlspecialchars($order['supplier_name']); ?></td>
                                <td><?php echo date('d M Y', strtotime($order['order_date'])); ?></td>
                                <td>RM <?php echo number_format($order['total_amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($order['username']); ?></td>
                                <td>
                                    <?php if ($order['status'] == 'Completed'): ?>
                                        <span class="status-badge status-completed">Completed</span>
                                    <?php else: ?>
                                        <span class="status-badge status-cancelled">Cancelled</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
