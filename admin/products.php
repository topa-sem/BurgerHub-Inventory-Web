<?php
// /admin/products.php
session_start();
require "../config/db_conn.php";

$edit_id = null;
$edit_name = '';
$edit_price = '';

// Handle product creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    if ($name !== '' && $price > 0) {
        $stmt = $conn->prepare("INSERT INTO `PRODUCT` (name, selling_price) VALUES (?, ?)");
        $stmt->bind_param("sd", $name, $price);
        $stmt->execute(); $stmt->close();
        header("Location: products.php");
        exit();
    }
}

// Handle product deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $product_id = intval($_POST['product_id']);
    $stmt = $conn->prepare("DELETE FROM `PRODUCT` WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute(); $stmt->close();
    header("Location: products.php");
    exit();
}

// Handle product update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $product_id = intval($_POST['product_id']);
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    
    if ($name !== '' && $price > 0 && $product_id > 0) {
        $stmt = $conn->prepare("UPDATE `PRODUCT` SET name = ?, selling_price = ? WHERE product_id = ?");
        $stmt->bind_param("sdi", $name, $price, $product_id);
        $stmt->execute(); $stmt->close();
        header("Location: products.php");
        exit();
    }
}

// Check if we are editing (GET request)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $stmt = $conn->prepare("SELECT name, selling_price FROM `PRODUCT` WHERE product_id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($product = $result->fetch_assoc()) {
        $edit_name = $product['name'];
        $edit_price = $product['selling_price'];
    }
    $stmt->close();
}

// Fetch all products (for the table)
$stmt = $conn->prepare("SELECT product_id, name, selling_price FROM `PRODUCT` ORDER BY product_id DESC");
$stmt->execute(); $products = $stmt->get_result(); $stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Products - Admin</title>
    <link rel="stylesheet" href="../style/dashboard.css">
</head>
<body>
<div class="container">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <?php include 'includes/header.php'; ?>
        <h2><?php echo ($edit_id ? 'Edit Product' : 'Add Product'); ?></h2>
            <div class="panel">
            <form method="POST" class="form-inline">
                <?php if ($edit_id): ?>
                    <input type="hidden" name="product_id" value="<?php echo $edit_id; ?>">
                    <input type="text" name="name" placeholder="Product name" value="<?php echo htmlspecialchars($edit_name); ?>" required> 
                    <input type="number" step="0.01" name="price" placeholder="Price (RM)" value="<?php echo htmlspecialchars($edit_price); ?>" required>
                    <button class="btn" name="update_product">Update</button>
                    <a href="products.php" class="action-btn btn-edit">Cancel</a>
                <?php else: ?>
                    <input type="text" name="name" placeholder="Product name" required> 
                    <input type="number" step="0.01" name="price" placeholder="Price (RM)" required> 
                    <button class="btn" name="add_product">Add</button>
                <?php endif; ?>
            </form>
        </div>
            <h2>All Products</h2>
        <div class="panel">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Price (RM)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i=1; while($row = $products->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $i++;?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo number_format($row['selling_price'],2); ?></td>
                        <td>
                            <a href="products.php?edit_id=<?php echo $row['product_id']; ?>" class="action-btn btn-edit">Edit</a>
                            
                            <form method="POST" class="inline-form" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                                <button type="submit" name="delete_product" class="action-btn btn-delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>