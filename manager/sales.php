<?php
session_start();
require "../config/db_conn.php";

// Mock user session for testing (replace with your actual login system)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Assuming admin user is ID 1
}
if (!isset($_SESSION['branch'])) {
    $_SESSION['branch'] = 'HQ';
}
$user_id = $_SESSION['user_id'];

// Initialize cart in session
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Get session messages for success/error
$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['error_message']);

// --- HANDLE POST ACTIONS ---

// ACTION: Add an item to the cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);

    if ($product_id > 0 && $quantity > 0) {
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $quantity; // Add to existing quantity
        } else {
            $_SESSION['cart'][$product_id] = $quantity; // Add new item
        }
    }
    header("Location: sales.php");
    exit();
}

// ACTION: Clear the cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_cart'])) {
    $_SESSION['cart'] = [];
    header("Location: sales.php");
    exit();
}

// ACTION: Process the entire sale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_sale'])) {
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

        // --- Loop 1: Check stock and calculate totals ---
        // This loop reads data and locks ingredient rows for update
        foreach ($_SESSION['cart'] as $product_id => $quantity_sold) {
            
            // 1. Get product price and name
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
                'sale_price' => $product_data['selling_price'],
                'name' => $product_data['name']
            ];

            // 2. Get recipe for this product
            $recipe_stmt = $conn->prepare("SELECT ingredient_id, quantity_required FROM RECIPE WHERE product_id = ?");
            $recipe_stmt->bind_param("i", $product_id);
            $recipe_stmt->execute();
            $recipe_result = $recipe_stmt->get_result();
            
            // 3. Check stock for each ingredient
            while ($ingredient = $recipe_result->fetch_assoc()) {
                $ingredient_id = $ingredient['ingredient_id'];
                $total_required = $ingredient['quantity_required'] * $quantity_sold;

                // Lock the ingredient row to prevent race conditions
                $stock_stmt = $conn->prepare("SELECT name, current_stock_quantity FROM INGREDIENT WHERE ingredient_id = ? FOR UPDATE");
                $stock_stmt->bind_param("i", $ingredient_id);
                $stock_stmt->execute();
                $stock_data = $stock_stmt->get_result()->fetch_assoc();
                $stock_stmt->close();

                if ($stock_data['current_stock_quantity'] < $total_required) {
                    $insufficient_stock = true;
                    $error_details = "Not enough stock for ingredient: " . htmlspecialchars($stock_data['name']) . 
                                     ". (Required: $total_required, Available: " . $stock_data['current_stock_quantity'] . ")";
                    break 2; // Break out of both the inner and outer loops
                }
            }
            $recipe_stmt->close();
        }

        // If any stock check failed, roll back
        if ($insufficient_stock) {
            throw new Exception($error_details);
        }

        // --- If all stock is sufficient, proceed with all database writes ---

        // 1. Create the main SALE record
        $sale_stmt = $conn->prepare("INSERT INTO SALE (user_id, sale_date, total_revenue) VALUES (?, CURDATE(), ?)");
        $sale_stmt->bind_param("id", $user_id, $total_revenue);
        $sale_stmt->execute();
        $sale_id = $conn->insert_id;
        $sale_stmt->close();

        // 2. Create SALE_ITEM records and deduct stock
        foreach ($cart_items_details as $product_id => $item) {
            
            // 2a. Insert into SALE_ITEM
            $sale_item_stmt = $conn->prepare("INSERT INTO SALE_ITEM (sale_id, product_id, quantity_sold, sale_price) VALUES (?, ?, ?, ?)");
            $sale_item_stmt->bind_param("iiid", $sale_id, $product_id, $item['quantity_sold'], $item['sale_price']);
            $sale_item_stmt->execute();
            $sale_item_stmt->close();

            // 2b. Deduct ingredients based on recipe
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

        // 3. Update user's account balance
        $user_balance_stmt = $conn->prepare("UPDATE USER SET account_balance = account_balance + ? WHERE user_id = ?");
        $user_balance_stmt->bind_param("di", $total_revenue, $user_id);
        $user_balance_stmt->execute();
        $user_balance_stmt->close();

        // If we get here, everything was successful
        $conn->commit();
        $_SESSION['cart'] = []; // Clear the cart
        $_SESSION['success_message'] = "Sale #$sale_id processed successfully! RM " . number_format($total_revenue, 2) . " added to your account.";

    } catch (Exception $e) {
        $conn->rollback(); // Roll back all changes on error
        $_SESSION['error_message'] = "Sale failed: " . $e->getMessage();
    }

    header("Location: sales.php");
    exit();
}

// --- GET DATA FOR PAGE DISPLAY ---

// Get all products for the dropdown
$all_products = [];
$product_result = $conn->query("SELECT product_id, name, selling_price FROM PRODUCT ORDER BY name");
while ($row = $product_result->fetch_assoc()) {
    $all_products[] = $row;
}

// Get cart details for display
$cart_display_items = [];
$cart_total = 0;
if (!empty($_SESSION['cart'])) {
    $product_ids_in_cart = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
    $cart_product_result = $conn->query("SELECT product_id, name, selling_price FROM PRODUCT WHERE product_id IN ($product_ids_in_cart)");
    
    $cart_products_data = [];
    while ($row = $cart_product_result->fetch_assoc()) {
        $cart_products_data[$row['product_id']] = $row;
    }
    
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        if (isset($cart_products_data[$product_id])) {
            $product = $cart_products_data[$product_id];
            $line_total = $product['selling_price'] * $quantity;
            $cart_display_items[] = [
                'name' => $product['name'],
                'quantity' => $quantity,
                'unit_price' => $product['selling_price'],
                'line_total' => $line_total
            ];
            $cart_total += $line_total;
        } else {
            // Product was in cart but not in DB (e.g., deleted), remove it
            unset($_SESSION['cart'][$product_id]);
        }
    }
}

// Get today's sales
$todays_sales = [];
$sales_stmt = $conn->prepare("
    SELECT s.sale_id, s.total_revenue, TIME(s.sale_date) AS sale_time
    FROM SALE s 
    WHERE DATE(s.sale_date) = CURDATE() 
    ORDER BY s.sale_id DESC
");
$sales_stmt->execute();
$sales_result = $sales_stmt->get_result();
while ($row = $sales_result->fetch_assoc()) {
    $todays_sales[] = $row;
}
$sales_stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sales</title>
    <link rel="stylesheet" href="../style/dashboard.css"">
</head>
<body>
<div class="container">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <?php include 'includes/header.php'; ?>

        <h2>New Sale (POS)</h2>

        <?php if ($success_message): ?>
            <div class="toast success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="toast error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="pos-layout">
            
            <div class="panel">
                <h3>Add Product</h3>
                <form method="POST" class="form-vertical">
                    <label for="product_select">Product:</label>
                    <select id="product_select" name="product_id" required>
                        <option value="">-- Choose a product --</option>
                        <?php foreach ($all_products as $product): ?>
                            <option value="<?php echo $product['product_id']; ?>">
                                <?php echo htmlspecialchars($product['name']) . ' (RM ' . number_format($product['selling_price'], 2) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <label for="quantity_input">Quantity:</label>
                    <input id="quantity_input" type="number" name="quantity" value="1" min="1" required>
                    
                    <button type="submit" name="add_to_cart" class="btn">Add to Cart</button>
                </form>
            </div>

            <div class="panel">
                <h3>Current Cart</h3>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
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
                                KA<td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>RM <?php echo number_format($item['unit_price'], 2); ?></td>
                                <td>RM <?php echo number_format($item['line_total'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3">Total:</th>
                            <th>RM <?php echo number_format($cart_total, 2); ?></th>
                        </tr>
                    </tfoot>
                </table>

                <?php if (!empty($cart_display_items)): ?>
                <div class="cart-actions">
                    <form method="POST" onsubmit="return confirm('Process this sale?');">
                        <button type="submit" name="process_sale" class="btn btn-primary">Process Sale</button>
                    </form>
                    <form method="POST" onsubmit="return confirm('Clear the cart?');">
                        <button type="submit" name="clear_cart" class="btn btn-delete">Clear Cart</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>

        </div>

        <div class="panel">
            <h2>Today's Sales (<?php echo date('d M Y'); ?>)</h2>
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
                            <td>#<?php echo $sale['sale_id']; ?></td>
                            <td><?php echo date('h:i A', strtotime($sale['sale_time'])); ?></td>
                            <td>RM <?php echo number_format($sale['total_revenue'], 2); ?></td>
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