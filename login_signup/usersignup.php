<?php 
session_start();
// include('../admin/config/constant.php'); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - BurgerHub</title>
    
    <link rel="stylesheet" type="text/css" href="../style/login_signup.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

    <div class="auth-container">
        <div class="auth-header">
            <div class="brand">BurgerHub</div>
            <h1>Create Account</h1>
        </div>

        <form action="usersignupquery.php" method="POST">

            <?php // Display Messages
            if (isset($_GET['error'])) {
                echo '<div class="auth-message error">' . htmlspecialchars($_GET['error']) . '</div>';
            }
            if (isset($_GET['success'])) {
                echo '<div class="auth-message success">' . htmlspecialchars($_GET['success']) . '</div>';
            }
            ?>

            <div class="form-group">
                <p>Username</p> <input type="text" name="username" placeholder="Username" 
                       value="<?php echo isset($_GET['username']) ? htmlspecialchars($_GET['username']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <p>Email</p> <input type="email" name="email" placeholder="Email"
                       value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <p>User type</p>
                <select name="user_type" required>
                    <option value="">-- Select your Role --</option>
                    <option value="Admin">Admin</option>
                    <option value="Manager">Manager</option>
                    <option value="Staff">Staff</option>
                </select>
            </div>

            <div class="form-group">
                <p>Branch</p> <input type="text" name="branch" placeholder="Branch"
                       value="<?php echo isset($_GET['branch']) ? htmlspecialchars($_GET['branch']) : ''; ?>" required>
            </div>

            <div class="form-group password-container">
                <p>Password</p> <input type="password" name="password" placeholder="Password" required>
                <i class="fas fa-eye toggle-password"></i>
            </div>

            <div class="form-group password-container">
                <p>Re-enter Password</p> <input type="password" name="re_password" placeholder="Re-enter Password" required>
                <i class="fas fa-eye toggle-password"></i>
            </div>

            <input type="submit" class="btn-submit" value="Sign Up">

            <div class="auth-link">
                <a href="userlogin.php">Already have an account? Log In</a>
            </div>
        </form>
    </div>

    <script src="../javascript/pass_see.js"></script>

</body>
</html>