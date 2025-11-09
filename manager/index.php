<?php
session_start();
require "../config/db_conn.php";

// Check if user is logged in and is Manager
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Manager') {
    header("Location: login.php");
    exit();
}

// Get selected date from session
$selected_date = $_SESSION['selected_date'] ?? date('Y-m-d');
$user_id = $_SESSION['user_id'];

// --- Fetch overview stats with date filtering ---

// total products (unchanged)
$stmt = $conn->prepare("SELECT COUNT(*) AS total_products FROM `PRODUCT`");
$stmt->execute(); $res = $stmt->get_result(); $total_products = $res->fetch_assoc()['total_products'] ?? 0; $stmt->close();

// FIXED: low stock ingredients (user-specific) - count all ingredients where stock <= threshold
$stmt = $conn->prepare("
    SELECT COUNT(*) AS low_stock 
    FROM (
        SELECT 
            i.ingredient_id,
            COALESCE(uis.current_stock_quantity, 0) as current_stock,
            COALESCE(uis.low_stock_threshold, 10) as threshold
        FROM INGREDIENT i
        LEFT JOIN USER_INGREDIENT_STOCK uis ON i.ingredient_id = uis.ingredient_id AND uis.user_id = ?
    ) AS stock_data
    WHERE current_stock <= threshold
");
$stmt->bind_param("i", $user_id);
$stmt->execute(); $res = $stmt->get_result(); $low_stock = $res->fetch_assoc()['low_stock'] ?? 0; $stmt->close();

// recent orders count (filtered by selected date and user)
$stmt = $conn->prepare("SELECT COUNT(*) AS recent_orders FROM `ORDER` WHERE DATE(order_date) = ? AND user_id = ?");
$stmt->bind_param("si", $selected_date, $user_id);
$stmt->execute(); $res = $stmt->get_result(); $recent_orders = $res->fetch_assoc()['recent_orders'] ?? 0; $stmt->close();

// recent sales count (filtered by selected date and user)
$stmt = $conn->prepare("SELECT COUNT(*) AS recent_sales FROM `SALE` WHERE DATE(sale_date) = ? AND user_id = ?");
$stmt->bind_param("si", $selected_date, $user_id);
$stmt->execute(); $res = $stmt->get_result(); $recent_sales = $res->fetch_assoc()['recent_sales'] ?? 0; $stmt->close();

// --- Total Revenue (All Time) - user specific ---
$stmt = $conn->prepare("SELECT SUM(total_revenue) AS total_revenue FROM `SALE` WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute(); $res = $stmt->get_result(); $total_revenue = $res->fetch_assoc()['total_revenue'] ?? 0; $stmt->close();

// --- Selected Date's Revenue ---
$stmt = $conn->prepare("SELECT SUM(total_revenue) AS selected_date_revenue FROM `SALE` WHERE DATE(sale_date) = ? AND user_id = ?");
$stmt->bind_param("si", $selected_date, $user_id);
$stmt->execute(); $res = $stmt->get_result(); $selected_date_revenue = $res->fetch_assoc()['selected_date_revenue'] ?? 0; $stmt->close();

// Current account balance
$current_balance = $_SESSION['account_balance'] ?? 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manager Dashboard</title>
</head>
<body>
<div class="container">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main">
        <?php include 'includes/header.php'; ?>

        <h2><?php 
    echo htmlspecialchars($_SESSION['branch'] ?? 'Unknown Branch'); 
    ?> - Manager Dashboard</h2>

        <?php if ($low_stock > 0): ?>
            <div class="alert-low-stock">
                <strong>Alert:</strong> You have <?php echo $low_stock; ?> item(s) running low on stock. Check the "Stocks" page.
            </div>
        <?php else: ?>
            <div class="alert-stock-ok">
                <strong>All Good:</strong> Your inventory levels are sufficient.
            </div>
        <?php endif; ?>

        <div class="cards">
            <div class="card">
                <div class="value">RM <?php echo number_format($selected_date_revenue, 2); ?></div>
                <div class="label">Today's Revenue</div>
            </div>
            <div class="card">
                <div class="value">RM <?php echo number_format($today_expenses, 2); ?></div>
                <div class="label">Today's Expenses</div>
            </div>
            <div class="card">
                <div class="value"><?php echo (int)$total_products; ?></div>
                <div class="label">Available Products</div>
            </div>
            <div class="card">
                <div class="value"><?php echo (int)$recent_orders; ?></div>
                <div class="label">Today's Orders</div>
            </div>
            <div class="card">
                <div class="value"><?php echo (int)$recent_sales; ?></div>
                <div class="label">Today's Sales</div>
            </div>
            <div class="card">
                <div class="value"><?php echo (int)$low_stock; ?></div>
                <div class="label">Low Stock Items</div>
            </div>
        </div>

        <div class="panel">
            <h3>My Top Selling Products - <?php echo date('d M Y', strtotime($selected_date)); ?></h3>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Total Units Sold</th>
                        <th>Total Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT p.name, SUM(si.quantity_sold) AS total_sold, SUM(si.quantity_sold * si.sale_price) AS total_revenue
                            FROM `SALE_ITEM` si
                            JOIN `SALE` s ON si.sale_id = s.sale_id
                            JOIN `PRODUCT` p ON si.product_id = p.product_id
                            WHERE DATE(s.sale_date) = ? AND s.user_id = ?
                            GROUP BY si.product_id
                            ORDER BY total_sold DESC
                            LIMIT 5";
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("si", $selected_date, $user_id);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    
                    if ($res->num_rows === 0) {
                        echo "<tr><td colspan='3'>No sales for this date.</td></tr>";
                    } else {
                        while($row = $res->fetch_assoc()){
                            echo "<tr>";
                            echo "<td>".htmlspecialchars($row['name'])."</td>";
                            echo "<td>".(int)$row['total_sold']."</td>";
                            echo "<td>RM ".number_format($row['total_revenue'], 2)."</td>";
                            echo "</tr>";
                        }
                    }
                    $stmt->close();
                    ?>
                </tbody>
            </table>
        </div>

        <div class="panel">
            <h3>My Recent Sales - <?php echo date('d M Y', strtotime($selected_date)); ?></h3>
            <table>
                <thead>
                    <tr>
                        <th>Sale ID</th>
                        <th>Time</th>
                        <th>Revenue (RM)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT s.sale_id, s.sale_date, s.total_revenue
                            FROM `SALE` s
                            WHERE DATE(s.sale_date) = ? AND s.user_id = ?
                            ORDER BY s.sale_date DESC, s.sale_id DESC
                            LIMIT 5";

                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("si", $selected_date, $user_id);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    
                    if ($res->num_rows === 0) {
                        echo "<tr><td colspan='3'>No sales for this date.</td></tr>";
                    } else {
                        while($row = $res->fetch_assoc()){
                            echo "<tr>";
                            echo "<td>#".$row['sale_id']."</td>";
                            echo "<td>".date('h:i A', strtotime($row['sale_date']))."</td>";
                            echo "<td>".number_format($row['total_revenue'], 2)."</td>";
                            echo "</tr>";
                        }
                    }
                    $stmt->close();
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Latest Products (unchanged) -->
        <div class="panel">
            <h3>Available Products</h3>
            <table>
                <thead><tr><th>#</th><th>Product</th><th>Price (RM)</th></tr></thead>
                <tbody>
                    <?php
                    $stmt = $conn->prepare("SELECT product_id, name, selling_price FROM `PRODUCT` ORDER BY product_id DESC LIMIT 6");
                    $stmt->execute(); $res = $stmt->get_result();
                    $i=1;
                    while($row = $res->fetch_assoc()){
                        echo "<tr>";
                        echo "<td>".$i++."</td>";
                        echo "<td>".htmlspecialchars($row['name'])."</td>";
                        echo "<td>".number_format($row['selling_price'],2)."</td>";
                        echo "</tr>";
                    }
                    $stmt->close();
                    ?>
                </tbody>
            </table>
        </div>

    </div>
</div>
</body>
</html>