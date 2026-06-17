<?php
session_start();
include "includes/header.php";
include "includes/db_connect.php";

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['package_id'])) {
    $package_id = (int)$_POST['package_id'];
    
    $stmt_chk = $conn->prepare("SELECT * FROM ms_boosters WHERE package_id = ?");
    $stmt_chk->bind_param("i", $package_id);
    $stmt_chk->execute();
    $pkg = $stmt_chk->get_result()->fetch_assoc();

    if ($pkg) {
        $conn->begin_transaction();
        try {
            $ins = $conn->prepare("INSERT INTO tr_userbooster (user_id, booster_id, purchased_at) VALUES (?, ?, NOW())");
            $ins->bind_param("ii", $user_id, $package_id);
            $ins->execute();

            $conn->commit();
            
            $success_msg = "Booster " . htmlspecialchars($pkg['package_name']) . " Activated!";
            $show_success = true;
        } catch (Exception $e) {
            $conn->rollback();
            $error_msg = "Activation failed. Please try again.";
        }
    }
}

$active_query = "SELECT p.package_name, p.multiplier, ub.purchased_at 
                 FROM tr_userbooster ub 
                 JOIN ms_boosters p ON ub.booster_id = p.package_id 
                 WHERE ub.user_id = ? 
                 ORDER BY ub.purchased_at DESC LIMIT 1";

$stmt = $conn->prepare($active_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$current_active = $stmt->get_result()->fetch_assoc();


$packages_query = "SELECT * FROM ms_boosters WHERE status = 'active' ORDER BY package_price ASC";
$packages_result = mysqli_query($conn, $packages_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swapify - Booster</title>
    <link rel="stylesheet" href="css/booster.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .popup-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); display: none; align-items: center; justify-content: center; z-index: 10001; backdrop-filter: blur(4px); }
        .popup-overlay.active { display: flex; }
        .popup-content { background: white; padding: 30px; border-radius: 20px; width: 90%; max-width: 450px; position: relative; animation: slideUp 0.3s ease-out; }
        .popup-header p { margin-bottom: 10px; }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .popup-close { position: absolute; top: 15px; right: 20px; background: none; border: none; font-size: 24px; cursor: pointer; color: #6B7280; }
        .package-info { background: #F9FAFB; padding: 20px; border-radius: 12px; text-align: center; margin-bottom: 20px; border: 1px solid #E5E7EB; }
        .package-name { font-weight: 700; color: #FF6B35; font-size: 18px; }
        .package-price { font-size: 24px; font-weight: 800; color: #1F2937; margin-top: 5px; }
        .popup-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 20px; }
        .btn-cancel { padding: 12px; border-radius: 10px; border: 1px solid #E5E7EB; background: white; color: #4B5563; cursor: pointer; font-weight: 600; }
        .btn-confirm { padding: 12px; border-radius: 10px; border: none; background: #FF6B35; color: white; cursor: pointer; font-weight: 600; }
        
        .va-container { margin-bottom: 20px; text-align: left; }
        .va-label { font-size: 12px; font-weight: 600; color: #6B7280; margin-bottom: 5px; display: block; }
        .va-box { 
            background: #F3F4F6; border: 2px dashed #D1D5DB; border-radius: 12px; 
            padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; 
        }
        .va-number { font-family: monospace; font-size: 18px; font-weight: 700; color: #1F2937; letter-spacing: 1px; }
        .copy-btn { 
            background: white; border: 1px solid #D1D5DB; padding: 4px 10px; 
            border-radius: 6px; font-size: 12px; cursor: pointer; color: #4B5563; font-weight: 600;
        }
        .copy-btn:hover { background: #E5E7EB; }
    </style>
</head>

<body>
    <div class="container">
        <div class="page-header">
            <h1>Booster</h1>
            <p>Accelerate your progress and unlock exclusive benefits with our Booster.</p>
            <div class="underline"></div>
        </div>

        <h3 class="section-title">Current Active Booster</h3>
        <div class="active-booster">
            <div class="booster-left">
                <div class="booster-icon">
                    <?= $current_active ? "⚡" : "" ?>
                </div>
                <div>
                    <?php if ($current_active): ?>
                        <h4 style="color: #FF6B35"><?= htmlspecialchars($current_active['package_name']) ?> Active</h4>
                        <p>Status: Running (<?= $current_active['multiplier'] ?>x Multiplier)</p>
                    <?php else: ?>
                        <h4>No Active Booster</h4>
                        <p>Purchase a booster to start earning more!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <h3 class="section-title">Choose Your Booster</h3>
        <div class="booster-grid">
            <?php 
            $count = 0;
            while($pkg = mysqli_fetch_assoc($packages_result)): 
                $count++;
                $highlightClass = ($count == 2) ? 'highlight' : '';
                
                $benefitText = ($pkg['multiplier'] <= 1.5) ? 'Standard Priority' : (($pkg['multiplier'] <= 2.0) ? 'High Priority' : 'Top Priority');
                
                $imgUrl = !empty($pkg['image_url']) ? $pkg['image_url'] : "https://cdn-icons-png.flaticon.com/512/1077/1077035.png";
            ?>
                <div class="booster-card <?= $highlightClass ?>">
                    <div class="booster-img">
                        <img src="<?= htmlspecialchars($imgUrl) ?>" alt="icon">
                    </div>
                    <h3><?= htmlspecialchars($pkg['package_name']) ?></h3>
                    <ul>
                        <li><?= $pkg['multiplier'] ?>x credits</li>
                        <li><?= $benefitText ?></li>
                    </ul>
                    <div class="price">
                        <span class="amount">IDR <?= number_format($pkg['package_price']) ?></span> 
                        <span class="unit">/month</span>
                    </div>
                    <button class="activate-btn" 
                        onclick="openPopup('<?= htmlspecialchars($pkg['package_name']) ?>', '<?= $benefitText ?>', <?= $pkg['package_price'] ?>, <?= $pkg['package_id'] ?>)">
                        Activate Now
                    </button>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="popup-overlay" id="paymentPopup">
        <div class="popup-content">
            <button class="popup-close" onclick="closePopup()">×</button>
            <div class="popup-header">
                <h2>Activate Booster</h2>
                <p>Please confirm your booster activation</p>
            </div>
            <div class="package-info">
                <div class="package-name" id="popupPackageName">-</div>
                <div style="font-size: 13px; color: #666; margin-top: 5px;" id="popupBenefit">-</div>
                <div class="package-price" id="popupPrice">0 Credits</div>
            </div>

            <div class="va-container">
                <span class="va-label">Virtual Account Number (BCA)</span>
                <div class="va-box">
                    <span class="va-number" id="vaNumber">8800 1234 5678</span>
                    <button type="button" class="copy-btn" onclick="copyVA()">Copy</button>
                </div>
            </div>

            <div class="instructions" style="font-size: 13px; color: #6B7280; margin-bottom: 20px;">
                <p>⚠️ Complete the payment within <strong>15 minutes</strong>. Your booster will be activated automatically after payment.</p>
            </div>

            <form id="boosterForm" method="POST" action="">
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
                confirmButtonColor: '#FF6B35'
            }).then(() => {
                window.location.href = 'booster.php';
            });
        <?php endif; ?>

        <?php if (isset($error_msg)): ?>
            Swal.fire('Error', '<?= $error_msg ?>', 'error');
        <?php endif; ?>

        function openPopup(name, benefit, price, id) {
            const randomVA = "8800 " + Math.floor(10000000 + Math.random() * 90000000);

            document.getElementById('popupPackageName').textContent = name;
            document.getElementById('popupBenefit').textContent = benefit;
            document.getElementById('popupPrice').textContent = 'IDR ' + new Intl.NumberFormat('id-ID').format(price);
            document.getElementById('vaNumber').textContent = randomVA;
            document.getElementById('selectedPackageId').value = id;
            document.getElementById('paymentPopup').classList.add('active');
        }
        
        function closePopup() { document.getElementById('paymentPopup').classList.remove('active'); }

        function copyVA() {
            const va = document.getElementById('vaNumber').textContent;
            navigator.clipboard.writeText(va.replace(/\s/g, '')); 
            
            const btn = document.querySelector('.copy-btn');
            const originalText = btn.textContent;
            btn.textContent = "Copied!";
            setTimeout(() => { btn.textContent = originalText; }, 1500);
        }

        document.getElementById('boosterForm').onsubmit = function(e) {
            closePopup();
            Swal.fire({
                title: 'Checking Payment...',
                text: 'Please wait a moment',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => { Swal.showLoading(); }
            });
            return true;
        };
    </script>
</body>
</html>