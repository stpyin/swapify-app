<?php 
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    session_start();
    include "includes/header.php";
    include "includes/db_connect.php";

    $user_id = $_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['package_id'])) {
        $package_id = (int)$_POST['package_id'];
        
        $stmt_pkg = $conn->prepare("SELECT name, credit_amount FROM ms_topuppackages WHERE package_id = ?");
        $stmt_pkg->bind_param("i", $package_id);
        $stmt_pkg->execute();
        $pkg_result = $stmt_pkg->get_result();
        $pkg = $pkg_result->fetch_assoc();

        if ($pkg) {
            $added_credits = $pkg['credit_amount'];
            $pkg_name = $pkg['name'];

            $conn->begin_transaction();
            try {
                $ins = $conn->prepare("INSERT INTO tr_credittopup (user_id, package_id, payment_method, confirmed_at) VALUES (?, ?, 'Virtual Account', NOW())");
                $ins->bind_param("ii", $user_id, $package_id);
                $ins->execute();

                $check_bal = $conn->query("SELECT user_id FROM ms_usercredits WHERE user_id = $user_id");
                if ($check_bal->num_rows == 0) {
                    $upd = $conn->prepare("INSERT INTO ms_usercredits (user_id, balance) VALUES (?, ?)");
                    $upd->bind_param("ii", $user_id, $added_credits);
                } else {
                    $upd = $conn->prepare("UPDATE ms_usercredits SET balance = balance + ? WHERE user_id = ?");
                    $upd->bind_param("ii", $added_credits, $user_id);
                }
                $upd->execute();

                $desc = "Top Up: " . $pkg_name;
                $ins_hist = $conn->prepare("INSERT INTO tr_credithistory (user_id, amount, transaction_type, description, created_at) VALUES (?, ?, 'topup', ?, NOW())");
                $ins_hist->bind_param("iis", $user_id, $added_credits, $desc);
                $ins_hist->execute();

                $conn->commit();
                $show_success = true;
                $success_msg = "Top Up Success! " . $added_credits . " credits added.";
            } catch (Exception $e) {
                $conn->rollback();
                $error_msg = "Transaction failed. Please try again. " . $e->getMessage();
            }
        }
    }

    $packages_query = "SELECT * FROM ms_topuppackages ORDER BY price_money ASC";
    $packages_result = mysqli_query($conn, $packages_query);

    $history_query = "SELECT t.*, p.name, p.price_money
                      FROM tr_credittopup t
                      JOIN ms_topuppackages p ON t.package_id = p.package_id
                      WHERE t.user_id = '$user_id'
                      ORDER BY t.created_at DESC";
    $history_result = mysqli_query($conn, $history_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Swapify - Top Up Wallet</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .booster-container { max-width: 1000px; margin: 0 auto; padding: 40px 20px; }
        .header-title { text-align: center; margin-bottom: 50px; }
        .header-title h1 { color: #111827; font-size: 32px; margin-bottom: 12px; font-weight: 800; letter-spacing: -0.5px; }
        .header-title p { color: #6B7280; max-width: 600px; margin: 0 auto; line-height: 1.6; }

        .package-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; margin-bottom: 60px; }
        .package-card { background: white; border-radius: 16px; padding: 32px; border: 1px solid #E5E7EB; text-align: center; transition: all 0.3s; display: flex; flex-direction: column; }
        .package-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.08); border-color: #FF8A50; }
        .package-card h2 { color: #1F2937; margin-bottom: 10px; font-size: 20px; font-weight: 700; }
        
        .credits-display { font-size: 36px; font-weight: 800; color: #FF8A50; margin: 15px 0; }
        .credits-display span { font-size: 14px; color: #6B7280; font-weight: 500; display: block; margin-top: 5px; text-transform: uppercase; letter-spacing: 1px; }
        
        .package-features { list-style: none; padding: 0; margin: 25px 0; color: #4B5563; font-size: 14px; text-align: left; }
        .package-features li { margin-bottom: 10px; display: flex; align-items: center; justify-content: center; gap: 8px; }
        
        .price-tag { display: block; font-size: 24px; font-weight: 700; color: #111827; margin: 20px 0; margin-top: auto; }
        
        .buy-btn { width: 100%; padding: 14px; background: #FF8A50; color: white; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; transition: background 0.2s; font-size: 15px; }
        .buy-btn:hover { background: #E56E30; }

        .history-section { margin-top: 50px; }
        .history-title { 
            font-size: 20px; color: #111827; font-weight: 700; margin-bottom: 20px; 
            display: flex; align-items: center; gap: 10px;
        }
        
        .history-table-container {
            background: white;
            border-radius: 12px;
            padding: 0;
            border: 1px solid #E5E7EB;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        table { width: 100%; border-collapse: collapse; white-space: nowrap; }
        
        thead { background: #F9FAFB; border-bottom: 1px solid #E5E7EB; }
        
        thead th { 
            padding: 20px 24px; 
            text-align: left; 
            font-weight: 600; 
            color: #6B7280; 
            font-size: 11px; 
            text-transform: uppercase; 
            letter-spacing: 0.05em; 
        }

        tbody tr { 
            border-bottom: 1px solid #F3F4F6; 
            transition: background-color 0.2s; 
        }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background-color: #FAFAFA; }

        tbody td { 
            padding: 24px; 
            color: #374151; 
            font-size: 14px; 
            vertical-align: middle;
        }

        .package-name-cell { font-weight: 600; color: #111827; }
        .date-cell { color: #6B7280; }
        .method-cell { color: #4B5563; font-weight: 500; }
        .amount-cell { font-family: monospace; font-weight: 600; color: #111827; }

        .status-badge { 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            padding: 6px 16px; 
            border-radius: 9999px; 
            font-size: 11px; 
            font-weight: 700; 
            letter-spacing: 0.025em; 
            text-transform: uppercase; 
        }
        
        .status-badge.success { 
            background-color: #ECFDF5; 
            color: #047857; 
            border: 1px solid #D1FAE5; 
        }

        .empty-history { text-align: center; padding: 40px !important; color: #9CA3AF; font-style: italic; }

        .popup-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); z-index: 10001; justify-content: center; align-items: center; backdrop-filter: blur(4px); }
        .popup-overlay.active { display: flex; }
        .popup-content { background: white; padding: 32px; border-radius: 20px; width: 90%; max-width: 480px; position: relative; animation: slideUp 0.3s ease-out; box-shadow: 0 20px 60px rgba(0,0,0,0.15); }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .popup-close { position: absolute; top: 20px; right: 20px; background: #F3F4F6; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; color: #6B7280; font-size: 18px; transition: all 0.2s; }
        .popup-close:hover { background: #E5E7EB; color: #111827; }
        
        .popup-header { text-align: center; margin-bottom: 24px; }
        .popup-header h2 { font-size: 22px; font-weight: 700; color: #111827; margin-bottom: 5px; }
        .popup-header p { font-size: 14px; color: #6B7280; }

        .package-info-popup { background: #F9FAFB; padding: 20px; border-radius: 12px; text-align: center; margin-bottom: 24px; border: 1px solid #E5E7EB; }
        .pkg-name { font-weight: 700; color: #111827; font-size: 18px; margin-bottom: 5px; }
        .pkg-price { font-size: 24px; font-weight: 800; color: #FF8A50; }

        .va-container { margin-bottom: 24px; }
        .va-label { font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 8px; display: block; text-transform: uppercase; letter-spacing: 0.5px; }
        .va-box { background: white; border: 2px dashed #D1D5DB; border-radius: 12px; padding: 16px; display: flex; align-items: center; justify-content: space-between; transition: border-color 0.2s; }
        .va-box:hover { border-color: #9CA3AF; }
        .va-number { font-family: 'SF Mono', 'Courier New', monospace; font-size: 20px; font-weight: 700; color: #111827; letter-spacing: 1px; }
        .copy-btn { background: #F3F4F6; border: 1px solid #E5E7EB; padding: 8px 16px; border-radius: 8px; font-size: 13px; cursor: pointer; color: #4B5563; font-weight: 600; transition: all 0.2s; }
        .copy-btn:hover { background: #E5E7EB; color: #111827; }
        
        .instructions { background: #FFF7ED; border-left: 4px solid #FF8A50; border-radius: 8px; padding: 16px; margin-bottom: 24px; font-size: 13px; color: #9A3412; }
        .instructions h4 { margin-bottom: 10px; font-weight: 700; }
        .instructions ol { margin: 0; padding-left: 20px; line-height: 1.6; }
        
        .popup-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .btn-cancel { padding: 14px; border-radius: 12px; border: 1px solid #E5E7EB; background: white; color: #374151; cursor: pointer; font-weight: 600; }
        .btn-cancel:hover { background: #F9FAFB; }
        .btn-confirm { padding: 14px; border-radius: 12px; border: none; background: #FF8A50; color: white; cursor: pointer; font-weight: 600; }
        .btn-confirm:hover { background: #E56E30; }
    </style>
</head>

<body>
<div class="booster-container">

    <div class="header-title">
        <h1>Top Up Wallet</h1>
        <p>Purchase credits instantly via Virtual Account.</p>
    </div>

    <div class="package-grid">
        <?php while($p = mysqli_fetch_assoc($packages_result)): ?>
            <div class="package-card">
                <h2><?= htmlspecialchars($p['name']) ?></h2>
                <div class="credits-display">
                    <?= number_format($p['credit_amount']) ?>
                    <span>Credits</span>
                </div>
               
                <span class="price-tag">Rp <?= number_format($p['price_money'], 0, ',', '.') ?></span>
                <button type="button" class="buy-btn" 
                    onclick="openPopup('<?= htmlspecialchars($p['name']) ?>', <?= $p['credit_amount'] ?>, <?= $p['price_money'] ?>, <?= $p['package_id'] ?>)">
                    Buy Now
                </button>
            </div>
        <?php endwhile; ?>
    </div>

    <div class="history-section">
        <h3 class="history-title">
            Transaction History
        </h3>
        
        <div class="history-table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 25%;">PACKAGE</th>
                        <th style="width: 25%;">DATE</th>
                        <th style="width: 20%;">METHOD</th>
                        <th style="width: 15%;">AMOUNT</th>
                        <th style="width: 15%;">STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($history_result && $history_result->num_rows > 0): ?>
                        <?php while ($row = $history_result->fetch_assoc()): ?>
                            <tr>
                                <td class="package-name-cell"><?= htmlspecialchars($row['name']) ?></td>
                                <td class="date-cell"><?= date('d M Y, H:i', strtotime($row['created_at'])) ?></td>
                                <td class="method-cell"><?= htmlspecialchars($row['payment_method']) ?></td>
                                <td class="amount-cell">Rp <?= number_format($row['price_money'], 0, ',', '.') ?></td>
                                <td><span class="status-badge success">SUCCESS</span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="empty-history">No transaction history found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<div class="popup-overlay" id="paymentPopup">
    <div class="popup-content">
        <button class="popup-close" onclick="closePopup()">×</button>
        
        <div class="popup-header">
            <h2>Complete Payment</h2>
            <p>Transfer to the Virtual Account below</p>
        </div>

        <div class="package-info-popup">
            <div class="pkg-name" id="popupPackageName">Starter Pack</div>
            <div style="font-size:14px; color:#666; margin-top:5px;" id="popupCredits">100 Credits</div>
            <div class="pkg-price" id="popupPrice">Rp 10,000</div>
        </div>

        <div class="va-container">
            <span class="va-label">Virtual Account Number (BCA)</span>
            <div class="va-box">
                <span class="va-number" id="vaNumber">8800 1234 5678</span>
                <button type="button" class="copy-btn" onclick="copyVA()">Copy</button>
            </div>
        </div>

        <div class="instructions">
            <h4>📋 Payment Instructions</h4>
            <ol>
                <li>Copy the Virtual Account number above</li>
                <li>Transfer via ATM / Mobile Banking</li>
                <li>Select transfer to Virtual Account</li>
                <li>Paste the VA number and confirm</li>
            </ol>
        </div>

        <form method="POST">
            <input type="hidden" name="package_id" id="selectedPackageId" value="">
            <div class="popup-actions">
                <button type="button" class="btn-cancel" onclick="closePopup()">Cancel</button>
                <button type="submit" class="btn-confirm">I Have Paid</button>
            </div>
        </form>
    </div>
</div>

<script>
    <?php if (isset($show_success) && $show_success): ?>
        Swal.fire({
            title: 'Success!',
            text: '<?= $success_msg ?>',
            icon: 'success',
            confirmButtonColor: '#FF8A50'
        }).then(() => {
            window.location.href = 'topup.php';
        });
    <?php endif; ?>

    <?php if (isset($error_msg)): ?>
        Swal.fire('Error', '<?= $error_msg ?>', 'error');
    <?php endif; ?>

    function openPopup(packageName, credits, price, packageId) {
        const randomVA = '8801 ' + Math.floor(10000000 + Math.random() * 90000000);
        document.getElementById('popupPackageName').textContent = packageName;
        document.getElementById('popupCredits').textContent = credits.toLocaleString() + ' Credits';
        document.getElementById('popupPrice').textContent = 'Rp ' + price.toLocaleString('id-ID');
        document.getElementById('vaNumber').textContent = randomVA;
        document.getElementById('selectedPackageId').value = packageId;
        document.getElementById('paymentPopup').classList.add('active');
    }

    function closePopup() {
        document.getElementById('paymentPopup').classList.remove('active');
    }

    function copyVA() {
        const va = document.getElementById('vaNumber').textContent;
        navigator.clipboard.writeText(va.replace(/\s/g, '')).then(() => {
            const btn = document.querySelector('.copy-btn');
            const originalText = btn.textContent;
            btn.textContent = 'Copied!';
            setTimeout(() => btn.textContent = originalText, 1500);
        });
    }
</script>

</body>
</html>