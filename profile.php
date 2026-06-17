<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!file_exists("includes/db_connect.php")) die("Error: File 'includes/db_connect.php' missing.");
if (!file_exists("includes/header.php")) die("Error: File 'includes/header.php' missing.");

include "includes/db_connect.php";
include "includes/header.php";

$user_id = $_SESSION['user_id']; 

$create_table_sql = "
CREATE TABLE IF NOT EXISTS tr_credithistory (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount INT NOT NULL,
    transaction_type VARCHAR(50) NOT NULL,
    description VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES ms_users(user_id) ON DELETE CASCADE
)";
mysqli_query($conn, $create_table_sql);

$query_credit = mysqli_query($conn, "SELECT balance FROM ms_usercredits WHERE user_id = '$user_id'");
$balance = ($query_credit && mysqli_num_rows($query_credit) > 0) ? mysqli_fetch_assoc($query_credit)['balance'] : 0;

$wallet_query = "SELECT * FROM tr_credithistory WHERE user_id = '$user_id' ORDER BY created_at DESC LIMIT 5";
$wallet_result = mysqli_query($conn, $wallet_query);

$wallet_data = [];
if ($wallet_result) {
    while($row = mysqli_fetch_assoc($wallet_result)) {
        $wallet_data[] = $row;
    }
}

$history_query = "SELECT 
                    cp.created_at AS purchase_date, 
                    c.title AS course_title, 
                    c.credits_price, 
                    u.name AS author_name, 
                    u.profile_picture AS author_photo
                  FROM tr_coursepurchase cp
                  JOIN ms_courses c ON cp.course_id = c.course_id
                  JOIN ms_users u ON c.user_id = u.user_id
                  WHERE cp.user_id = '$user_id'
                  ORDER BY cp.created_at DESC";
$history_result = mysqli_query($conn, $history_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Swapify</title>
    <link rel="stylesheet" href="css/profile.css?v=2"> 
    <style>
        .status-pill.income { background: #dcfce7; color: #166534; }
        .status-pill.expense { background: #fee2e2; color: #991b1b; }
        .amount-pos { color: #166534; font-weight: bold; }
        .amount-neg { color: #991b1b; font-weight: bold; }
        
        .history-table-card table tbody tr td:nth-child(3) {
            text-align: left !important;
            padding-left: 0; 
        }
        
        .history-table-card table thead tr th:nth-child(3) {
            text-align: left !important;
        }

        .history-table-card table tbody tr td:nth-child(5) {
            text-align: left !important;
            padding-left: 0;
        }
        .history-table-card table thead tr th:nth-child(5) {
            text-align: left !important;
        }

        .status-pill {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="container">
    <header class="dashboard-header">
        <div class="welcome">
            <h1>Welcome Back, <?= $_SESSION['name']; ?>!</h1>
            <p>Here is your latest wallet activity and course purchases.</p>
        </div>
        <div class="credit-badge-profile">
            <a href="topup.php">
                <button class="add-credit">Add Credit +</button>
            </a>
            <span class="amount" style="color:white;"><?= number_format($balance); ?></span>
        </div>
    </header>

    <section class="pending-section">
        <h2 class="section-title">Wallet History</h2>
        <div class="history-table-card">
            <table style="width:100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="text-align:left; padding:15px;">Date</th>
                        <th style="text-align:left; padding:15px;">Description</th>
                        <th style="text-align:left; padding:15px;">Type</th>
                        <th style="text-align:right; padding:15px;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($wallet_data)): ?>
                        <?php foreach($wallet_data as $w): ?>
                            <?php 
                                $is_in = ($w['transaction_type'] == 'earning' || $w['transaction_type'] == 'topup');
                                $cls_amt = $is_in ? 'amount-pos' : 'amount-neg';
                                $sign = $is_in ? '+' : '-';
                                $pill = $is_in ? 'income' : 'expense';
                            ?>
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding:15px;"><?= date('d M Y, H:i', strtotime($w['created_at'])) ?></td>
                            <td style="padding:15px;"><?= htmlspecialchars($w['description']) ?></td>
                            <td style="padding:15px;">
                                <span class="status-pill <?= $pill ?>" style="text-transform:capitalize;">
                                    <?= $w['transaction_type'] ?>
                                </span>
                            </td>
                            <td style="padding:15px; text-align:right;" class="<?= $cls_amt ?>">
                                <?= $sign . number_format($w['amount']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align:center; padding: 30px; color:#888;">
                                No transaction history found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="history-section">
        <h2 class="section-title">Course Purchase History</h2>
        <div class="history-table-card">
            <table style="width:100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="text-align:left; padding:15px;">Author (Partner)</th>
                        <th style="text-align:left; padding:15px;">Course Title</th>
                        <th style="text-align:left; padding:15px;">Date</th>
                        <th style="text-align:right; padding:15px;">Credits</th>
                        <th style="text-align:left; padding:15px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($history_result && mysqli_num_rows($history_result) > 0): ?>
                        <?php while($h = mysqli_fetch_assoc($history_result)): ?>
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding:15px;">
                                <div class="partner-cell">
                                    <img src="uploads/<?= !empty($h['author_photo']) ? $h['author_photo'] : 'default.png' ?>" class="mini-avatar" onerror="this.src='assets/default-avatar.png'">
                                    <?= htmlspecialchars($h['author_name']) ?>
                                </div>
                            </td>
                            <td style="padding:15px;"><?= htmlspecialchars($h['course_title']) ?></td>
                            <td style="padding:15px;"><?= date('M d, Y', strtotime($h['purchase_date'])) ?></td>
                            <td style="padding:15px; text-align:right;" class="negative">-<?= number_format($h['credits_price']) ?></td> 
                            <td style="padding:15px;">
                                <span class="status-pill" style="background:#e0f2fe; color:#0369a1;">COMPLETED</span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding: 30px; color:#888;">No purchase history found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    
    <div class="user-section">
        <a href="index.php" class="nav-link" style="color: #ef8644ff; font-weight: 600; margin-left: auto; margin-right: 50px; margin-bottom: 50px; margin-top:0;">Logout</a>
    </div>
</div>
</body>
</html>