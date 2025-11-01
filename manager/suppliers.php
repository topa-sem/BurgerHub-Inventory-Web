<?php
session_start();
require "../config/db_conn.php";

$manage_id = null;
$manage_name = '';
$all_suppliers = [];
$all_ingredients = [];
$supplier_ingredients = [];

// --- HANDLE POST ACTIONS ---

// ACTION: Add a new supplier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_supplier'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);

    if (!empty($name)) {
        $stmt = $conn->prepare("INSERT INTO SUPPLIER (name, phone, contact_email) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $phone, $email);
        $stmt->execute(); $stmt->close();
    }
    header("Location: suppliers.php");
    exit();
}

// ACTION: Delete a supplier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_supplier'])) {
    $supplier_id = intval($_POST['supplier_id']);
    if ($supplier_id > 0) {
        $stmt = $conn->prepare("DELETE FROM SUPPLIER WHERE supplier_id = ?");
        $stmt->bind_param("i", $supplier_id);
        $stmt->execute(); $stmt->close();
    }
    header("Location: suppliers.php");
    exit();
}

// ACTION: Add an ingredient to a supplier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_ingredient_to_supplier'])) {
    $supplier_id = intval($_POST['supplier_id']);
    $ingredient_id = intval($_POST['ingredient_id']);
    $price = floatval($_POST['price']); // NEW

    if ($supplier_id > 0 && $ingredient_id > 0) {
        // NEW: Added price to query
        $stmt = $conn->prepare("INSERT INTO SUPPLIER_INGREDIENT (supplier_id, ingredient_id, price) VALUES (?, ?, ?)
                                ON DUPLICATE KEY UPDATE price = VALUES(price)");
        $stmt->bind_param("iid", $supplier_id, $ingredient_id, $price); // NEW: iid
        $stmt->execute(); $stmt->close();
    }
    header("Location: suppliers.php?manage_id=" . $supplier_id);
    exit();
}

// ACTION: Update price or Remove ingredient from supplier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['update_ingredient_price']) || isset($_POST['remove_ingredient_from_supplier']))) {
    $supplier_id = intval($_POST['supplier_id']);
    $ingredient_id = intval($_POST['ingredient_id']);

    if ($supplier_id > 0 && $ingredient_id > 0) {
        
        if (isset($_POST['update_ingredient_price'])) {
            // --- UPDATE PRICE ---
            $price = floatval($_POST['price']);
            $stmt = $conn->prepare("UPDATE SUPPLIER_INGREDIENT SET price = ? WHERE supplier_id = ? AND ingredient_id = ?");
            $stmt->bind_param("dii", $price, $supplier_id, $ingredient_id);
            $stmt->execute(); $stmt->close();

        } elseif (isset($_POST['remove_ingredient_from_supplier'])) {
            // --- REMOVE INGREDIENT ---
            $stmt = $conn->prepare("DELETE FROM SUPPLIER_INGREDIENT WHERE supplier_id = ? AND ingredient_id = ?");
            $stmt->bind_param("ii", $supplier_id, $ingredient_id);
            $stmt->execute(); $stmt->close();
        }
    }
    header("Location: suppliers.php?manage_id=" . $supplier_id);
    exit();
}


// --- GATHER DATA FOR PAGE DISPLAY ---

// Check if we are in "Manage" mode
if (isset($_GET['manage_id'])) {
    $manage_id = intval($_GET['manage_id']);
    
    // 1. Get the supplier's name
    $stmt = $conn->prepare("SELECT name FROM SUPPLIER WHERE supplier_id = ?");
    $stmt->bind_param("i", $manage_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $manage_name = $row['name'];
    } else { $manage_id = null; }
    $stmt->close();

    // 2. Get all ingredients (for the "add" dropdown)
    $stmt = $conn->prepare("SELECT ingredient_id, name, unit FROM INGREDIENT ORDER BY name");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $all_ingredients[] = $row;
    $stmt->close();

    // 3. Get ingredients this supplier provides (NEW: get price)
    $stmt = $conn->prepare("
        SELECT I.ingredient_id, I.name, I.unit, SI.price
        FROM SUPPLIER_INGREDIENT SI
        JOIN INGREDIENT I ON SI.ingredient_id = I.ingredient_id
        WHERE SI.supplier_id = ?
        ORDER BY I.name
    ");
    $stmt->bind_param("i", $manage_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $supplier_ingredients[] = $row;
    $stmt->close();

} else {
    // Default view: Get all suppliers
    $stmt = $conn->prepare("SELECT * FROM SUPPLIER ORDER BY name");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $all_suppliers[] = $row;
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Suppliers</title>
    <link rel="stylesheet" href="../style/dashboard.css"">
</head>
<body>
<div class="container">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <?php include 'includes/header.php'; ?>

        <?php if ($manage_id): ?>
            <!--
            // ============================
            // ---  MANAGE VIEW (CUSTOMIZE) ---
            // ============================
            -->
            <h2>Managing Supplier: <?php echo htmlspecialchars($manage_name); ?></h2>
            <a href="suppliers.php" class="back-link">&larr; Back to all suppliers</a>

            <div class="supplier-grid">
                <div class="panel">
                    <h3>Ingredients Supplied</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Ingredient</th>
                                <th>Unit</th>
                                <!-- NEW: Combined Price & Actions Column -->
                                <th width="280px">Price (RM) & Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($supplier_ingredients)): ?>
                                <tr><td colspan="3">This supplier has no ingredients.</td></tr>
                            <?php else: ?>
                                <?php foreach ($supplier_ingredients as $ing): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ing['name']); ?></td>
                                    <td><?php echo htmlspecialchars($ing['unit']); ?></td>
                                    <!-- NEW: Form-in-a-cell for updating/deleting -->
                                    <td class="form-cell">
                                        <form method="POST">
                                            <input type="hidden" name="supplier_id" value="<?php echo $manage_id; ?>">
                                            <input type="hidden" name="ingredient_id" value="<?php echo $ing['ingredient_id']; ?>">
                                            
                                            <input type="number" step="0.01" min="0" name="price" value="<?php echo htmlspecialchars($ing['price']); ?>" required>
                                            
                                            <button type="submit" name="update_ingredient_price" class="action-btn btn-edit">Update</button>
                                            
                                            <button type="submit" name="remove_ingredient_from_supplier" class="action-btn btn-delete" onclick="return confirm('Remove this ingredient from this supplier?');">&times;</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="panel">
                    <h3>Add Ingredient to Supplier</h3>
                    <form method="POST" class="form-vertical">
                        <input type="hidden" name="supplier_id" value="<?php echo $manage_id; ?>">
                        
                        <label for="ingredient_select">Ingredient:</label>
                        <select id="ingredient_select" name="ingredient_id" required>
                            <option value="">-- Choose an ingredient --</option>
                            <?php foreach ($all_ingredients as $ing): ?>
                                <option value="<?php echo $ing['ingredient_id']; ?>">
                                    <?php echo htmlspecialchars($ing['name']) . ' (' . htmlspecialchars($ing['unit']) . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <!-- NEW: Price field -->
                        <label for="price_input">Price (RM):</label>
                        <input id="price_input" type="number" step="0.01" min="0" name="price" placeholder="e.g. 12.50" required>

                        <button type="submit" name="add_ingredient_to_supplier" class="btn">Add Ingredient</button>
                    </form>
                </div>
            </div>

        <?php else: ?>
            <!--
            // ============================
            // ---  DEFAULT VIEW (LIST ALL) ---
            // ============================
            -->
            <h2>Add New Supplier</h2>
            <div class="panel">
                <form method="POST" class="form-inline">
                    <input type="text" name="name" placeholder="Supplier Name" required>
                    <input type="tel" name="phone" placeholder="Phone">
                    <input type="email" name="email" placeholder="Email">
                    <button class="btn" name="add_supplier">Add Supplier</button>
                </form>
            </div>

            <h2>All Suppliers</h2>
            <div class="panel">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_suppliers as $supplier): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($supplier['name']); ?></td>
                            <td><?php echo htmlspecialchars($supplier['phone']); ?></td>
                            <td><?php echo htmlspecialchars($supplier['contact_email']); ?></td>
                            <td>
                                <a href="suppliers.php?manage_id=<?php echo $supplier['supplier_id']; ?>" class="action-btn btn-manage">Manage Ingredients</a>
                                <form method="POST" class="inline-form" onsubmit="return confirm('Are you sure you want to delete this supplier? This cannot be undone.');">
                                    <input type="hidden" name="supplier_id" value="<?php echo $supplier['supplier_id']; ?>">
                                    <button type="submit" name="delete_supplier" class="action-btn btn-delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>
</div>
</body>
</html>