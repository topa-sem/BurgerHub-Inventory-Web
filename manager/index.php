<?php
session_start();
require "../config/db_conn.php";

// --- Check if Manager is logged in and get branch ---
// We must assume the branch is stored in the session after login
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Manager' || !isset($_SESSION['branch'])) {
    // Redirect to login or an error page if not a logged-in Manager
    // You should have a login.php page
    header('Location: login.php'); 
    exit;
}

// Get the manager's branch from the session to filter queries
$manager_branch = $_SESSION['branch'];

// --- Fetch overview stats (FILTERED BY BRANCH) ---

// total products (Global - Managers can see all products)
$stmt = $conn->prepare("SELECT COUNT(*) AS total_products FROM `PRODUCT`");
$stmt->execute(); $res = $stmt->get_result(); $total_products = $res->fetch_assoc()['total_products'] ?? 0; $stmt->close();

// low stock ingredients (Global - Stock is global in this schema)
$stmt = $conn->prepare("SELECT COUNT(*) AS low_stock FROM `INGREDIENT` WHERE current_stock_quantity <= low_stock_threshold");
$stmt->execute(); $res = $stmt->get_result(); $low_stock = $res->fetch_assoc()['low_stock'] ?? 0; $stmt->close();

// recent orders count (last 7 days) (Branch-specific)
// This query joins with USER to filter by branch
$stmt = $conn->prepare("SELECT COUNT(*) AS recent_orders 
                        FROM `ORDER` o
                        JOIN `USER` u ON o.user_id = u.user_id
                        WHERE o.order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                        AND u.branch = ?");
$stmt->bind_param("s", $manager_branch);
$stmt->execute(); $res = $stmt->get_result(); $recent_orders = $res->fetch_assoc()['recent_orders'] ?? 0; $stmt->close();

// --- Total Revenue (All Time) (Branch-specific) ---
$stmt = $conn->prepare("SELECT SUM(s.total_revenue) AS total_revenue 
                        FROM `SALE` s
                        JOIN `USER` u ON s.user_id = u.user_id
                        WHERE u.branch = ?");
$stmt->bind_param("s", $manager_branch);
$stmt->execute(); $res = $stmt->get_result(); $total_revenue = $res->fetch_assoc()['total_revenue'] ?? 0; $stmt->close();

// --- Today's Revenue (Branch-specific) ---
$stmt = $conn->prepare("SELECT SUM(s.total_revenue) AS todays_revenue 
                        FROM `SALE` s
                        JOIN `USER` u ON s.user_id = u.user_id
                        WHERE s.sale_date = CURDATE()
                        AND u.branch = ?");
$stmt->bind_param("s", $manager_branch);
$stmt->execute(); $res = $stmt->get_result(); $todays_revenue = $res->fetch_assoc()['todays_revenue'] ?? 0; $stmt->close();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Manager Dashboard</title>
    <link rel="stylesheet" href="../style/dashboard.css">
</head>
<body>
<div class="container">
    <?php include 'includes/sidebar.php'; // Make sure this sidebar is also customized for managers ?>

    <div class="main">
        <?php include 'includes/header.php'; ?>

        <h2>Recent activity (<?php echo htmlspecialchars($manager_branch); ?> Branch)</h2>

        <?php if ($low_stock > 0): ?>
            <div class="alert-low-stock">
                <strong>Alert:</strong> There are <?php echo $low_stock; ?> item(s) running low on global stock.
            </div>
        <?php else: ?>
            <div class="alert-stock-ok">
                <strong>All Good:</strong> Global inventory levels are sufficient.
            </div>
        <?php endif; ?>

        <div class="cards">
            <div class="card">
                <div class="value">RM <?php echo number_format($total_revenue, 2); ?></div>
                <div class="label">Branch Total Revenue</div>
            </div>
            <div class="card">
                <div class="value">RM <?php echo number_format($todays_revenue, 2); ?></div>
                <div class="label">Branch Today's Revenue</div>
            </div>

            <div class="card">
                <div class="value"><?php echo (int)$total_products; ?></div>
                <div class="label">Products</div>
            </div>
            <div class="card">
                <div class="value"><?php echo (int)$recent_orders; ?></div>
                <div class="label">Branch Orders (7d)</div>
            </div>
            <div class="card">
                <div class="value"><?php echo (int)$low_stock; ?></div>
                <div class="label">Low stock items</div>
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
                    // This query joins SALE_ITEM, PRODUCT, SALE, and USER
                    // to filter sales by the manager's branch.
                    $sql = "SELECT p.name, SUM(si.quantity_sold) AS total_sold
                            FROM `SALE_ITEM` si
                            JOIN `PRODUCT` p ON si.product_id = p.product_id
                            JOIN `SALE` s ON si.sale_id = s.sale_id
                            JOIN `USER` u ON s.user_id = u.user_id
                            WHERE u.branch = ?
                            GROUP BY si.product_id
                            ORDER BY total_sold DESC
                            LIMIT 5";
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("s", $manager_branch); // Bind the branch
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
                        <th>Date</th>
                        <th>Revenue (RM)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // This query joins SALE with USER to filter by the manager's branch
                    // The manager's username is no longer selected
                    $sql = "SELECT s.sale_id, s.sale_date, s.total_revenue
                            FROM `SALE` s
                            JOIN `USER` u ON s.user_id = u.user_id
                            WHERE u.branch = ?
                            ORDER BY s.sale_date DESC, s.sale_id DESC
                            LIMIT 5";

                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("s", $manager_branch); // Bind the branch
                    $stmt->execute();
                    $res = $stmt->get_result();
                    
                    while($row = $res->fetch_assoc()){
                        echo "<tr>";
                        echo "<td>".$row['sale_id']."</td>";
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
                    // This is global, which is fine for a manager to see
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