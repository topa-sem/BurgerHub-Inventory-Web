<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Location: ../login_signup/userlogin.php?error=Please+login');
    exit();
}

// Check if user is an Admin OR a Manager
if ($_SESSION['user_type'] !== 'Admin' && $_SESSION['user_type'] !== 'Manager') {
    // If they are neither, deny access
    header('Location: ../login_signup/userlogin.php?error=Access+denied');
    exit();
}

// --- Add necessary file ---
// Use require_once to prevent errors if db_conn.php was already included
require_once '../config/db_conn.php';

// --- Make money balance up-to-date ---
// Fetch the LATEST balance from the database
$stmt = $conn->prepare("SELECT account_balance FROM `USER` WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $current_balance = $row['account_balance'];
    // Update the session variable so it's correct everywhere
    $_SESSION['account_balance'] = $current_balance; 
} else {
    // Failsafe in case user was deleted but session exists
    header('Location: ../login_signup/userlogin.php?error=User+not+found');
    exit();
}
$stmt->close();
?>
<header class="topbar">
    <div class="top-left">
        <div class="brand"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
    </div>
    <div class="top-right">
        <!-- Display the up-to-date balance -->
        <span class="balance">RM <?php echo number_format($current_balance, 2); ?></span>
        <span class="icon">
             <a href="../manager/settings.php" class="profile">⚙️</a></span>
    </div>
</header>

