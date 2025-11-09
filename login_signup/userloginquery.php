<?php
session_start();
include "../config/db_conn.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Collect input
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    // Validate input
    if (empty($username) || empty($password)) {
        header("Location: userlogin.php?error=Please enter both username and password.");
        exit();
    }

    // Prepare SQL (user can login using either username or email)
    $sql = "SELECT * FROM `USER` WHERE `username` = ? OR `email` = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        header("Location: userlogin.php?error=Database error: unable to prepare statement.");
        exit();
    }

    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // If user exists
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // Hashing the password
        if (md5($password) === $row["password_hash"]) {
            // Set session variables
            $_SESSION["user_id"] = $row["user_id"];
            $_SESSION["username"] = $row["username"];
            $_SESSION["user_type"] = $row["user_type"];
            $_SESSION["branch"] = $row["branch"];
            $_SESSION["account_balance"] = $row["account_balance"];

            // Redirect based on role
            if ($row["user_type"] === "Admin") {
                header("Location: ../admin/index.php");
                exit();
            } elseif ($row["user_type"] === "Manager") {
                header("Location: ../manager/index.php");
                exit();
            } else {
                header("Location: ../index/index.html");
                exit();
            }

        } else {
            header("Location: userlogin.php?error=Incorrect password.");
            exit();
        }

    } else {
        header("Location: userlogin.php?error=User not found.");
        exit();
    }

} else {
    header("Location: userlogin.php");
    exit();
}
?>
