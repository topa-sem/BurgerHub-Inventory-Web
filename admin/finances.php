<?php
session_start();
require "../config/db_conn.php";

// 1. Total Revenue (from all sales)
$stmt = $conn->prepare("SELECT IFNULL(SUM(total_revenue),0) AS total_revenue FROM `SALE`");
$stmt->execute();
$res = $stmt->get_result();
$total_revenue = $res->fetch_assoc()['total_revenue'];
$stmt->close();

// 2. Total Expenses (from COMPLETED orders)
//    *** THIS IS THE FIX: Using 'total_amount' and checking for 'Completed' status ***
$stmt = $conn->prepare("SELECT IFNULL(SUM(total_amount),0) AS total_expenses FROM `ORDER` WHERE status = 'Completed'");
$stmt->execute();
$res = $stmt->get_result();
$total_expenses = $res->fetch_assoc()['total_expenses'];
$stmt->close();

// 3. Net Cashflow (a more accurate term than 'Profit' for this calculation)
$net_cashflow = $total_revenue - $total_expenses;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Finances - Admin</title>
    <link rel="stylesheet" href="../style/dashboard.css">
</head>
<body>
<div class="container">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <?php include 'includes/header.php'; ?>

        <h2>Finances Dashboard</h2>

        <!-- Top Summary Cards -->
        <div class="cards">
            <div class="card">
                <div class="value">RM <?php echo number_format($total_revenue, 2); ?></div>
                <div class="label">Total Revenue (All Sales)</div>
            </div>
            <div class="card">
                <div class="value">RM <?php echo number_format($total_expenses, 2); ?></div>
                <div class="label">Total Expenses (Completed Orders)</div>
            </div>
            <div class="card">
                <div class="value">RM <?php echo number_format($net_cashflow, 2); ?></div>
                <div class="label">Net Cashflow (Revenue - Expenses)</div>
            </div>
        </div>

        <!-- Panels are now stacked vertically -->
            
        <!-- Recent Sales Panel -->
        <div class="panel">
            <h3>Recent Sales</h3>
            <table>
                <thead>
                    <tr>
                        <th>Sale ID</th>
                        <th>Date</th>
                        <th>User</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->prepare("
                        SELECT s.sale_id, s.sale_date, s.total_revenue, u.username
                        FROM `SALE` s
                        JOIN `USER` u ON s.user_id = u.user_id
                        ORDER BY s.sale_id DESC
                        LIMIT 5
                    ");
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($res->num_rows === 0) {
                        echo "<tr><td colspan='4'>No sales found.</td></tr>";
                    } else {
                        while ($r = $res->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>#" . $r['sale_id'] . "</td>";
                            echo "<td>" . date('d M Y', strtotime($r['sale_date'])) . "</td>";
                            echo "<td>" . htmlspecialchars($r['username']) . "</td>";
                            echo "<td>RM " . number_format($r['total_revenue'], 2) . "</td>";
                            echo "</tr>";
                        }
                    }
                    $stmt->close();
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Recent Orders Panel -->
        <div class="panel">
            <h3>Recent Orders (Expenses)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Supplier</th>
                        <th>Status</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->prepare("
                        SELECT o.order_id, o.total_amount, o.status, s.name AS supplier_name
                        FROM `ORDER` o
                        LEFT JOIN `SUPPLIER` s ON o.supplier_id = s.supplier_id
                        ORDER BY o.order_id DESC
                        LIMIT 5
                    ");
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($res->num_rows === 0) {
                        echo "<tr><td colspan='4'>No orders found.</td></tr>";
                    } else {
                        while ($r = $res->fetch_assoc()) {
                            $status_class = strtolower(htmlspecialchars($r['status']));
                            echo "<tr>";
                            echo "<td>#" . $r['order_id'] . "</td>";
                            echo "<td>" . htmlspecialchars($r['supplier_name'] ?? 'N/A') . "</td>";
                            echo "<td><span class=\"status-badge status-{$status_class}\">" . htmlspecialchars($r['status']) . "</span></td>";
                            echo "<td>RM " . number_format($r['total_amount'], 2) . "</td>";
                            echo "</tr>";
                        }
                    }
                    $stmt->close();
                    ?>
                </tbody>
            </table>
        </div>


        <!-- Top User Balances -->
        <div class="panel">
            <h3>Top User Account Balances</h3>
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Balance (RM)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->prepare("SELECT username, account_balance FROM `USER` ORDER BY account_balance DESC LIMIT 8");
                    $stmt->execute();
                    $res = $stmt->get_result();
                    while ($r = $res->fetch_assoc()) {
                        echo "<tr><td>" . htmlspecialchars($r['username']) . "</td><td>RM " . number_format($r['account_balance'], 2) . "</td></tr>";
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

