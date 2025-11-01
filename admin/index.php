<?php
session_start();
require "../config/db_conn.php";

// --- Fetch overview stats ---

// total users
$stmt = $conn->prepare("SELECT COUNT(*) AS total_users FROM `USER`");
$stmt->execute(); $res = $stmt->get_result(); $total_users = $res->fetch_assoc()['total_users'] ?? 0; $stmt->close();

// total products
$stmt = $conn->prepare("SELECT COUNT(*) AS total_products FROM `PRODUCT`");
$stmt->execute(); $res = $stmt->get_result(); $total_products = $res->fetch_assoc()['total_products'] ?? 0; $stmt->close();

// low stock ingredients (threshold)
$stmt = $conn->prepare("SELECT COUNT(*) AS low_stock FROM `INGREDIENT` WHERE current_stock_quantity <= low_stock_threshold");
$stmt->execute(); $res = $stmt->get_result(); $low_stock = $res->fetch_assoc()['low_stock'] ?? 0; $stmt->close();

// recent orders count (last 7 days)
$stmt = $conn->prepare("SELECT COUNT(*) AS recent_orders FROM `ORDER` WHERE order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stmt->execute(); $res = $stmt->get_result(); $recent_orders = $res->fetch_assoc()['recent_orders'] ?? 0; $stmt->close();

// --- NEW: Total Revenue (All Time) ---
$stmt = $conn->prepare("SELECT SUM(total_revenue) AS total_revenue FROM `SALE`");
$stmt->execute(); $res = $stmt->get_result(); $total_revenue = $res->fetch_assoc()['total_revenue'] ?? 0; $stmt->close();

// --- NEW: Today's Revenue ---
$stmt = $conn->prepare("SELECT SUM(total_revenue) AS todays_revenue FROM `SALE` WHERE sale_date = CURDATE()");
$stmt->execute(); $res = $stmt->get_result(); $todays_revenue = $res->fetch_assoc()['todays_revenue'] ?? 0; $stmt->close();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../style/dashboard.css">
</head>
<body>
<div class="container">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main">
        <?php include 'includes/header.php'; ?>

        <h2>Recent activity</h2>

        <?php if ($low_stock > 0): ?>
            <div class="alert-low-stock">
                <strong>Alert:</strong> You have <?php echo $low_stock; ?> item(s) running low on stock. Check the "Low Stock Ingredients" table below.
            </div>
        <?php else: ?>
            <div class="alert-stock-ok">
                <strong>All Good:</strong> Your inventory levels are sufficient.
            </div>
        <?php endif; ?>

        <div class="cards">
            <div class="card">
                <div class="value">RM <?php echo number_format($total_revenue, 2); ?></div>
                <div class="label">Total Revenue</div>
            </div>
            <div class="card">
                <div class="value">RM <?php echo number_format($todays_revenue, 2); ?></div>
                <div class="label">Today's Revenue</div>
            </div>

            <div class="card">
                <div class="value"><?php echo (int)$total_products; ?></div>
                <div class="label">Products</div>
            </div>
            <div class="card">
                <div class="value"><?php echo (int)$recent_orders; ?></div>
                <div class="label">Orders (7d)</div>
            </div>
            <div class="card">
                <div class="value"><?php echo (int)$low_stock; ?></div>
                <div class="label">Low stock items</div>
            </div>
            <div class="card">
                <div class="value"><?php echo (int)$total_users; ?></div>
                <div class="label">Users</div>
            </div>
        </div>

        <div class="panel">
            <h3>Top Selling Products</h3>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Total Units Sold</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // This query joins SALE_ITEM with PRODUCT, groups by product,
                    // sums the quantities sold, and orders it.
                    $sql = "SELECT p.name, SUM(si.quantity_sold) AS total_sold
                            FROM `SALE_ITEM` si
                            JOIN `PRODUCT` p ON si.product_id = p.product_id
                            GROUP BY si.product_id
                            ORDER BY total_sold DESC
                            LIMIT 5";
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    
                    while($row = $res->fetch_assoc()){
                        echo "<tr>";
                        echo "<td>".htmlspecialchars($row['name'])."</td>";
                        echo "<td>".(int)$row['total_sold']."</td>";
                        echo "</tr>";
                    }
                    $stmt->close();
                    ?>
                </tbody>
            </table>
        </div>

        <div class="panel">
            <h3>Recent Sales</h3>
            <table>
                <thead>
                    <tr>
                        <th>Sale ID</th>
                        <th>Manager</th>
                        <th>Date</th>
                        <th>Revenue (RM)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // This query joins SALE with USER to show who made the sale
                    $sql = "SELECT s.sale_id, s.sale_date, s.total_revenue, u.username
                            FROM `SALE` s
                            JOIN `USER` u ON s.user_id = u.user_id
                            ORDER BY s.sale_date DESC, s.sale_id DESC
                            LIMIT 5";

                    $stmt = $conn->prepare($sql);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    
                    while($row = $res->fetch_assoc()){
                        echo "<tr>";
                        echo "<td>".$row['sale_id']."</td>";
                        echo "<td>".htmlspecialchars($row['username'])."</td>";
                        echo "<td>".date('d M Y', strtotime($row['sale_date']))."</td>";
                        echo "<td>".number_format($row['total_revenue'], 2)."</td>";
                        echo "</tr>";
                    }
                    $stmt->close();
                    ?>
                </tbody>
            </table>
        </div>

        <div class="panel">
            <h3>Latest Products</h3>
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