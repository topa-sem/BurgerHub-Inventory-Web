<?php
session_start();
include "../config/db_conn.php";

// Function to sanitize inputs
function validate($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Collect inputs
    $username = validate($_POST['username']);
    $email = validate($_POST['email']);
    $branch = isset($_POST['branch']) ? validate($_POST['branch']) : '';
    $password = $_POST['password'];
    $re_password = $_POST['re_password'];

    // Check for empty required fields
    if (empty($username) || empty($email) || empty($password) || empty($re_password)) {
        header("Location: usersignup.php?error=All fields except branch are required.&username=$username&email=$email");
        exit();
    }

    // Check if passwords match
    if ($password !== $re_password) {
        header("Location: usersignup.php?error=Passwords do not match.&username=$username&email=$email");
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: usersignup.php?error=Invalid email format.&username=$username");
        exit();
    }

    // Check for duplicate username or email
    $check_sql = "SELECT * FROM USER WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        header("Location: usersignup.php?error=Username or Email already exists.&username=$username&email=$email");
        exit();
    }

    // Hash password using MD5 (for demonstration)
    $password_hash = md5($password);

    // Insert user into database
    $insert_sql = "INSERT INTO USER (username, email, password_hash, branch, account_balance)
                   VALUES (?, ?, ?, ?, 5000.00)";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("ssss", $username, $email, $password_hash, $branch);

    if ($stmt->execute()) {
        header("Location: usersignup.php?success=Account created successfully! You can now log in.");
        exit();
    }

} else {
    header("Location: usersignup.php");
    exit();
}
?>
