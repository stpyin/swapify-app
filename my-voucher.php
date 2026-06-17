<?php
    include "includes/header.php";
    include "includes/db_connect.php";

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $search = "";
    
   
    $query = "SELECT tr.redeem_date, tr.redeem_id, tr.status, v.* FROM tr_redeemvoucher tr
            JOIN ms_voucher v ON tr.voucher_id = v.voucher_id
            WHERE tr.user_id = '$user_id'";

    if (isset($_GET['search'])) {
        $search = mysqli_real_escape_string($conn, $_GET['search']);
        $query .= " AND (v.voucher_name LIKE '%$search%' OR v.partner_name LIKE '%$search%')";
    } 

    $query .= " ORDER BY tr.redeem_date DESC";

    $result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swapify - My Vouchers</title>
    <link rel="stylesheet" href="css/my-voucher.css?v=10">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">My Vouchers</h1>
            <p class="page-subtitle">List of vouchers you have collected</p>
            <div class="title-underline"></div>
        </div>

        <div class="search-section">
            <form action="" method="GET" class="search-bar">
                <span class="search-icon">🔍</span>
                <input type="text" name="search" placeholder="Search my vouchers..." value="<?php echo htmlspecialchars($search); ?>">
            </form>
        </div>

         <div class="voucher-toggle">
            <a href="voucher.php">
                <button>Explore Voucher</button>
            </a>
            <button class="active">My Voucher</button>
        </div>

        <div class="voucher-grid">
        <?php 
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) { 
                    $unique_code = "VCHR-" . str_pad($row['redeem_id'], 5, '0', STR_PAD_LEFT);
            ?>
                <div class="voucher-card">
    
    <div class="card-left">
        <div class="header-row">
             <div class="merchant-badge">
                <?php if(!empty($row['image'])): ?>
                    <img src="images/vouchers/<?php echo $row['image']; ?>" alt="logo">
                <?php else: ?>
                    <span class="initial"><?php echo substr($row['partner_name'], 0, 1); ?></span>
                <?php endif; ?>
                <span class="merchant-name"><?php echo htmlspecialchars($row['partner_name']); ?></span>
            </div>
        </div>

        <h3 class="voucher-name"><?php echo htmlspecialchars($row['voucher_name']); ?></h3>
        
        <div class="footer-row">
            <span class="status-pill status-<?php echo $row['status']; ?>">
                <?php echo ucfirst($row['status']); ?>
            </span>
            <span class="expiry-text">Valid: <?php echo date('d M Y', strtotime($row['end_date'])); ?></span>
        </div>
    </div>

    <div class="ticket-divider">
        <div class="notch-top"></div>
        <div class="dashed-line"></div>
        <div class="notch-bottom"></div>
    </div>

    <?php 
        $status_class = "state-" . $row['status']; 
    ?>
    <div class="card-right <?php echo $status_class; ?>">
        
        <?php if ($row['status'] == 'available'): ?>
            <span class="label-tiny">YOUR CODE</span>
            <div class="code-box"><?php echo $unique_code; ?></div>
            <button class="btn-action" onclick="copyCode(this, '<?php echo $unique_code; ?>')">
                Copy
            </button>
        
        <?php elseif ($row['status'] == 'used'): ?>
            <span class="label-status" style="font-size: 20px;">Redeemed</span>
            <!-- <span class="date-status"><?php echo date('d/m/y', strtotime($row['redeem_date'])); ?></span> -->

        <?php elseif ($row['status'] == 'expired'): ?>
            <div class="icon-big"></div>
            <span class="label-status" style="font-size: 20px;">Expired</span>
        <?php endif; ?>

    </div>
</div>
            <?php 
                }
            } else {
                echo "<div style='grid-column: 1/-1; text-align: center; padding: 50px;'>
                        <p>You haven't redeemed any vouchers yet.</p>
                        <a href='voucher.php' style='color: #FF6B35; text-decoration: underline; text-decoration:none;'>Browse Vouchers</a>
                      </div>";
            }
            ?>
        </div>
    </div>

    <script>
        function copyCode(btnElement, code) {
            navigator.clipboard.writeText(code).then(() => {
                
                const originalText = btnElement.innerText;
                
                btnElement.innerText = "Copied!";
                btnElement.classList.add('copied');
                
                setTimeout(() => {
                    btnElement.innerText = originalText;
                    btnElement.classList.remove('copied');
                }, 2000);

            }).catch(err => {
                console.error('Gagal copy: ', err);
                alert("Failed to copy code.");
            });
        }
    </script>
</body>
</html>