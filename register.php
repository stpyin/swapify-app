<?php
session_start();
include "includes/db_connect.php";

$error = "";
$success = "";
$form_data = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $bio = mysqli_real_escape_string($conn, $_POST['bio']);

    $form_data = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'bio' => $bio
    ];

    if ($password !== $confirm_password) {
        $error = "Password and Password Confirmation didn't match!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long!";
    } else {
        $check_email = $conn->prepare("SELECT email FROM ms_users WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $result = $check_email->get_result();

        if ($result->num_rows > 0) {
            $error = "Email already registered! Please use a different email.";
        } else {
            $profile_pic_name = "default.png";
            if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
                $target_dir = "uploads/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                $file_extension = pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION);
                $profile_pic_name = time() . "_" . preg_replace("/[^a-zA-Z0-9]/", "", $name) . "." . $file_extension;
                $target_file = $target_dir . $profile_pic_name;
                move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file);
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO ms_users (name, email, phone_number, password, profile_picture, bio) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $name, $email, $phone, $hashed_password, $profile_pic_name, $bio);

            if ($stmt->execute()) {
                $new_user_id = $conn->insert_id;
                $stmt_credit = $conn->prepare("INSERT INTO ms_usercredits (user_id, balance, update_at) VALUES (?, 0, NOW())");
                $stmt_credit->bind_param("i", $new_user_id);
                $stmt_credit->execute();

                $success = "Registration success! Please login.";
                $form_data = [];
            } else {
                $error = "An error occurred while creating your account. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Swapify</title>
    <link rel="stylesheet" href="css/register.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

    <?php include 'includes/header_first.php'; ?>

    <div class="title-section">
        <h1>Register Now</h1>
        <p>Register to start using Swapify</p>
        <div class="title-underline"></div>
    </div>

    <form action="" method="POST" enctype="multipart/form-data">
        <div class="form-container">
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" class="form-control" placeholder="Enter name..." 
                       value="<?= htmlspecialchars($form_data['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" placeholder="Enter email..." 
                       value="<?= htmlspecialchars($form_data['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone" class="form-control" placeholder="Enter phone number..." 
                       value="<?= htmlspecialchars($form_data['phone'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter password..." minlength="6" required>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Confirm password..." required>
            </div>
            <div class="form-group">
                <label>Profile Picture</label>
                <div class="file-upload-wrapper">
                    <span class="upload-text">Upload a file</span>
                    <span class="upload-icon">&#8679;</span> 
                    <input type="file" name="profile_pic" accept="image/*">
                </div>
            </div>
            <div class="form-group">
                <label>Bio</label>
                <textarea name="bio" class="form-control" rows="3" placeholder="Enter bio..."><?= htmlspecialchars($form_data['bio'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="btn-submit-container">
            <button type="submit" class="btn-submit">Register</button>
            <div class="footer-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </div>
    </form>

    <script>
        <?php if(!empty($success)): ?>
            Swal.fire({
                title: 'Success!',
                text: <?php echo json_encode($success); ?>,
                icon: 'success',
                confirmButtonText: 'To Login Page',
                confirmButtonColor: '#4A90E2'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'login.php';
                }
            });
        <?php endif; ?>

        <?php if(!empty($error)): ?>
            Swal.fire({
                title: 'Registration Failed',
                html: '<p style="font-size: 16px; color: #666;">' + <?php echo json_encode($error); ?> + '</p>',
                icon: 'error',
                confirmButtonText: 'Try Again',
                confirmButtonColor: '#FF6B35',
                iconColor: '#EF4444'
            });
        <?php endif; ?>
    </script>
</body>
</html>