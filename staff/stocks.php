<?php
// /staff/stocks.php
session_start();
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

// GATHER DATA: Get all ingredients for display
$ingredients = [];
$stmt = $conn->prepare("
    SELECT ingredient_id, name, unit, current_stock_quantity, low_stock_threshold 
    FROM INGREDIENT 
    ORDER BY name ASC
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $ingredients[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Stock Overview - Staff</title>
    <link rel="stylesheet" href="../style/dashboard.css">
    <style>
        .col-num {
            text-align: right;
        }
        .col-center {
            text-align: center;
        }
        .low-stock {
            background-color: #ffe6e6;
            color: #c0392b;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="container">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <?php include 'includes/header.php'; ?>

        <h2>Stock Overview</h2>

        <div class="panel">
            <table>
                <thead>
                    <tr>
                        <th>Ingredient</th>
                        <th class="col-center">Unit</th>
                        <th class="col-center">Current Stock</th>
                        <th class="col-center">Low Stock Threshold</th>
                        <th class="col-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ingredients)): ?>
                        <tr><td colspan="5">No ingredients found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($ingredients as $ing): 
                            $isLow = $ing['current_stock_quantity'] < $ing['low_stock_threshold'];
                        ?>
                            <tr class="<?php echo $isLow ? 'low-stock' : ''; ?>">
                                <td><?php echo htmlspecialchars($ing['name']); ?></td>
                                <td class="col-center"><?php echo htmlspecialchars($ing['unit']); ?></td>
                                <td class="col-center"><?php echo number_format($ing['current_stock_quantity'], 2); ?></td>
                                <td class="col-center"><?php echo (int)$ing['low_stock_threshold']; ?></td>
                                <td class="col-center">
                                    <?php echo $isLow ? '⚠️ Low Stock' : '✅ Sufficient'; ?>
                                </td>
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
