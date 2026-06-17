<?php
    include "includes/header.php";
    include "includes/db_connect.php";

    $search = "";
    if (isset($_GET['search'])) {
        $search = mysqli_real_escape_string($conn, $_GET['search']);
        $query = "SELECT * FROM ms_voucher WHERE voucher_name LIKE '%$search%' OR partner_name LIKE '%$search%' ORDER BY created_at DESC";
    } else {
        $query = "SELECT * FROM ms_voucher ORDER BY created_at DESC";
    }

    $result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swapify - Voucher Available</title>
    <link rel="stylesheet" href="css/voucher.css?v=10">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Voucher Available</h1>
            <p class="page-subtitle">Discover vouchers to redeem</p>
            <div class="title-underline"></div>
        </div>

        <div class="search-section">
            <form action="" method="GET" class="search-bar">
                <span class="search-icon">🔍</span>
                <input type="text" name="search" placeholder="Search for a voucher..." value="<?php echo htmlspecialchars($search); ?>">
            </form>
        </div>

         <div class="voucher-toggle">
            <button class="active">Explore Voucher</button>
            <a href="my-voucher.php">
                <button>My Voucher</button>
            </a>
        </div>

        <div class="voucher-grid">
            <?php 
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) { 
            ?>
                <div class="voucher-card">
                    <div class="voucher-logo">
                        <?php if(!empty($row['image'])): ?>
                            <img src="images/vouchers/<?php echo $row['image']; ?>" alt="logo" style="width:100%; height:100%; border-radius:50%;">
                        <?php else: ?>
                            <?php echo substr($row['partner_name'], 0, 1); ?>
                        <?php endif; ?>
                    </div>

                    <div class="voucher-info">
                        <h3 class="voucher-title"><?php echo htmlspecialchars($row['voucher_name']); ?></h3>
                        <p class="voucher-merchant"><?php echo htmlspecialchars($row['partner_name']); ?></p>
                        <p class="voucher-description"><?php echo htmlspecialchars($row['description']); ?></p>
                        <p class="voucher-expiry">Valid until <?php echo date('d M Y', strtotime($row['end_date'])); ?></p>
                    </div>

                    <div class="voucher-redeem" 
                        onclick="confirmRedeem(<?= $row['voucher_id']; ?>, '<?= addslashes($row['voucher_name']); ?>', <?= $row['credits_price']; ?>)" 
                        style="cursor: pointer;">
                        
                        <div class="voucher-credits">
                            <div class="credit-amount"><?php echo number_format($row['credits_price']); ?></div>
                            <div class="credit-label-small">Credits</div>
                        </div>
                        
                        <div class="stock-info"><?php echo $row['stock']; ?> Left</div>
                        
                        <div class="btn-redeem-text" style="margin-top: 10px; font-weight: bold; text-transform: uppercase; color: white; font-size: 0.8rem;">
                            Redeem Now
                        </div>
                    </div>
                </div>`
            <?php 
                } 
            } else {
                echo "<div style='grid-column: 1/-1; text-align: center; padding: 50px;'>
                        <p>No vouchers found for '".htmlspecialchars($search)."'</p>
                      </div>";
            }
            ?>
        </div>
    </div>

    <script>
        function confirmRedeem(id, name, price) {
            Swal.fire({
                title: 'Redeem Voucher?',
                html: `You will redeem <b>${name}</b> with <br><span style="color: #FF6B35; font-size: 1.5rem; font-weight: bold;">${new Intl.NumberFormat().format(price)} Credits</span>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#FF6B35',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Redeem Now',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'redeem_process.php?id=' + id;
                }
            })
        }

        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');
            const msg = urlParams.get('message');

            if (status === 'success') {
                Swal.fire('Success!', msg || 'Voucher successfuly claimed.', 'success');
            } else if (status === 'error') {
                Swal.fire('Failed!', msg || 'An error occured.', 'error');
            }
        });
    </script>
</body>
</html>