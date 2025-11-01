<?php
session_start();
require "../config/db_conn.php";

// --- Security Check ---
// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$logged_in_user_id = (int)$_SESSION['user_id'];
$error_message = '';
$success_message = '';

// --- ACTION: Update Profile ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $branch = trim($_POST['branch']); // Added branch
    $password = $_POST['password'];

    if (empty($username) || empty($email)) {
        $error_message = "Error: Username and Email cannot be blank.";
    } else {
        if (!empty($password)) {
            // --- Update profile AND password ---
            $password_hash = md5($password); // Using md5 as in your other files
            $stmt = $conn->prepare("UPDATE `USER` SET username = ?, email = ?, branch = ?, password_hash = ? WHERE user_id = ?");
            $stmt->bind_param("ssssi", $username, $email, $branch, $password_hash, $logged_in_user_id);
        } else {
            // --- Update profile WITHOUT changing password ---
            $stmt = $conn->prepare("UPDATE `USER` SET username = ?, email = ?, branch = ? WHERE user_id = ?");
            $stmt->bind_param("sssi", $username, $email, $branch, $logged_in_user_id);
        }
        
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $success_message = "Your profile has been updated successfully.";
            // Optional: Update session username in case it's displayed in the header
            $_SESSION['username'] = $username; 
        } else {
            // This can happen if they click "Update" without changing anything
            $error_message = "No changes were made.";
        }
        $stmt->close();
    }
}


// --- Fetch current user data to pre-fill the form ---
$stmt = $conn->prepare("SELECT username, email, branch FROM `USER` WHERE user_id = ?"); // Added branch
$stmt->bind_param("i", $logged_in_user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    // Failsafe in case session is broken
    session_destroy();
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Profile Settings</title>
    <link rel="stylesheet" href="../style/dashboard.css"">
</head>
<body>
<div class="container">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <?php include 'includes/header.php'; ?>

        <h2>My Profile Settings</h2>

        <!-- Display Error/Success Messages -->
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <div class="panel">
            <form method="POST" action="settings.php" class="form-vertical">
                <label>Username:</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>

                <label>Email:</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                
                <label>Branch:</label>
                <input type="text" name="branch" value="<?php echo htmlspecialchars($user['branch']); ?>">
                
                <hr style="border:0; border-top: 1px solid #eee; margin: 20px 0;">

                <label>New Password:</label>
                <input type="password" name="password" placeholder="Leave blank to keep your current password">
                
                <button type="submit" name="update_profile" class="btn">Update My Profile</button>
            </form>
        </div>

    </div>
</div>
</body>
</html>

