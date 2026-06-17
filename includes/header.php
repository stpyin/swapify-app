<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "includes/db_connect.php";

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $header_query = "SELECT u.name, u.profile_picture, u.role, IFNULL(c.balance, 0) as balance 
                     FROM ms_users u 
                     LEFT JOIN ms_usercredits c ON u.user_id = c.user_id 
                     WHERE u.user_id = ?";
    $header_stmt = $conn->prepare($header_query);
    $header_stmt->bind_param("i", $user_id);
    $header_stmt->execute();
    $header_result = $header_stmt->get_result();
    $userData = $header_result->fetch_assoc();
} else {
    $userData = ['name' => 'Guest', 'balance' => 0, 'profile_picture' => 'default.png', 'role' => 'user'];
}

$currentPage = basename($_SERVER['PHP_SELF']);
$isAdmin = (isset($userData['role']) && $userData['role'] === 'admin');
$isExpert = (isset($userData['role']) && $userData['role'] === 'expert'); // Cek apakah Expert
?>

<link rel="stylesheet" href="css/header.css?v=10">

<header class="header-nav">
    <div class="logo-container">
        <div class="logo-icon">
            <img src="images/v91_89.png" alt="Swapify Logo">
        </div>
        <span class="logo-text">SWAPIFY</span>
    </div>
    
    <nav class="nav-menu">
        <?php if ($isAdmin): ?>
            <a href="admin-dashboard.php"    class="nav-link admin-link <?= $currentPage == 'admin-dashboard.php' ? 'active' : '' ?>">Admin Dashboard</a>
            <a href="manage_user.php"       class="nav-link admin-link <?= $currentPage == 'manage_user.php' ? 'active' : '' ?>">Manage User</a>
            <a href="manage_vouchers.php"   class="nav-link admin-link <?= $currentPage == 'manage_vouchers.php' ? 'active' : '' ?>">Manage Voucher</a>
            <a href="admin_booster.php"   class="nav-link admin-link <?= $currentPage == 'admin_booster.php' ? 'active' : '' ?>">Manage Booster</a>
            <a href="admin_topup_packages.php"   class="nav-link admin-link <?= $currentPage == 'admin_topup_packages.php' ? 'active' : '' ?>">Manage Topup</a>

        <?php else: ?>
            <a href="dashboard.php" class="nav-link <?= $currentPage == 'dashboard.php' ? 'active' : '' ?>">Skill Swap</a>
            <a href="voucher.php"   class="nav-link <?= ($currentPage == 'voucher.php' || $currentPage == 'my-voucher.php') ? 'active' : '' ?>">Voucher</a>
            <a href="courses.php"   class="nav-link <?= ($currentPage == 'courses.php' || $currentPage == 'my-courses.php' || $currentPage == 'uploaded-courses.php') ? 'active' : '' ?>">Course</a>
            <a href="sessions.php"  class="nav-link <?= $currentPage == 'sessions.php' ? 'active' : '' ?>">My Swap</a>
            <a href="booster.php"   class="nav-link <?= $currentPage == 'booster.php' ? 'active' : '' ?>">Booster</a>
            
            <?php if ($isExpert): ?>
                <a href="my-withdraw.php" class="nav-link <?= $currentPage == 'my-withdraw.php' ? 'active' : '' ?>">Withdraw</a>
            <?php endif; ?>
        <?php endif; ?>
    </nav>

    <?php if (!$isAdmin): ?>
        <div class="user-section">
            <span class="user-name"><?= htmlspecialchars($userData['name']) ?></span>

            <a href="topup.php" style="text-decoration: none; display: flex; align-items: center;">
                <div class="credit-badge">
                    <div class="credit-icon">+</div>
                    <span class="credit-value"><?= number_format($userData['balance']) ?></span>
                </div>
            </a>

            <div class="coin-icon">C</div>

            <div class="user-avatar">
                <a href="profile.php" style="text-decoration: none;">
                    <?php 
                    $has_profile_img = !empty($userData['profile_picture']) && file_exists("uploads/" . $userData['profile_picture']);
                    
                    if ($has_profile_img): ?>
                        <img src="uploads/<?= $userData['profile_picture'] ?>" alt="User Avatar">
                    <?php else: ?>
                        <div class="avatar-gradient">
                            <?= strtoupper(substr($userData['name'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="user-section">
            <a href="index.php" class="nav-link" style="color: #ef4444;">Logout</a>
        </div>
    <?php endif; ?>
</header>