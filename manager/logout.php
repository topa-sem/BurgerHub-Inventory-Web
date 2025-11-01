<?php
session_start();

// --- ACTION: User has CONFIRMED the logout ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_logout'])) {
    
    // Unset all session variables
    session_unset();
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    // Using the path from your original file
    header("Location: ../login_signup/userlogin.php?success=Logged+out");
    exit();
}

// --- DISPLAY: Show confirmation page ---
// If not confirmed, show the HTML confirmation page
?>
<!DOCTYPE html>
<html>
<head>
    <title>Log Out</title>
    <link rel="stylesheet" href="../style/dashboard.css"">
    <style>
        /* Styles for the confirmation buttons */
        .logout-actions {
            margin-top: 20px;
        }
        .btn-cancel {
            text-decoration: none;
            padding: 10px 15px;
            background-color: #7f8c8d; /* Gray */
            color: white;
            border-radius: 4px;
            margin-left: 10px;
        }
        .btn-cancel:hover {
            background-color: #95a5a6;
        }
        .btn-danger {
            padding: 10px 15px;
            background-color: #e74c3c; /* Red */
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
        }
        .btn-danger:hover {
            background-color: #c0392b;
        }
    </style>
</head>
<body>
<div class="container">
    <?php 
    // We include sidebar and header to keep the page layout consistent
    // We need to require db_conn.php first as header/sidebar might use it
    require '../config/db_conn.php'; 
    include 'includes/sidebar.php'; 
    ?>
    <div class="main">
        <?php include 'includes/header.php'; ?>

        <h2>Log Out</h2>

        <div class="panel">
            <h3>Are you sure you want to log out?</h3>
            <p>You will be returned to the login screen.</p>
            
            <div class="logout-actions">
                <!-- This form submits to itself to confirm the logout -->
                <form method="POST" action="logout.php" style="display: inline;">
                    <button type="submit" name="confirm_logout" class="btn-danger">
                        Yes, Log Out
                    </button>
                </form>

                <!-- Cancel button (assumes index.php is your dashboard) -->
                <a href="index.php" class="btn-cancel">Cancel</a>
            </div>
        </div>

    </div>
</div>
</body>
</html>
