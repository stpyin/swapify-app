<?php
session_start();
include "includes/db_connect.php"; 

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

$q_credit = "SELECT balance FROM ms_usercredits WHERE user_id = $user_id";
$res_credit = mysqli_query($conn, $q_credit);
$data_credit = mysqli_fetch_assoc($res_credit);
$current_balance = $data_credit ? $data_credit['balance'] : 0; 

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $amount = (int) $_POST['amount'];
    $bank_name = mysqli_real_escape_string($conn, $_POST['bank_name']);
    $acc_number = mysqli_real_escape_string($conn, $_POST['account_number']);
    $acc_name = mysqli_real_escape_string($conn, $_POST['account_name']);
    
    if ($amount < 10000) {
        $message = "<div class='alert alert-error'>Minimum withdrawal is 10,000 Credits.</div>";
    } elseif ($amount > $current_balance) {
        $message = "<div class='alert alert-error'>Insufficient balance.</div>";
    } elseif (empty($bank_name) || empty($acc_number) || empty($acc_name)) {
        $message = "<div class='alert alert-error'>All fields are required.</div>";
    } else {
        
        $money_received = $amount; 
        $status = 'completed';
        
        $created_at = date('Y-m-d H:i:s');
        $received_at = date('Y-m-d H:i:s'); 

        $sql_insert = "INSERT INTO tr_withdraw (
                            user_id, 
                            credit_amount, 
                            money_received, 
                            bank_account_number, 
                            bank_name, 
                            account_holder_name, 
                            status, 
                            created_at, 
                            received_at
                       ) VALUES (
                            '$user_id', 
                            '$amount', 
                            '$money_received', 
                            '$acc_number', 
                            '$bank_name', 
                            '$acc_name', 
                            '$status', 
                            '$created_at', 
                            '$received_at'
                       )";

        if (mysqli_query($conn, $sql_insert)) {
            
            $sql_update_saldo = "UPDATE ms_usercredits 
                                 SET balance = balance - $amount 
                                 WHERE user_id = $user_id";
                                 
            mysqli_query($conn, $sql_update_saldo);

            header("Location: my-withdraw.php"); 
            exit();

        } else {
            $message = "<div class='alert alert-error'>Error: " . mysqli_error($conn) . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request Withdraw - Swapify</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="css/view-course.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            background-color: #F5F1ED;
            font-family: 'Inter', sans-serif;
        }

        .layout-wrapper {
            max-width: 720px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            color: #6B7280;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 16px;
            transition: color 0.2s;
        }
        .back-link:hover { color: #111827; }

        .page-title {
            font-size: 28px;
            font-weight: 800;
            color: #111827;
            margin-top: 0;
            margin-bottom: 24px;
            letter-spacing: -0.5px;
        }

        .form-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.08);
            border: 1px solid #f0f0f0;
        }

        .balance-box {
            background-color: #FFF7ED; 
            border: 1px solid #FFEDD5;
            border-radius: 12px;
            padding: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 32px;
        }
        
        .balance-label {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #9A3412;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .balance-sub { font-size: 14px; color: #C2410C; }

        .balance-amount {
            font-size: 32px;
            font-weight: 800;
            color: #EA580C;
        }

        .form-section-title {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #E5E7EB;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }

        .form-group { display: flex; flex-direction: column; }
        .full-width { grid-column: span 2; }

        .form-label {
            font-weight: 600;
            font-size: 14px;
            color: #374151;
            margin-bottom: 8px;
        }

        .form-input {
            background-color: #F9FAFB;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 15px;
            color: #111827;
            outline: none;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
        }

        .form-input:focus {
            border-color: #FF6B35;
            background-color: #fff;
            box-shadow: 0 0 0 4px rgba(255, 107, 53, 0.1);
        }

        .helper-text { margin-top: 8px; font-size: 13px; color: #6B7280; }

        .btn-save {
            background-color: #FF6B35;
            color: white;
            font-weight: 600;
            font-size: 16px;
            padding: 16px;
            border-radius: 50px;
            border: none;
            width: 100%;
            cursor: pointer;
            transition: transform 0.1s, background-color 0.2s;
            margin-top: 10px;
            box-shadow: 0 4px 6px rgba(255, 107, 53, 0.2);
        }

        .btn-save:hover {
            background-color: #E85D2A;
            transform: translateY(-1px);
        }

        .btn-save:active { transform: translateY(0); }

        .alert {
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 500;
        }
        .alert-error {
            background-color: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }

        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
            .balance-box { flex-direction: column; align-items: flex-start; gap: 10px; }
        }
    </style>
</head>
<body>

    <header class="header-minimal">
        <div class="logo-container">
            <div class="logo-icon">
                <img src="images/v91_89.png" alt="Swapify Logo">
            </div>
            <span class="logo-text">SWAPIFY</span>
        </div>
        
        <div class="header-right">
            <div class="user-profile">
                <span class="user-name"><?php echo htmlspecialchars($user_data['name']); ?></span>
                <div class="avatar-container">
                    <img src="<?php echo $final_avatar; ?>" alt="Profile" class="header-avatar">
                </div>
            </div>
        </div>
    </header>

    <div class="layout-wrapper">
        <a href="my-withdraw.php" class="back-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><path d="M19 12H5"></path><path d="M12 19l-7-7 7-7"></path></svg>
            Back to History
        </a>

        <h1 class="page-title">Request Withdraw</h1>

        <div class="form-card">
            
            <?php echo $message; ?>

            <div class="balance-box">
                <div>
                    <div class="balance-label">Current Balance</div>
                    <div class="balance-sub">1 Credit = 1 Rupiah</div>
                </div>
                <div class="balance-amount">
                    <?php echo number_format($current_balance); ?> Credits
                </div>
            </div>

            <form method="POST" action="">
                <div class="form-section-title">Withdrawal Details</div>
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label class="form-label">Amount to Withdraw (Credits)</label>
                        <input type="number" name="amount" id="amount" 
                               class="form-input" 
                               placeholder="Min. 10,000" 
                               min="10000" 
                               max="<?php echo $current_balance; ?>" 
                               required 
                               oninput="calculateRupiah()">
                        <div class="helper-text">
                            You will receive: <span id="rupiah-preview" style="font-weight: 700; color: #111827;">Rp 0</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Bank Name / E-Wallet</label>
                        <input type="text" name="bank_name" class="form-input" placeholder="e.g. BCA, GoPay" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Account Number</label>
                        <input type="number" name="account_number" class="form-input" placeholder="e.g. 1234567890" required>
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Account Holder Name</label>
                        <input type="text" name="account_name" class="form-input" placeholder="Name as shown in bank book" required>
                    </div>
                </div>

                <button type="submit" class="btn-save">Submit & Auto-Approve</button>
            </form>

        </div>
    </div>

    <script>
        function calculateRupiah() {
            const amountInput = document.getElementById('amount').value;
            const preview = document.getElementById('rupiah-preview');
            
            let amount = parseInt(amountInput);
            if (isNaN(amount)) amount = 0;

            const formatted = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(amount);

            preview.innerText = formatted;
        }
    </script>

</body>
</html>