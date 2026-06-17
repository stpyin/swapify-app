<?php
session_start();
include "includes/header.php";
include "includes/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

$packages_query = "SELECT * FROM ms_topuppackages ORDER BY price_money ASC";
$packages_result = mysqli_query($conn, $packages_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swapify - Admin Top-Up Management</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="css/admin_topup_packages.css?v=3">
</head>

<body>
    <div class="container">
        <div class="page-header">
            <h1>Top-Up Package Management</h1>
            <p>Manage credit packages and monitor sales performance</p>
            <div class="underline"></div>
        </div>

        <div class="action-bar">
            <h2>All Packages</h2>
            <button class="btn-add" onclick="openAddPopup()">+ Add New Package</button>
        </div>

        <?php if (mysqli_num_rows($packages_result) > 0): ?>
            <div class="packages-grid">
                <?php while($pkg = mysqli_fetch_assoc($packages_result)):
                    $usage_query = "SELECT COUNT(*) as usage_count FROM tr_credittopup WHERE package_id = " . $pkg['package_id'];
                    $usage_result = mysqli_query($conn, $usage_query);
                    $usage = mysqli_fetch_assoc($usage_result);
                ?>
                    <div class="package-card">
                        <h3><?= htmlspecialchars($pkg['name']) ?></h3>
                        <div class="package-credits"><?= number_format($pkg['credit_amount']) ?> <span style="font-size: 18px; color: #6B7280; opacity: 0,5;">Credits</span></div>
                        <div class="package-price">Rp <?= number_format($pkg['price_money'], 0, ',', '.') ?></div>
                        <div class="package-meta">
                            <div style="margin-bottom: 4px;">💳 <strong><?= $usage['usage_count'] ?></strong> purchases</div>
                            <div>📅 Created: <strong><?= date('d M Y', strtotime($pkg['created_at'])) ?></strong></div>
                        </div>
                        <div class="action-btns">
                            <button class="btn-edit" onclick='openEditPopup(<?= json_encode($pkg) ?>)'>Edit</button>
                            <button class="btn-delete" onclick="deletePackage(<?= $pkg['package_id'] ?>, '<?= htmlspecialchars($pkg['name']) ?>')">Delete</button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <img src="https://cdn-icons-png.flaticon.com/512/2748/2748558.png" alt="empty">
                <h3>No Packages Yet</h3>
                <p>Create your first top-up package to get started</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="popup-overlay" id="packagePopup">
        <div class="popup-content">
            <button class="popup-close" onclick="closePopup()">×</button>
            <div class="popup-header">
                <h2 id="popupTitle">Add New Package</h2>
                <p id="popupSubtitle">Fill in the package details below</p>
            </div>
            <form id="packageForm">
                <input type="hidden" name="package_id" id="packageId" value="">
                <input type="hidden" name="action" id="formAction" value="add">
                
                <div class="form-group">
                    <label>Package Name</label>
                    <input type="text" name="name" id="packageName" placeholder="e.g., Starter Pack, Premium Pack" required>
                    <small>A descriptive name for the package</small>
                </div>
                
                <div class="form-group">
                    <label>Credit Amount</label>
                    <input type="number" name="credit_amount" id="creditAmount" min="1" placeholder="e.g., 100" required>
                    <small>Number of credits users will receive</small>
                </div>
                
                <div class="form-group">
                    <label>Price (IDR)</label>
                    <input type="number" name="price_money" id="priceMoney" min="0" placeholder="e.g., 10000" required>
                    <small>Price in Indonesian Rupiah</small>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closePopup()">Cancel</button>
                    <button type="submit" class="btn-submit" id="submitBtn">Add Package</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddPopup() {
            document.getElementById('popupTitle').textContent = 'Add New Package';
            document.getElementById('popupSubtitle').textContent = 'Fill in the package details below';
            document.getElementById('submitBtn').textContent = 'Add Package';
            document.getElementById('formAction').value = 'add';
            document.getElementById('packageForm').reset();
            document.getElementById('packageId').value = '';
            document.getElementById('packagePopup').classList.add('active');
        }

        function openEditPopup(pkg) {
            document.getElementById('popupTitle').textContent = 'Edit Package';
            document.getElementById('popupSubtitle').textContent = 'Update the package details below';
            document.getElementById('submitBtn').textContent = 'Update Package';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('packageId').value = pkg.package_id;
            document.getElementById('packageName').value = pkg.name;
            document.getElementById('creditAmount').value = pkg.credit_amount;
            document.getElementById('priceMoney').value = pkg.price_money;
            document.getElementById('packagePopup').classList.add('active');
        }

        function closePopup() {
            document.getElementById('packagePopup').classList.remove('active');
        }

        function deletePackage(id, name) {
            Swal.fire({
                title: 'Delete Package?',
                text: `Are you sure you want to delete "${name}"? This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('package_id', id);

                    fetch('process_admin_topup.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if(data.status === 'success') {
                            Swal.fire({ 
                                title: 'Deleted!', 
                                text: data.message, 
                                icon: 'success', 
                                confirmButtonColor: '#FF8A50' 
                            }).then(() => { location.reload(); });
                        } else {
                            Swal.fire({ 
                                title: 'Error', 
                                text: data.message, 
                                icon: 'error', 
                                confirmButtonColor: '#FF8A50' 
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire('Error', 'Something went wrong.', 'error');
                    });
                }
            });
        }

        document.getElementById('packageForm').onsubmit = function(e) {
            e.preventDefault();
            closePopup();
            
            const action = document.getElementById('formAction').value;
            const actionText = action === 'add' ? 'Adding' : 'Updating';
            
            Swal.fire({
                title: 'Processing...',
                text: `${actionText} package`,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => { Swal.showLoading(); }
            });

            const formData = new FormData(this);
            fetch('process_admin_topup.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                Swal.close();
                if(data.status === 'success') {
                    Swal.fire({ 
                        title: 'Success!', 
                        text: data.message, 
                        icon: 'success', 
                        confirmButtonColor: '#FF8A50' 
                    }).then(() => { location.reload(); });
                } else {
                    Swal.fire({ 
                        title: 'Error', 
                        text: data.message, 
                        icon: 'error', 
                        confirmButtonColor: '#FF8A50' 
                    });
                }
            })
            .catch(error => {
                Swal.close();
                Swal.fire('Error', 'Something went wrong.', 'error');
            });
        };

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closePopup();
        });
    </script>
</body>
</html>