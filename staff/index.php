<?php
session_start();
require "../config/db_conn.php";

// --- Verify staff login ---
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Staff') {
    // Redirect to correct dashboard based on role
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
$staff_name = $_SESSION['username'] ?? 'Staff'; // ✅ use username, not staff_name

//total sales made by this staff
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total_sales
    FROM `SALE`
    WHERE user_id = ?

");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$res = $stmt->get_result();
$total_sales = $res->fetch_assoc()['total_sales'] ?? 0;
$stmt->close();

// Total revenue (all time) by this staff
$stmt = $conn->prepare("
    SELECT SUM(total_revenue) AS total_revenue
    FROM `SALE`
    WHERE user_id = ?
");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$res = $stmt->get_result();
$total_revenue = $res->fetch_assoc()['total_revenue'] ?? 0;
$stmt->close();

// Today's revenue by this staff
$stmt = $conn->prepare("
    SELECT SUM(total_revenue) AS todays_revenue
    FROM `SALE`
    WHERE user_id = ? AND sale_date = CURDATE()
");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$res = $stmt->get_result();
$todays_revenue = $res->fetch_assoc()['todays_revenue'] ?? 0;
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Dashboard</title>
    <link rel="stylesheet" href="../style/dashboard.css">
</head>
<body>
<div class="container">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <?php include 'includes/header.php'; ?>

        <h2>Hello, <?php echo htmlspecialchars($staff_name); ?> 👋</h2>
        <p>Your performance summary:</p>

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
                <div class="value"><?php echo (int)$total_sales; ?></div>
                <div class="label">Total Sales</div>
            </div>
        </div>

        <div class="panel">
            <h3>Your Top Selling Products</h3>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Total Units Sold</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "
                        SELECT p.name, SUM(si.quantity_sold) AS total_sold
                        FROM `SALE_ITEM` si
                        JOIN `PRODUCT` p ON si.product_id = p.product_id
                        JOIN `SALE` s ON si.sale_id = s.sale_id
                        WHERE s.user_id = ?
                        GROUP BY si.product_id
                        ORDER BY total_sold DESC
                        LIMIT 5
                    ";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $staff_id);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($res->num_rows > 0) {
                        while ($row = $res->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                            echo "<td>" . (int)$row['total_sold'] . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='2'>No sales yet.</td></tr>";
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
                    $sql = "
                        SELECT sale_id, sale_date, total_revenue
                        FROM `SALE`
                        WHERE user_id = ?
                        ORDER BY sale_date DESC, sale_id DESC
                        LIMIT 5
                    ";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $staff_id);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($res->num_rows > 0) {
                        while ($row = $res->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row['sale_id'] . "</td>";
                            echo "<td>" . date('d M Y', strtotime($row['sale_date'])) . "</td>";
                            echo "<td>" . number_format($row['total_revenue'], 2) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3'>No recent sales.</td></tr>";
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
