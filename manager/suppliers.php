<?php
session_start();
require "../config/db_conn.php";

// Check if user is logged in and is Manager
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Manager') {
    header("Location: login.php");
    exit();
}

$all_suppliers = [];

// --- GATHER DATA FOR PAGE DISPLAY ---

// Get all suppliers (managers can view all suppliers but not manage them)
$stmt = $conn->prepare("SELECT * FROM SUPPLIER ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) $all_suppliers[] = $row;
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Suppliers - Manager</title>
</head>
<body>
<div class="container">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <?php include 'includes/header.php'; ?>

        <h2>Available Suppliers</h2>
        <div class="panel">
            <p>These are the suppliers available for placing orders. Contact admin to add new suppliers.</p>
        </div>

        <div class="panel">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_suppliers as $supplier): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($supplier['name']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['phone']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['contact_email']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>
</body>
</html>