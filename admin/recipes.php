<?php
session_start();
require "../config/db_conn.php";

$selected_product_id = null;
$selected_product_name = '';
$current_recipe = [];
$all_products = [];
$all_ingredients = [];

// --- HANDLE POST ACTIONS (Update, Delete, Add) ---

// ACTION: Update an ingredient's quantity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_recipe'])) {
    $product_id = intval($_POST['product_id']);
    $ingredient_id = intval($_POST['ingredient_id']);
    $quantity = floatval($_POST['quantity']);

    if ($product_id > 0 && $ingredient_id > 0 && $quantity > 0) {
        $stmt = $conn->prepare("UPDATE RECIPE SET quantity_required = ? WHERE product_id = ? AND ingredient_id = ?");
        $stmt->bind_param("dii", $quantity, $product_id, $ingredient_id);
        $stmt->execute(); $stmt->close();
    }
    // Redirect back to the same product page
    header("Location: recipes.php?product_id=" . $product_id);
    exit();
}

// ACTION: Delete an ingredient from a recipe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_recipe'])) {
    $product_id = intval($_POST['product_id']);
    $ingredient_id = intval($_POST['ingredient_id']);

    if ($product_id > 0 && $ingredient_id > 0) {
        $stmt = $conn->prepare("DELETE FROM RECIPE WHERE product_id = ? AND ingredient_id = ?");
        $stmt->bind_param("ii", $product_id, $ingredient_id);
        $stmt->execute(); $stmt->close();
    }
    header("Location: recipes.php?product_id=" . $product_id);
    exit();
}

// ACTION: Add a new ingredient to a recipe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_recipe'])) {
    $product_id = intval($_POST['product_id']);
    $ingredient_id = intval($_POST['ingredient_id']);
    $quantity = floatval($_POST['quantity']);

    if ($product_id > 0 && $ingredient_id > 0 && $quantity > 0) {
        // Use ON DUPLICATE KEY UPDATE to prevent errors if ingredient already exists
        $sql = "INSERT INTO RECIPE (product_id, ingredient_id, quantity_required) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE quantity_required = VALUES(quantity_required)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iid", $product_id, $ingredient_id, $quantity);
        $stmt->execute(); $stmt->close();
    }
    header("Location: recipes.php?product_id=" . $product_id);
    exit();
}


// --- GATHER DATA FOR PAGE DISPLAY ---

// Check if a product is selected from the URL
if (isset($_GET['product_id'])) {
    $selected_product_id = intval($_GET['product_id']);
}

// 1. Fetch all products (for the main dropdown)
$stmt = $conn->prepare("SELECT product_id, name FROM PRODUCT ORDER BY name ASC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $all_products[] = $row;
    if ($row['product_id'] == $selected_product_id) {
        $selected_product_name = $row['name'];
    }
}
$stmt->close();

// 2. Fetch all ingredients (for the "Add" dropdown)
$stmt = $conn->prepare("SELECT ingredient_id, name, unit FROM INGREDIENT ORDER BY name ASC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $all_ingredients[] = $row;
}
$stmt->close();

// 3. If a product is selected, fetch its current recipe
if ($selected_product_id) {
    $stmt = $conn->prepare("
        SELECT I.ingredient_id, I.name, I.unit, R.quantity_required 
        FROM RECIPE R
        JOIN INGREDIENT I ON R.ingredient_id = I.ingredient_id
        WHERE R.product_id = ?
        ORDER BY I.name
    ");
    $stmt->bind_param("i", $selected_product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $current_recipe[] = $row;
    }
    $stmt->close();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Recipe Management - Admin</title>
    <link rel="stylesheet" href="../style/dashboard.css">
</head>
<body>
<div class="container">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <?php include 'includes/header.php'; ?>

        <h2>Recipe Management</h2>

        <div class="panel">
            <form method="GET" action="recipes.php" class="form-vertical">
                <label>Select a product:</label>
                <select name="product_id" onchange="this.form.submit()">
                    <option value="">-- Choose a Product --</option>
                    <?php foreach ($all_products as $prod): ?>
                        <option value="<?php echo $prod['product_id']; ?>" <?php echo ($prod['product_id'] == $selected_product_id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($prod['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <noscript><button type="submit" class="btn">View</button></noscript>
            </form>
        </div>

        <?php if ($selected_product_id): ?>
        <div class="recipe-grid">
            
            <div class="panel">
                <h3>Recipe for <?php echo htmlspecialchars($selected_product_name); ?></h3>
                <table>
                    <thead>
                        <tr>
                            <th>Ingredient</th>
                            <th>Amount</th>
                            <th>Unit</th>
                            <th width="200px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($current_recipe)): ?>
                            <tr>
                                <td colspan="4">This product has no recipe. Add ingredients below.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($current_recipe as $item): ?>
                            <tr>
                                <form method="POST" class="form-vertical">
                                    <input type="hidden" name="product_id" value="<?php echo $selected_product_id; ?>">
                                    <input type="hidden" name="ingredient_id" value="<?php echo $item['ingredient_id']; ?>">
                                    
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td>
                                        <input type="number" step="0.01" name="quantity" value="<?php echo $item['quantity_required']; ?>" min="0.01" required>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                    <td>
                                        <button type="submit" name="update_recipe" class="action-btn btn-edit">Update</g>
                                        <button type="submit" name="delete_recipe" class="action-btn btn-delete" onclick="return confirm('Remove this ingredient from the recipe?');">Delete</button>
                                    </td>
                                </form>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="panel">
                <h3>Add Ingredient to <?php echo htmlspecialchars($selected_product_name); ?></h3>
                <form method="POST" class="form-vertical">
                    <input type="hidden" name="product_id" value="<?php echo $selected_product_id; ?>">
                    
                    <label for="ingredient_select">Ingredient:</label>
                    <select id="ingredient_select" name="ingredient_id" required>
                        <option value="">-- Choose an ingredient --</option>
                        <?php foreach ($all_ingredients as $ing): ?>
                            <option value="<?php echo $ing['ingredient_id']; ?>">
                                <?php echo htmlspecialchars($ing['name']) . ' (' . htmlspecialchars($ing['unit']) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="quantity_input">Quantity:</label>
                    <input id="quantity_input" type="number" step="0.01" name="quantity" placeholder="Amount" min="0.01" required>
                    
                    <button type="submit" name="add_recipe" class="btn">Add to Recipe</button>
                </form>
            </div>

        </div>
        <?php endif; ?> </div>
</div>
</body>
</html>