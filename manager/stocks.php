<?php
session_start();
require "../config/db_conn.php";

// Check if user is logged in and is Manager
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Manager') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- HANDLE POST ACTIONS ---

// ACTION: Update low stock threshold only
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_threshold'])) {
    $ingredient_id = intval($_POST['ingredient_id']);
    $threshold = intval($_POST['low_stock_threshold']);

    if ($ingredient_id > 0 && $threshold >= 0) {
        // Insert or update user's low stock threshold only
        $stmt = $conn->prepare("
            INSERT INTO USER_INGREDIENT_STOCK (user_id, ingredient_id, current_stock_quantity, low_stock_threshold) 
            VALUES (?, ?, 0, ?)
            ON DUPLICATE KEY UPDATE 
            low_stock_threshold = VALUES(low_stock_threshold)
        ");
        $stmt->bind_param("iii", $user_id, $ingredient_id, $threshold);
        $stmt->execute(); 
        $stmt->close();
    }
    header("Location: stocks.php");
    exit();
}

// GATHER DATA: Get all ingredients and user's stock levels
$ingredients = [];
$stmt = $conn->prepare("
    SELECT 
        i.ingredient_id, 
        i.name, 
        i.unit,
        COALESCE(uis.current_stock_quantity, 0) as current_stock_quantity,
        COALESCE(uis.low_stock_threshold, 10) as low_stock_threshold
    FROM INGREDIENT i
    LEFT JOIN USER_INGREDIENT_STOCK uis ON i.ingredient_id = uis.ingredient_id AND uis.user_id = ?
    ORDER BY i.name ASC
");
$stmt->bind_param("i", $user_id);
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
    <title>My Stock Management - Manager</title>

</head>
<body>
<div class="container">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <?php include 'includes/header.php'; ?>

        <h2>My Ingredient Stock Levels</h2>

        <div class="panel">
            <table>
                <thead>
                    <tr>
                        <th>Ingredient</th>
                        <th class="col-num">Unit</th>
                        <th class="col-num">Current Stock</th>
                        <th class="col-num">Low Stock Threshold</th>
                        <th class="col-center">Update Threshold</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ingredients)): ?>
                        <tr><td colspan="5">No ingredients found in the system.</td></tr>
                    <?php else: ?>
                        <?php foreach ($ingredients as $ing): 
                            $low_stock_class = ($ing['current_stock_quantity'] < $ing['low_stock_threshold']) ? 'status-cancelled' : '';
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ing['name']); ?></td>
                                <td class="col-num"><?php echo htmlspecialchars($ing['unit']); ?></td>
                                
                                <td class="col-num <?php echo $low_stock_class; ?>">
                                    <?php echo number_format($ing['current_stock_quantity'], 2); ?>
                                    <?php if ($low_stock_class): ?>
                                        <br><small style="color: #e74c3c;">(Low Stock)</small>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="col-num"><?php echo (int)$ing['low_stock_threshold']; ?></td>
                                
                                <td class="col-center">
                                    <form method="POST" class="stock-form">
                                        <input type="hidden" name="ingredient_id" value="<?php echo $ing['ingredient_id']; ?>">
                                        
                                        <input type="number" step="1" min="0"
                                               name="low_stock_threshold" 
                                               value="<?php echo (int)$ing['low_stock_threshold']; ?>" 
                                               required>
                                        
                                        <button type="submit" name="update_threshold" class="action-btn btn-edit">Update</button>
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