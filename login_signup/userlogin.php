<?php 
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BurgerHub</title>
    
    <link rel="stylesheet" type="text/css" href="../style/login_signup.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    </head>
<body>

    <div class="auth-container">
        
        <div class="auth-header">
            <div class="brand">BurgerHub</div>
            <h1>Login</h1>
        </div>

        <form action="userloginquery.php" method="POST">
            
            <?php // Display Messages
            if (isset($_GET['error'])) {
                echo '<div class="auth-message error">' . htmlspecialchars($_GET['error']) . '</div>';
            }
            if (isset($_SESSION['no-login-message'])) {
                echo '<div class="auth-message error">' . $_SESSION['no-login-message'] . '</div>';
                unset($_SESSION['no-login-message']);
            }
            if (isset($_GET['success'])) {
                echo '<div class="auth-message success">' . htmlspecialchars($_GET['success']) . '</div>';
            }
            ?>

            <div class="form-group">
                <p>Username or Email</p> <input type="text" name="username" placeholder="Username or Email" required>
            </div>

            <div class="form-group password-container">
                <p>Password</p> <input type="password" name="password" placeholder="Password" required>
                <i class="fas fa-eye toggle-password"></i>
            </div>

            <input type="submit" class="btn-submit" value="Log In">

            <div class="auth-link">
            <!--    <a href="usersignup.php">Don't have an account? Sign Up</a> -->
            </div>
        </form>
    </div>

    <script src="../javascript/pass_see.js"></script>

</body>
</html>