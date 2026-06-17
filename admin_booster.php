<?php
session_start();
include "includes/header.php";
include "includes/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

$boosters_query = "SELECT * FROM ms_boosters ORDER BY package_price ASC";
$boosters_result = mysqli_query($conn, $boosters_query);

function getBoosterIcon($multiplier, $saved_icon = null) {
    $upload_path = "uploads/booster/";
    
    if (!empty($saved_icon) && file_exists($upload_path . $saved_icon)) {
        return $upload_path . htmlspecialchars($saved_icon);
    }
    
    if ($multiplier <= 1.5) return "https://cdn-icons-png.flaticon.com/512/1077/1077035.png";
    if ($multiplier <= 2.0) return "https://cdn-icons-png.flaticon.com/512/1048/1048927.png";
    return "https://cdn-icons-png.flaticon.com/512/3050/3050525.png";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swapify - Admin Booster Management</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="css/admin_booster.css?v=12">
</head>

<body>
    <div class="container">
        <div class="page-header">
            <h1>Booster Management</h1>
            <p>Manage booster packages and pricing for your users</p>
            <div class="underline"></div>
        </div>

        <div class="action-bar">
            <h2>All Boosters</h2>
            <button class="btn-add" onclick="openAddPopup()">+ Add New Booster</button>
        </div>

        <div class="boosters-table">
            <?php if (mysqli_num_rows($boosters_result) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Booster Name</th>
                            <th>Multiplier</th>
                            <th>Price (Credits)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($booster = mysqli_fetch_assoc($boosters_result)): ?>
                            <tr>
                                <td>
                                    <div class="booster-name-cell">
                                        <img src="<?= getBoosterIcon($booster['multiplier'], $booster['image_url']) ?>" alt="icon" class="booster-icon-small">
                                        <strong><?= htmlspecialchars($booster['package_name']) ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-multiplier"><?= $booster['multiplier'] ?>x</span>
                                </td>
                                <td><strong><?= number_format($booster['package_price']) ?></strong> Credits</td>
                                <td>
                                    <div class="action-btns">
                                        <button class="btn-edit" onclick='openEditPopup(<?= json_encode($booster) ?>)'>Edit</button>
                                        <button class="btn-delete" onclick="deleteBooster(<?= $booster['package_id'] ?>, '<?= htmlspecialchars($booster['package_name']) ?>')">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <img src="https://cdn-icons-png.flaticon.com/512/2748/2748558.png" alt="empty">
                    <h3>No Boosters Yet</h3>
                    <p>Create your first booster package to get started</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="popup-overlay" id="boosterPopup">
        <div class="popup-content">
            <button class="popup-close" onclick="closePopup()">×</button>
            <div class="popup-header">
                <h2 id="popupTitle">Add New Booster</h2>
                <p id="popupSubtitle">Fill in the details below</p>
            </div>
            <form id="boosterForm" enctype="multipart/form-data">
                <input type="hidden" name="package_id" id="boosterId" value="">
                <input type="hidden" name="action" id="formAction" value="add">
                
                <div class="form-group">
                    <label>Booster Name</label>
                    <input type="text" name="package_name" id="boosterName" placeholder="e.g., Bronze Booster" required>
                </div>

                <div class="form-group">
                    <label>Booster Icon (Optional)</label>
                    <input type="file" name="icon" id="boosterIcon" accept="image/*">
                    <small id="currentIconLabel" style="color: #666; display:none;">Leave blank to keep current icon</small>
                </div>
                
                <div class="form-group">
                    <label>Multiplier</label>
                    <input type="number" name="multiplier" id="multiplier" step="0.1" min="1" placeholder="e.g., 1.5" required>
                </div>
                
                <div class="form-group">
                    <label>Price (Credits)</label>
                    <input type="number" name="package_price" id="price" min="0" placeholder="e.g., 500" required>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closePopup()">Cancel</button>
                    <button type="submit" class="btn-submit" id="submitBtn">Add Booster</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddPopup() {
            document.getElementById('popupTitle').textContent = 'Add New Booster';
            document.getElementById('popupSubtitle').textContent = 'Fill in the details below';
            document.getElementById('submitBtn').textContent = 'Add Booster';
            document.getElementById('formAction').value = 'add';
            document.getElementById('boosterForm').reset();
            document.getElementById('boosterId').value = '';
            document.getElementById('currentIconLabel').style.display = 'none';
            document.getElementById('boosterPopup').classList.add('active');
        }

        function openEditPopup(booster) {
            document.getElementById('popupTitle').textContent = 'Edit Booster';
            document.getElementById('popupSubtitle').textContent = 'Update the details below';
            document.getElementById('submitBtn').textContent = 'Update Booster';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('boosterId').value = booster.package_id;
            document.getElementById('boosterName').value = booster.package_name;
            document.getElementById('multiplier').value = booster.multiplier;
            document.getElementById('price').value = booster.package_price;
            
            document.getElementById('currentIconLabel').style.display = 'block';
            document.getElementById('boosterPopup').classList.add('active');
        }

        function closePopup() {
            document.getElementById('boosterPopup').classList.remove('active');
        }

        function deleteBooster(id, name) {
            Swal.fire({
                title: 'Delete Booster?',
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

                    fetch('process_admin_booster.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if(data.status === 'success') {
                            Swal.fire({ title: 'Deleted!', text: data.message, icon: 'success', confirmButtonColor: '#FF6B35' })
                            .then(() => { location.reload(); });
                        } else {
                            Swal.fire({ title: 'Error', text: data.message, icon: 'error', confirmButtonColor: '#FF6B35' });
                        }
                    })
                    .catch(error => {
                        Swal.fire('Error', 'Something went wrong.', 'error');
                    });
                }
            });
        }

        document.getElementById('boosterForm').onsubmit = function(e) {
            e.preventDefault();
            
            const action = document.getElementById('formAction').value;
            const actionText = action === 'add' ? 'Adding' : 'Updating';
            
            Swal.fire({
                title: 'Processing...',
                text: `${actionText} booster package`,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => { Swal.showLoading(); }
            });

            const formData = new FormData(this);
            fetch('process_admin_booster.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                Swal.close();
                if(data.status === 'success') {
                    closePopup();
                    Swal.fire({ 
                        title: 'Success!', 
                        text: data.message, 
                        icon: 'success', 
                        confirmButtonColor: '#FF6B35' 
                    }).then(() => { location.reload(); });
                } else {
                    Swal.fire({ 
                        title: 'Error', 
                        text: data.message, 
                        icon: 'error', 
                        confirmButtonColor: '#FF6B35' 
                    });
                }
            })
            .catch(error => {
                Swal.close();
                Swal.fire('Error', 'Something went wrong.', 'error');
            });
        };
    </script>
</body>
</html>