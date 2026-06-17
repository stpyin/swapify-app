<?php
session_start();
include "includes/db_connect.php";
include "includes/header.php";

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$q_user = "SELECT name, profile_picture FROM ms_users WHERE user_id = $user_id";
$res_user = mysqli_query($conn, $q_user);
$user_data = mysqli_fetch_assoc($res_user);

$user_img = $user_data['profile_picture']; 

$final_avatar = (!empty($user_img) && file_exists("uploads/" . $user_img)) 
                ? "uploads/" . $user_img 
                : ((!empty($user_img) && file_exists("images/" . $user_img)) 
                    ? "images/" . $user_img
                    : "https://ui-avatars.com/api/?name=" . urlencode($user_data['name']) . "&background=FF6B35&color=fff");

$q_withdraw = "SELECT * FROM tr_withdraw WHERE user_id = $user_id ORDER BY created_at DESC";
$res_withdraw = mysqli_query($conn, $q_withdraw);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Withdraw History</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="css/view-course.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/my-withdraw.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

    <main class="container">
        <div style="margin-top: 30px; margin-bottom: 10px;">
            <a href="dashboard.php" class="back-link">&larr; Back to Dashboard</a>
        </div>

        <div class="page-header-flex">
            <h1 class="page-title">Withdraw History</h1>
            <a href="request-withdraw.php" class="btn-withdraw">
                + Request Withdraw
            </a>
        </div>

        <div class="table-container">
            <table class="history-table">
                <thead>
                    <tr>
                        <th width="15%">Date Requested</th>
                        <th width="10%">Credits</th>
                        <th width="15%">Money Received</th>
                        <th width="15%">Bank / E-Wallet</th> 
                        <th width="20%">Account Number</th> 
                        <th width="10%">Status</th>
                        <th width="15%">Received Date</th>
                    </tr>
                </thead>
                <tbody>
    <?php if (mysqli_num_rows($res_withdraw) > 0): ?>
        <?php while($row = mysqli_fetch_assoc($res_withdraw)): ?>
            <tr>
                <td class="date-primary">
                    <?php echo date('d M Y', strtotime($row['created_at'])); ?>
                    <div class="date-secondary">
                        <?php echo date('H:i', strtotime($row['created_at'])); ?>
                    </div>
                </td>

                <td>
                    <span class="credit-amount">
                        <?php echo number_format($row['credit_amount']); ?>
                    </span>
                </td>

                <td>
                    <span class="money-badge">
                        Rp <?php echo number_format($row['money_received'], 0, ',', '.'); ?>
                    </span>
                </td>

                <td class="bank-name">
                    <?php echo htmlspecialchars($row['bank_name']); ?>
                </td>

                <td class="account-number">
                    <?php echo htmlspecialchars($row['bank_account_number']); ?>
                </td>

                <td>
                    <?php 
                        if ($row['status'] == 'completed') {
                            $statusClass = 'status-completed';
                        } elseif ($row['status'] == 'pending') {
                            $statusClass = 'status-pending';
                        } else {
                            $statusClass = 'status-failed';
                        }
                    ?>
                    <span class="status-badge <?php echo $statusClass; ?>">
                        <span style="font-size: 18px; line-height: 0;">&bull;</span> 
                        <?php echo ucfirst($row['status']); ?>
                    </span>
                </td>

                <td style="color: #6B7280;">
                    <?php 
                        if ($row['status'] == 'completed' && !empty($row['received_at'])) {
                            echo date('d M Y', strtotime($row['received_at']));
                        } else {
                            echo '-';
                        }
                    ?>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="7" style="text-align: center; padding: 80px;">
                <div class="empty-state">
                    <span style="color: #6B7280; font-weight: 500; font-size: 16px;">No withdraw history found.</span>
                </div>
            </td>
        </tr>
    <?php endif; ?>
</tbody>
            </table>
        </div>

    </main>

</body>
</html>