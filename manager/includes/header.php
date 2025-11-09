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

// Handle date selection
$selected_date = date('Y-m-d'); // Default to today

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_date'])) {
    $selected_date = $_POST['selected_date'];
    $_SESSION['selected_date'] = $selected_date;
} elseif (isset($_SESSION['selected_date'])) {
    $selected_date = $_SESSION['selected_date'];
} else {
    $_SESSION['selected_date'] = $selected_date;
}

// Format date for display
$display_date = date('d M Y', strtotime($selected_date));
$is_today = ($selected_date === date('Y-m-d'));
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manager Dashboard</title>
    <link rel="stylesheet" href="../style/dashboard.css">
</head>
<body>
<header class="topbar">
    <div class="top-left">
        <div class="brand"><?php echo htmlspecialchars($_SESSION['username']); ?> (Manager)</div>
    </div>
    <div class="top-right">
        <!-- Date Selector -->
        <div class="date-selector">
            <form method="POST" class="date-form" id="dateForm">
                <input type="date" 
                       id="datePicker" 
                       name="selected_date" 
                       value="<?php echo htmlspecialchars($selected_date); ?>" 
                       max="<?php echo date('Y-m-d'); ?>"
                       onchange="document.getElementById('dateForm').submit()"
                       class="<?php echo $is_today ? 'date-pulse' : ''; ?>"
                       title="Select date - <?php echo htmlspecialchars($display_date); ?>">
                <!-- We'll use JavaScript to display the formatted date -->
            </form>
        </div>
        
        <!-- Display the up-to-date balance -->
        <span class="balance">RM <?php echo number_format($current_balance, 2); ?></span>
        <span class="icon">
             <a href="../manager/settings.php">⚙️</a></span>
    </div>
</header>

<script>
// Optional: Close date picker when clicking outside
document.addEventListener('click', function(e) {
    const datePicker = document.getElementById('datePicker');
    if (e.target !== datePicker) {
        datePicker.blur();
    }
});
</script>