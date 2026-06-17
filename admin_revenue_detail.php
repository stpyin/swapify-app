<?php
session_start();
include "includes/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$revenue_query = "SELECT 
                    t.topup_id, 
                    u.name as user_name, 
                    u.profile_picture,
                    p.name as package_name, 
                    p.price_money, 
                    p.credit_amount,
                    t.payment_method, 
                    t.created_at 
                  FROM tr_credittopup t
                  JOIN ms_users u ON t.user_id = u.user_id
                  JOIN ms_topuppackages p ON t.package_id = p.package_id
                  ORDER BY t.topup_id ASC";
$revenue_result = mysqli_query($conn, $revenue_query);

$summary_query = "SELECT 
                    SUM(p.price_money) as total_rev, 
                    COUNT(t.topup_id) as total_trans 
                  FROM tr_credittopup t 
                  JOIN ms_topuppackages p ON t.package_id = p.package_id";
$summary_res = mysqli_query($conn, $summary_query);
$summary = mysqli_fetch_assoc($summary_res); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revenue Details - Admin Dashboard</title>
    <link rel="stylesheet" href="css/admin-detail.css">
    <link rel="stylesheet" href="css/admin_revenue_detail.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="admin-dashboard.php" class="back-btn">← Back to Dashboard</a>
            <h1>Revenue Analytics</h1>
            <p>Detailed history of all credit top-up transactions</p>
        </div>

        <div class="summary-banner">
            <div class="summary-item">
                <label>Total Confirmed Revenue</label>
                <h2>Rp <?= number_format($summary['total_rev'] ?? 0, 0, ',', '.') ?></h2>
            </div>
            <div class="summary-item">
                <label>Total Transactions</label>
                <h2><?= number_format($summary['total_trans'] ?? 0) ?> Transactions</h2>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>User</th>
                        <th>Package</th>
                        <th>Method</th>
                        <th>Amount (IDR)</th>
                        <th>Credits</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($revenue_result)): ?>
                    <tr>
                        <td><?=$row['topup_id'] ?></td>
                        <td>
                            <div class="user-cell">
                                <?php 
                                $profilePic = !empty($row['profile_picture']) ? "uploads/" . $row['profile_picture'] : "https://ui-avatars.com/api/?name=" . urlencode($row['user_name']) . "&background=FF6B35&color=fff";
                                ?>
                                <img src="<?= $profilePic ?>" alt="" class="user-avatar">
                                <strong><?= htmlspecialchars($row['user_name']) ?></strong>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($row['package_name']) ?></td>
                        <td><span style="text-transform: uppercase; font-size: 12px; font-weight: 600; color: #6B7280;"><?= $row['payment_method'] ?></span></td>
                        <td class="revenue-amount">Rp <?= number_format($row['price_money'], 0, ',', '.') ?></td>
                        <td><span style="font-weight: 600;">🪙 <?= number_format($row['credit_amount']) ?></span></td>
                        <td><?= date('d M Y, H:i', strtotime($row['created_at'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>