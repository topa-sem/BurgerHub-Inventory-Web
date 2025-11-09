<?php
// /admin/stocks.php
session_start();
require "../config/db_conn.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../log_signup/userlogin.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$error_message = '';
$success_message = '';

// --- HANDLE POST ACTIONS ---

// Add new ingredient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_ingredient'])) {
    $name = trim($_POST['name']);
    $unit = trim($_POST['unit']);

    if (!empty($name) && !empty($unit)) {
        // Check if ingredient already exists for this user
        $check_stmt = $conn->prepare("SELECT ingredient_id FROM INGREDIENT WHERE name = ? AND user_id = ?");
        $check_stmt->bind_param("si", $name, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_message = "You already have an ingredient with this name.";
        } else {
            // Insert into INGREDIENT table with user_id
            $stmt = $conn->prepare("INSERT INTO INGREDIENT (user_id, name, unit) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $name, $unit);
            
            if ($stmt->execute()) {
                $success_message = "New ingredient added successfully!";
            } else {
                $error_message = "Error adding ingredient: " . $conn->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    } else {
        $error_message = "Please fill in all required fields.";
    }
}

// Delete ingredient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_ingredient'])) {
    $ingredient_id = intval($_POST['ingredient_id']);
    if ($ingredient_id > 0) {
        // Only delete if the ingredient belongs to the current user
        $stmt = $conn->prepare("DELETE FROM INGREDIENT WHERE ingredient_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $ingredient_id, $user_id);
        if ($stmt->execute()) {
            $success_message = "Ingredient deleted successfully!";
        } else {
            $error_message = "Error deleting ingredient: " . $conn->error;
        }
        $stmt->close();
    }
}

// GATHER DATA: Get current user's ingredients for display
$ingredients = [];
$stmt = $conn->prepare("
    SELECT ingredient_id, name, unit 
    FROM INGREDIENT 
    WHERE user_id = ?
    ORDER BY name ASC
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
    <title>Ingredient Management - <?php echo $user_type; ?></title>
</head>
<body>
<div class="container">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <?php include 'includes/header.php'; ?>

        <!-- Display Messages -->
        <?php if ($success_message): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <h2>Ingredient Management</h2>

        <!-- Add New Ingredient Form -->
        <?php if ($user_type === 'Admin'): ?>
        <div class="panel">
            <h2>Add New Ingredient</h2>
            <form method="POST" class="form-inline">
                <div>
                    <input type="text" name="name" placeholder="Ingredient Name" required>
                </div>
                <div>
                    <input type="text" name="unit" placeholder="Unit (kg, pcs, L)" required>
                </div>
                <div>
                    <button class="btn" name="add_ingredient">Add Ingredient</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Ingredients List -->
        <div class="panel">
            <h2>My Ingredients</h2>
            <table>
                <thead>
                    <tr>
                        <th>Ingredient</th>
                        <th>Unit</th>
                        <?php if ($user_type === 'Admin'): ?>
                        <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ingredients)): ?>
                        <tr>
                            <td colspan="<?php echo $user_type === 'Admin' ? '3' : '2'; ?>">
                                No ingredients found. 
                                <?php if ($user_type === 'Admin'): ?>
                                    Add your first ingredient above.
                                <?php else: ?>
                                    No ingredients available.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ingredients as $ing): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ing['name']); ?></td>
                                <td><?php echo htmlspecialchars($ing['unit']); ?></td>
                                <?php if ($user_type === 'Admin'): ?>
                                <td>
                                    <form method="POST" class="inline-form" onsubmit="return confirm('Are you sure you want to delete this ingredient?');">
                                        <input type="hidden" name="ingredient_id" value="<?php echo $ing['ingredient_id']; ?>">
                                        <button type="submit" name="delete_ingredient" class="action-btn btn-delete">Delete</button>
                                    </form>
                                </td>
                                <?php endif; ?>
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