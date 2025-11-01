<?php
session_start();
require "../config/db_conn.php";

// ACTION: Update an ingredient's threshold
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_threshold'])) {
    $ingredient_id = intval($_POST['ingredient_id']);
    // Use intval() because the database column low_stock_threshold is an INT
    $threshold = intval($_POST['low_stock_threshold']);

    if ($ingredient_id > 0 && $threshold >= 0) {
        $stmt = $conn->prepare("UPDATE INGREDIENT SET low_stock_threshold = ? WHERE ingredient_id = ?");
        // Bind as "ii" (integer, integer)
        $stmt->bind_param("ii", $threshold, $ingredient_id);
        $stmt->execute(); $stmt->close();
    }
    // Redirect back to the same page
    header("Location: stocks.php");
    exit();
}

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
    <title>Stock Management</title>
    <link rel="stylesheet" href="../style/dashboard.css"">
    <style>
        /* Specific styles for this form to make it compact */
        .stock-form {
            display: flex;
            justify-content: center; /* CHANGED: Aligns form to the center */
            align-items: center;
        }
        .stock-form input[type="number"] {
            width: 80px; /* Smaller input box */
            margin-right: 10px;
        }
        .col-num {
            text-align: right;
        }
        /* ADDED: New style for centering */
        .col-center {
            text-align: center;
        }
    </style>
</head>
<body>
<div class="container">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <?php include 'includes/header.php'; ?>

        <h2>Stock Management - Manager</h2>

        <div class="panel">
            <table>
                <thead>
                    <tr>
                        <th>Ingredient</th>
                        <th class="col-num">Unit</th>
                        <th class="col-num">Current Stock</th>
                        <!-- Merged headings as requested -->
                        <!-- CHANGED: from col-num to col-center -->
                        <th class="col-center" colspan="2">Update Low Stock Threshold</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ingredients)): ?>
                        <tr><td colspan="5">No ingredients found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($ingredients as $ing): 
                            // Check if stock is low
                            $low_stock_class = ($ing['current_stock_quantity'] < $ing['low_stock_threshold']) ? 'status-cancelled' : '';
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ing['name']); ?></td>
                                <td class="col-num"><?php echo htmlspecialchars($ing['unit']); ?></td>
                                
                                <!-- Apply warning class if stock is low -->
                                <td class="col-num <?php echo $low_stock_class; ?>">
                                    <?php echo number_format($ing['current_stock_quantity'], 2); ?>
                                </td>
                                
                                <!-- This cell contains the form for updating -->
                                <!-- CHANGED: from col-num to col-center -->
                                <td class="col-center" colspan="2">
                                    <form method="POST" class="stock-form">
                                        <input type="hidden" name="ingredient_id" value="<?php echo $ing['ingredient_id']; ?>">
                                        
                                        <!-- The threshold input (step="1" for INT) -->
                                        <input type="number" 
                                               name="low_stock_threshold" 
                                               value="<?php echo (int)$ing['low_stock_threshold']; ?>" 
                                               step="1" 
                                               min="0" 
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

