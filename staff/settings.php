<?php
session_start();
require "../config/db_conn.php";

//allow staff only
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Staff') {
    header("Location: ../login_signup/userlogin.php");
    exit();
}

$staff_id = (int)$_SESSION['user_id'];

$error_message = '';
$success_message = '';

//update profile handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $branch = trim($_POST['branch']);
    $password = $_POST['password'];

    if (empty($username) || empty($email)) {
        $error_message = "Error: Username and email cannot be blank.";
    } else {

        // Handle profile picture upload (safe version)
        $profile_img_name = null;

        if (!empty($_FILES['profile_img']['name'])) {
            $file = $_FILES['profile_img'];

            // Create uploads folder if missing
            $upload_dir = __DIR__ . "/uploads/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Validate file type (only images)
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            $file_type = mime_content_type($file['tmp_name']);
            $file_size = $file['size'];

            if (!in_array($file_type, $allowed_types)) {
                $error_message = "Invalid image type. Please upload JPG, PNG, or GIF.";
            } elseif ($file_size > 2 * 1024 * 1024) { // 2MB limit
                $error_message = "Image too large. Maximum size is 2MB.";
            } elseif ($file['error'] !== UPLOAD_ERR_OK) {
                $error_message = "File upload error. Please try again.";
            } else {
                // Generate a unique name to avoid overwriting
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $profile_img_name = time() . "_" . uniqid() . "." . $ext;
                $target = $upload_dir . $profile_img_name;

                // Move uploaded file successfully
                if (move_uploaded_file($file['tmp_name'], $target)) {
                    // Update database when file upload succeeds
                    $stmt = $conn->prepare("UPDATE USER SET profile_image = ? WHERE user_id = ?");
                    $stmt->bind_param("si", $profile_img_name, $_SESSION['user_id']);
                    $stmt->execute();
                    $stmt->close();

                    // Update session so header updates immediately
                    $_SESSION['profile_image'] = $profile_img_name;
                } else {
                    $error_message = "Failed to move uploaded file.";
                }
            }
        }

        // Proceed only if no upload error
        if (empty($error_message)) {

            // Build SQL query depending on whether password or image is included
            if (!empty($password)) {
                $password_hash = md5($password);

                if ($profile_img_name) {
                    $stmt = $conn->prepare(
                        "UPDATE `user` 
                         SET username = ?, email = ?, branch = ?, password_hash = ?, profile_image = ?
                         WHERE user_id = ?"
                    );
                    $stmt->bind_param("sssssi", $username, $email, $branch, $password_hash, $profile_img_name, $staff_id);
                } else {
                    $stmt = $conn->prepare(
                        "UPDATE `user` 
                         SET username = ?, email = ?, branch = ?, password_hash = ?
                         WHERE user_id = ?"
                    );
                    $stmt->bind_param("ssssi", $username, $email, $branch, $password_hash, $staff_id);
                }

            } else { 
                // No password change
                if ($profile_img_name) {
                    $stmt = $conn->prepare(
                        "UPDATE `user`
                         SET username = ?, email = ?, branch = ?, profile_image = ?
                         WHERE user_id = ?"
                    );
                    $stmt->bind_param("ssssi", $username, $email, $branch, $profile_img_name, $staff_id);
                } else {
                    $stmt = $conn->prepare(
                        "UPDATE `user`
                         SET username = ?, email = ?, branch = ?
                         WHERE user_id = ?"
                    );
                    $stmt->bind_param("sssi", $username, $email, $branch, $staff_id);
                }
            }

            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $success_message = "Profile updated successfully.";

                // update session data
                $_SESSION['username'] = $username;
                if ($profile_img_name) {
                    $_SESSION['profile_image'] = $profile_img_name;
                }

            } else {
                $error_message = "No changes were made.";
            }

            $stmt->close();
        }
    }
}

//fetch user info
$stmt = $conn->prepare("SELECT username, email, branch, profile_image FROM `user` WHERE user_id = ?");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// If somehow user nonexistant
if (!$user) {
    session_destroy();
    header("Location: ../login_signup/userlogin.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Profile Settings</title>
    <link rel="stylesheet" href="../style/dashboard.css">

    <style>
        .profile-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ffa726;
            margin-bottom: 10px;
        }
        .alert {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            font-size: 14px;
        }
        .alert-danger { background: #ffcccc; color: #900; }
        .alert-success { background: #ccffcc; color: #060; }
    </style>
</head>

<body>
    <div class="container">

        <?php include 'includes/sidebar.php'; ?>
        <div class="main">
        <?php include 'includes/header.php'; ?>

        <h2>My Profile Settings</h2>

        <!-- Error message -->
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <!-- Success message -->
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>


        <div class="panel">

            <form method="POST" action="settings.php" enctype="multipart/form-data" class="form-vertical">

                <label>Current Profile Picture:</label><br>
                <img src="uploads/<?php echo $user['profile_image'] ?: 'default.png'; ?>" class="profile-preview">

                <br><br>

                <label>Upload New Profile Picture:</label>
                <label for="profile_img" class="custom-file-upload">
                    Choose File
                </label>
                <input type="file" id="profile_img" name="profile_img" accept="image/*">

                <label>Username:</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                <style>
                input[type="file"] {
                display: none;
                }

                .custom-file-upload {
                display: inline-block;
                padding: 6px 14px;
                cursor: pointer;
                background-color: #ffa726;
                color: #333 !important;
                font-size: 16px;
                border-radius: 8px;
                transition: background-color 0.3s, transform 0.2s;
                width:120px;
                text-align: left;
                white-space: nowrap;
                }

                .custom-file-upload:hover {
                background-color: #ffa726;
                transform: scale(1.05);
                }

                .custom-file-upload:active {
                background-color: #ffa726;
                }
                </style>

                <label>Email:</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>

                <label>Branch:</label>
                <input type="text" name="branch" value="<?php echo htmlspecialchars($user['branch']); ?>">

                <hr style="border:0; border-top:1px solid #333; margin:20px 0;">

                <label>New Password (optional):</label>
                <input type="password" name="password" placeholder="Leave blank to keep current password">

                <button type="submit" name="update_profile" class="btn">Update My Profile</button>

            </form>

        </div>
    </div>
</body>
</html>
