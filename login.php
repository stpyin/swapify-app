<?php
session_start();
include "includes/db_connect.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, name, password, role FROM ms_users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            if ($_SESSION['role'] !== 'admin') {
                header("Location: dashboard.php");
                exit();
            }

            header("Location: admin-dashboard.php");
            exit();
        } else {
            $error = "Password doesn't match!";
        }
    } else {
        $error = "Email is not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Swapify</title>
    <link rel="stylesheet" href="css/login.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php include 'includes/header_first.php'; ?>

    <div class="title-section">
        <h1>Welcome Back</h1>
        <p>Login to continue using Swapify</p>
        <div class="title-underline"></div>
    </div>

    <form action="" method="POST">
        <div class="form-container">
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" placeholder="Input email..." required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="Input password..." required>
            </div>

            <div class="btn-submit-container">
                <button type="submit" class="btn-submit">Login</button>
                
                <div class="footer-link">
                    Don’t have an account? <a href="register.php">Register here</a>
                </div>
            </div>

        </div>
    </form>

    <script>
    <?php if(!empty($error)): ?>
        Swal.fire({
            title: 'Login Failed',
            text: '<?php echo addslashes($error); ?>',
            icon: 'error',
            confirmButtonText: 'Try Again',
            confirmButtonColor: '#d33'
        });
    <?php endif; ?>
</script>
</body>
</html>