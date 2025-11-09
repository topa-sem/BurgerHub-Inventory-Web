<?php
session_start();
$staff_id = $_SESSION['user_id'];
require "../config/db_conn.php";

// --- Verify staff login ---
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Staff') {
    if ($_SESSION['user_type'] === 'Admin') {
        header("Location: ../admin/index.php");
    } elseif ($_SESSION['user_type'] === 'Manager') {
        header("Location: ../manager/index.php");
    } else {
        header("Location: ../login_signup/userlogin.php");
    }
    exit();
}

$staff_id = $_SESSION['user_id'];
$staff_name = $_SESSION['username'] ?? 'Staff';
$branch = $_SESSION['branch'] ?? 'Unknown';

// Initialize cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Session messages
$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['error_message']);

// --- HANDLE CART ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add to cart
    if (isset($_POST['add_to_cart'])) {
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);

        if ($product_id > 0 && $quantity > 0) {
            $_SESSION['cart'][$product_id] = ($_SESSION['cart'][$product_id] ?? 0) + $quantity;
        }
        header("Location: sales.php");
        exit();
    }

    // Clear cart
    if (isset($_POST['clear_cart'])) {
        $_SESSION['cart'] = [];
        header("Location: sales.php");
        exit();
    }

    // Process sale
    if (isset($_POST['process_sale'])) {
        if (empty($_SESSION['cart'])) {
            $_SESSION['error_message'] = "Cannot process an empty cart.";
            header("Location: sales.php");
            exit();
        }

        $conn->begin_transaction();
        try {
            $cart_items_details = [];
            $total_revenue = 0;
            $insufficient_stock = false;
            $error_details = "";

            // Check each product
            foreach ($_SESSION['cart'] as $product_id => $quantity_sold) {
                $product_stmt = $conn->prepare("SELECT name, selling_price FROM PRODUCT WHERE product_id = ?");
                $product_stmt->bind_param("i", $product_id);
                $product_stmt->execute();
                $product_data = $product_stmt->get_result()->fetch_assoc();
                $product_stmt->close();

                if (!$product_data) {
                    throw new Exception("Product with ID $product_id not found.");
                }

                $total_revenue += $product_data['selling_price'] * $quantity_sold;
                $cart_items_details[$product_id] = [
                    'quantity_sold' => $quantity_sold,
                    'sale_price' => $product_data['selling_price']
                ];

                // Check ingredients
                $recipe_stmt = $conn->prepare("SELECT ingredient_id, quantity_required FROM RECIPE WHERE product_id = ?");
                $recipe_stmt->bind_param("i", $product_id);
                $recipe_stmt->execute();
                $recipe_result = $recipe_stmt->get_result();

                while ($ingredient = $recipe_result->fetch_assoc()) {
                    $ingredient_id = $ingredient['ingredient_id'];
                    $total_required = $ingredient['quantity_required'] * $quantity_sold;

                    $stock_stmt = $conn->prepare("SELECT name, current_stock_quantity FROM INGREDIENT WHERE ingredient_id = ? FOR UPDATE");
                    $stock_stmt->bind_param("i", $ingredient_id);
                    $stock_stmt->execute();
                    $stock_data = $stock_stmt->get_result()->fetch_assoc();
                    $stock_stmt->close();

                    if ($stock_data['current_stock_quantity'] < $total_required) {
                        $insufficient_stock = true;
                        $error_details = "Not enough stock for ingredient: " . htmlspecialchars($stock_data['name']);
                        break 2;
                    }
                }
                $recipe_stmt->close();
            }

            if ($insufficient_stock) {
                throw new Exception($error_details);
            }

            if (empty($staff_id)) {
                echo "Error: Staff ID not found. Please log in again.";
                exit;
            }

            // Create SALE
            $sale_stmt = $conn->prepare("INSERT INTO SALE (user_id, sale_date, total_revenue) VALUES (?, NOW(), ?)");
            $sale_stmt->bind_param("id", $staff_id, $total_revenue);
            $sale_stmt->execute();
            $sale_id = $conn->insert_id;
            $sale_stmt->close();

            // Insert sale items + deduct ingredients
            foreach ($cart_items_details as $product_id => $item) {
                $sale_item_stmt = $conn->prepare("INSERT INTO SALE_ITEM (sale_id, product_id, quantity_sold, sale_price) VALUES (?, ?, ?, ?)");
                $sale_item_stmt->bind_param("iiid", $sale_id, $product_id, $item['quantity_sold'], $item['sale_price']);
                $sale_item_stmt->execute();
                $sale_item_stmt->close();

                $recipe_stmt = $conn->prepare("SELECT ingredient_id, quantity_required FROM RECIPE WHERE product_id = ?");
                $recipe_stmt->bind_param("i", $product_id);
                $recipe_stmt->execute();
                $recipe_result = $recipe_stmt->get_result();

                while ($ingredient = $recipe_result->fetch_assoc()) {
                    $total_to_deduct = $ingredient['quantity_required'] * $item['quantity_sold'];
                    $deduct_stmt = $conn->prepare("UPDATE INGREDIENT SET current_stock_quantity = current_stock_quantity - ? WHERE ingredient_id = ?");
                    $deduct_stmt->bind_param("di", $total_to_deduct, $ingredient['ingredient_id']);
                    $deduct_stmt->execute();
                    $deduct_stmt->close();
                }
                $recipe_stmt->close();
            }

            // Commit
            $conn->commit();
            $_SESSION['cart'] = [];
            $_SESSION['success_message'] = "Sale #$sale_id processed successfully! Total: RM " . number_format($total_revenue, 2);

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Sale failed: " . $e->getMessage();
        }

        header("Location: sales.php");
        exit();
    }
}

// --- LOAD DATA FOR DISPLAY ---
$all_products = $conn->query("SELECT product_id, name, selling_price FROM PRODUCT ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Cart details
$cart_display_items = [];
$cart_total = 0;
if (!empty($_SESSION['cart'])) {
    $product_ids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
    $result = $conn->query("SELECT product_id, name, selling_price FROM PRODUCT WHERE product_id IN ($product_ids)");
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[$row['product_id']] = $row;
    }
    foreach ($_SESSION['cart'] as $pid => $qty) {
        if (isset($products[$pid])) {
            $p = $products[$pid];
            $line_total = $p['selling_price'] * $qty;
            $cart_display_items[] = [
                'name' => $p['name'],
                'quantity' => $qty,
                'unit_price' => $p['selling_price'],
                'line_total' => $line_total
            ];
            $cart_total += $line_total;
        }
    }
}

// Only show sales made by this staff
$todays_sales = [];
$stmt = $conn->prepare("
    SELECT sale_id, total_revenue, TIME(sale_date) AS sale_time
    FROM SALE
    WHERE DATE(sale_date) = CURDATE() AND user_id = ?
    ORDER BY sale_id DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $todays_sales[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sales - Staff</title>
    <link rel="stylesheet" href="../style/dashboard.css">
</head>
<body>
<div class="container">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <?php include 'includes/header.php'; ?>

        <h2>Record New Sale</h2>

        <?php if ($success_message): ?><div class="toast success"><?= $success_message ?></div><?php endif; ?>
        <?php if ($error_message): ?><div class="toast error"><?= $error_message ?></div><?php endif; ?>

        <div class="pos-layout">
            <div class="panel">
                <h3>Add Product</h3>
                <form method="POST" class="form-vertical">
                    <label>Product:</label>
                    <select name="product_id" required>
                        <option value="">-- Select Product --</option>
                        <?php foreach ($all_products as $product): ?>
                            <option value="<?= $product['product_id'] ?>">
                                <?= htmlspecialchars($product['name']) ?> (RM <?= number_format($product['selling_price'], 2) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label>Quantity:</label>
                    <input type="number" name="quantity" value="1" min="1" required>
                    <button type="submit" name="add_to_cart" class="btn">Add to Cart</button>
                </form>
            </div>

            <div class="panel">
                <h3>Current Cart</h3>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($cart_display_items)): ?>
                            <tr><td colspan="4">Cart is empty.</td></tr>
                        <?php else: ?>
                            <?php foreach ($cart_display_items as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['name']) ?></td>
                                <td><?= $item['quantity'] ?></td>
                                <td>RM <?= number_format($item['unit_price'], 2) ?></td>
                                <td>RM <?= number_format($item['line_total'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr><th colspan="3">Total:</th><th>RM <?= number_format($cart_total, 2) ?></th></tr>
                    </tfoot>
                </table>

                <?php if (!empty($cart_display_items)): ?>
                <div class="cart-actions">
                    <form method="POST" onsubmit="return confirm('Process this sale?');">
                        <button type="submit" name="process_sale" class="btn btn-primary">Process Sale</button>
                    </form>
                    <form method="POST" onsubmit="return confirm('Clear cart?');">
                        <button type="submit" name="clear_cart" class="btn btn-delete">Clear Cart</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="panel">
            <h2>Today's Sales (<?= date('d M Y') ?>)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Sale ID</th>
                        <th>Time</th>
                        <th>Total Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($todays_sales)): ?>
                        <tr><td colspan="3">No sales recorded yet today.</td></tr>
                    <?php else: ?>
                        <?php foreach ($todays_sales as $sale): ?>
                        <tr>
                            <td>#<?= $sale['sale_id'] ?></td>
                            <td><?= date('h:i A', strtotime($sale['sale_time'])) ?></td>
                            <td>RM <?= number_format($sale['total_revenue'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
