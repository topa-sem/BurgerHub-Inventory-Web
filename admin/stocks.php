<?php
// /admin/stocks.php
session_start();
require "../config/db_conn.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login_signup/userlogin.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// Update threshold
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_threshold'])) {
    $ingredient_id = intval($_POST['ingredient_id']);
    $threshold = intval($_POST['low_stock_threshold']);

    if ($ingredient_id > 0 && $threshold >= 0) {
        // Use INSERT ... ON DUPLICATE KEY UPDATE to handle both cases
        // FIXED: Changed table name from INGREDIENT_STOCK to USER_INGREDIENT_STOCK
        $stmt = $conn->prepare("
            INSERT INTO USER_INGREDIENT_STOCK (ingredient_id, user_id, current_stock_quantity, low_stock_threshold) 
            VALUES (?, ?, 0, ?)
            ON DUPLICATE KEY UPDATE low_stock_threshold = ?
        ");
        $stmt->bind_param("iiii", $ingredient_id, $user_id, $threshold, $threshold);
        
        if ($stmt->execute()) {
            $success_message = "Threshold updated successfully!";
        } else {
            $error_message = "Error updating threshold: " . $conn->error;
        }
        $stmt->close();
    }
}

// Get current user's stock data - FIXED table name
$stock_data = [];
$stmt = $conn->prepare("
    SELECT i.ingredient_id, i.name, i.unit, 
           COALESCE(uis.current_stock_quantity, 0) as current_stock,
           COALESCE(uis.low_stock_threshold, 10) as threshold
    FROM INGREDIENT i
    LEFT JOIN USER_INGREDIENT_STOCK uis ON i.ingredient_id = uis.ingredient_id AND uis.user_id = ?
    ORDER BY i.name ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $stock_data[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Stock</title>
    </style>
</head>
<body>
<div class="container">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <?php include 'includes/header.php'; ?>

        <?php if ($success_message): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <h2>Stock Levels</h2>
        
        <div class="panel">
            <table>
                <thead>
                    <tr>
                        <th>Ingredient</th>
                        <th>Unit</th>
                        <th>Current Stock</th>
                        <th>Low Stock Threshold</th>
                        <th>Update Threshold</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stock_data)): ?>
                        <tr><td colspan="5">No ingredients available.</td></tr>
                    <?php else: ?>
                        <?php foreach ($stock_data as $item): 
                            $is_low = $item['current_stock'] < $item['threshold'];
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                <td class="<?php echo $is_low ? 'low-stock' : ''; ?>">
                                    <?php echo number_format($item['current_stock'], 2); ?>
                                    <?php if ($is_low): ?>
                                        <br><small>(Low Stock)</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $item['threshold']; ?></td>
                                <td>
                                    <form method="POST" class="threshold-form">
                                        <input type="hidden" name="ingredient_id" value="<?php echo $item['ingredient_id']; ?>">
                                        <input type="number" name="low_stock_threshold" 
                                               value="<?php echo $item['threshold']; ?>" 
                                               min="0" required>
                                        <button type="submit" name="update_threshold" class="btn">Update</button>
                                    </form>
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