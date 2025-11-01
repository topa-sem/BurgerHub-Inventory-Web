<?php
session_start();
require "../config/db_conn.php";

// --- Security Check ---
// Assume user_id and user_type are stored in session upon login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    // If not logged in, redirect to login page (adjust 'login.php' as needed)
    header("Location: login.php");
    exit();
}

// Ensure only 'Admin' can access this page
if ($_SESSION['user_type'] !== 'Admin') {
    die("Access Denied: You do not have permission to manage users.");
}

// Get the logged-in admin's ID to prevent self-destruction
$logged_in_user_id = (int)$_SESSION['user_id'];
$error_message = '';
$success_message = '';

// --- ACTION: Delete User ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id_to_delete = intval($_POST['user_id']);

    if ($user_id_to_delete === $logged_in_user_id) {
        $error_message = "Error: You cannot delete your own account.";
    } elseif ($user_id_to_delete > 0) {
        $stmt = $conn->prepare("DELETE FROM `USER` WHERE user_id = ?");
        $stmt->bind_param("i", $user_id_to_delete);
        $stmt->execute();
        $stmt->close();
        $success_message = "User deleted successfully.";
    }
}

// --- ACTION: Add or Update User ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    $user_id = intval($_POST['user_id']); // 0 for new user, > 0 for existing
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $branch = trim($_POST['branch']);
    $user_type = trim($_POST['user_type']);
    $password = $_POST['password'];

    // Validate user type
    if (!in_array($user_type, ['Admin', 'Manager'])) {
        $user_type = 'Manager'; // Default to Manager if invalid
    }

    if ($user_id > 0) {
        // --- UPDATE existing user ---
        if ($user_id === $logged_in_user_id && $user_type !== 'Admin') {
            $error_message = "Error: You cannot demote your own account.";
        } else {
            if (!empty($password)) {
                // Update with new password
                $password_hash = md5($password); // Using md5 as in your original file
                $stmt = $conn->prepare("UPDATE `USER` SET username = ?, email = ?, user_type = ?, branch = ?, password_hash = ? WHERE user_id = ?");
                $stmt->bind_param("sssssi", $username, $email, $user_type, $branch, $password_hash, $user_id);
            } else {
                // Update without changing password
                $stmt = $conn->prepare("UPDATE `USER` SET username = ?, email = ?, user_type = ?, branch = ? WHERE user_id = ?");
                $stmt->bind_param("ssssi", $username, $email, $user_type, $branch, $user_id);
            }
            $stmt->execute();
            $stmt->close();
            $success_message = "User updated successfully.";
        }
    } else {
        // --- ADD new user ---
        if (empty($password)) {
            $error_message = "Error: Password is required for new users.";
        } elseif (!empty($username) && !empty($email)) {
            $password_hash = md5($password);
            $stmt = $conn->prepare("INSERT INTO `USER` (username, email, password_hash, user_type, branch, account_balance) VALUES (?, ?, ?, ?, ?, 0.00)");
            $stmt->bind_param("sssss", $username, $email, $password_hash, $user_type, $branch);
            $stmt->execute();
            $stmt->close();
            $success_message = "User added successfully.";
        } else {
            $error_message = "Error: Username and Email are required.";
        }
    }
}

// --- PREPARE FORM: Check if we are in "Edit" mode ---
$edit_mode = false;
$edit_user = [
    'user_id' => 0,
    'username' => '',
    'email' => '',
    'user_type' => 'Manager',
    'branch' => ''
];

if (isset($_GET['edit_id'])) {
    $user_id_to_edit = intval($_GET['edit_id']);
    if ($user_id_to_edit === $logged_in_user_id) {
        $error_message = "Notice: You cannot edit your own account from this panel.";
    } elseif ($user_id_to_edit > 0) {
        $stmt = $conn->prepare("SELECT user_id, username, email, user_type, branch FROM `USER` WHERE user_id = ?");
        $stmt->bind_param("i", $user_id_to_edit);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $edit_mode = true;
            $edit_user = $result->fetch_assoc();
        }
        $stmt->close();
    }
}


// --- Fetch all users for the table ---
$stmt = $conn->prepare("SELECT user_id, username, email, user_type, branch, account_balance FROM `USER` ORDER BY user_id DESC");
$stmt->execute();
$users = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Management - Admin</title>
    <link rel="stylesheet" href="../style/dashboard.css">
    <style>
        .action-btn {
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9em;
            margin: 2px;
            display: inline-block;
            border: none;
            cursor: pointer;
        }
        .btn-edit {
            background-color: #3498db; /* Blue */
            color: white;
        }
        .btn-delete {
            background-color: #e74c3c; /* Red */
            color: white;
        }
        .inline-form {
            display: inline;
        }
        .form-cancel-btn {
            text-decoration: none;
            color: #777;
            margin-left: 15px;
        }
    </style>
</head>
<body>
<div class="container">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <?php include 'includes/header.php'; ?>

        <h2>User Management</h2>

        <!-- Display Error/Success Messages -->
        <?php if ($error_message): ?>
            <div classclass="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <!-- Add / Edit User Form -->
        <div class="panel">
            <h3><?php echo $edit_mode ? 'Edit User' : 'Add New User'; ?></h3>
            <form method="POST" action="users.php" class="form-vertical">
                <!-- Hidden ID for editing -->
                <input type="hidden" name="user_id" value="<?php echo $edit_user['user_id']; ?>">

                <label>Username:</label>
                <input type="text" name="username" placeholder="Username" value="<?php echo htmlspecialchars($edit_user['username']); ?>" required>

                <label>Email:</label>
                <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($edit_user['email']); ?>" required>

                <label>Branch:</label>
                <input type="text" name="branch" placeholder="Branch" value="<?php echo htmlspecialchars($edit_user['branch']); ?>">

                <label>User Role:</label>
                <select name="user_type" required>
                    <option value="Manager" <?php echo ($edit_user['user_type'] === 'Manager') ? 'selected' : ''; ?>>Manager</option>
                    <option value="Admin" <?php echo ($edit_user['user_type'] === 'Admin') ? 'selected' : ''; ?>>Admin</option>
                </select>

                <label>Password:</label>
                <input type="password" name="password" placeholder="<?php echo $edit_mode ? 'Leave blank to keep same password' : 'Password'; ?>" <?php echo $edit_mode ? '' : 'required'; ?>>

                <button class="btn" name="save_user"><?php echo $edit_mode ? 'Update User' : 'Add User'; ?></button>
                <?php if ($edit_mode): ?>
                    <a href="users.php" class="form-cancel-btn">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- User List Panel -->
        <div class="panel">
            <h3>All Users</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Branch</th>
                        <th>Balance (RM)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($row = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['user_id'];?></td>
                        <td><?php echo htmlspecialchars($row['username']);?></td>
                        <td><?php echo htmlspecialchars($row['email']);?></td>
                        <td><?php echo htmlspecialchars($row['user_type']);?></td>
                        <td><?php echo htmlspecialchars($row['branch']);?></td>
                        <td><?php echo number_format($row['account_balance'],2);?></td>
                        <td>
                            <?php if ($row['user_id'] === $logged_in_user_id): ?>
                                <strong>(Your Account)</strong>
                            <?php else: ?>
                                <!-- Edit Button -->
                                <a href="users.php?edit_id=<?php echo $row['user_id']; ?>" class="action-btn btn-edit">Edit</a>
                                
                                <!-- Delete Button -->
                                <form method="POST" class="inline-form" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                    <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                    <button type="submit" name="delete_user" class="action-btn btn-delete">Delete</button>
                                </form>
                            <?php endif; ?>
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
