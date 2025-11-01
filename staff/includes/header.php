<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// --- Check if user is logged in ---
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Location: ../login_signup/userlogin.php?error=Please+login');
    exit();
}

// --- Restrict access only to Staff users ---
if ($_SESSION['user_type'] !== 'Staff') {
    header('Location: ../login_signup/userlogin.php?error=Access+denied');
    exit();
}

// --- Connect to the database ---
require_once '../config/db_conn.php';

// --- Optional: Update latest account balance (if your staff users also have balance info) ---
$stmt = $conn->prepare("SELECT account_balance FROM `USER` WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $current_balance = $row['account_balance'];
    $_SESSION['account_balance'] = $current_balance; 
} else {
    // Failsafe: if user deleted but session still exists
    header('Location: ../login_signup/userlogin.php?error=User+not+found');
    exit();
}
$stmt->close();
?>

<!-- ================= HEADER BAR ================= -->
<header class="topbar">
    <div class="top-left">
        <div class="brand">
            👋 Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
        </div>
    </div>

    <div class="top-right">
        <!-- Show balance only if relevant -->
        <span class="balance">
            RM <?php echo number_format($_SESSION['account_balance'], 2); ?>
        </span>

         <!-- ✅ Added role indicator -->
        <div class="user-role">
            👤 <?php echo ucfirst($_SESSION['user_type']); ?>
        </div>

        <!-- Settings button (you can create staff/settings.php later) -->
        <span class="icon">
            <a href="../staff/settings.php" class="profile">⚙️</a>
        </span>
    </div>
</header>
