<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Check if user is logged in 
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Location: ../login_signup/userlogin.php?error=Please+login');
    exit();
}

// Restrict access only to Staff 
if ($_SESSION['user_type'] !== 'Staff') {
    header('Location: ../login_signup/userlogin.php?error=Access+denied');
    exit();
}

// Connect to the database
require_once '../config/db_conn.php';

// Update latest acc balance
$stmt = $conn->prepare("SELECT account_balance, profile_image FROM `USER` WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $current_balance = $row['account_balance'];
    $_SESSION['account_balance'] = $current_balance;
    $_SESSION['profile_image'] = $row['profile_image'] ?: 'default.png';
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
             Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>
        </div>
    </div>

    <div class="top-right">
        <!-- ✅ Profile picture replaces role icon -->
        <a href="settings.php" class="profile-link">
            <img src="uploads/<?php echo $_SESSION['profile_image'] ?? 'default.png'; ?>" 
                 alt="Profile Picture" class="profile-avatar">
        </a>

        <!-- Show balance -->
        <span class="balance">
            RM <?php echo number_format($_SESSION['account_balance'], 2); ?>
        </span>

        <!-- Settings button -->
        <span class="icon">
            <a href="settings.php" class="profile">⚙️</a>
        </span>
    </div>
</header>

<style>
//Profile Styling 
.profile-link {
    display: flex;
    align-items: center;
    text-decoration: none;
}

.profile-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #ffa726;
    transition: transform 0.2s ease;
}

.profile-avatar:hover {
    transform: scale(1.05);
}

//Topbar Layout 
.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #222;
    color: #fff;
    padding: 10px 20px;
}

.top-left .brand {
    font-weight: bold;
    font-size: 1.1em;
}

.top-right {
    display: flex;
    align-items: center;
    gap: 15px;
}

.balance {
    background: #333;
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 0.9em;
}

.icon a {
    color: #ffa726;
    text-decoration: none;
    font-size: 1.2em;
}
</style>
