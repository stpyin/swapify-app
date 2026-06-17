<?php
session_start();
include "includes/db_connect.php";
include "includes/header.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    $img_query = "SELECT image FROM ms_voucher WHERE voucher_id = ?";
    $img_stmt = $conn->prepare($img_query);
    $img_stmt->bind_param("i", $id);
    $img_stmt->execute();
    $res = $img_stmt->get_result()->fetch_assoc();
    
    if ($res && !empty($res['image'])) {
        $path = "images/vouchers/" . $res['image'];
        if (file_exists($path)) unlink($path);
    }

    $delete_query = "UPDATE ms_voucher SET is_active = 0 WHERE voucher_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: manage_vouchers.php?status=success&message=Voucher deleted successfully");
    } else {
        header("Location: manage_vouchers.php?status=error&message=Failed to delete voucher");
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $voucher_name = mysqli_real_escape_string($conn, $_POST['voucher_name']);
    $partner_name = mysqli_real_escape_string($conn, $_POST['partner_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $credits_price = intval($_POST['credits_price']);
    $stock = intval($_POST['stock']);
    $end_date = $_POST['end_date'];
    $voucher_id = isset($_POST['voucher_id']) ? intval($_POST['voucher_id']) : 0;
    
    $image_name = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $image_name = uniqid() . '.' . $ext;
            if (!is_dir('images/vouchers/')) {
                mkdir('images/vouchers/', 0777, true);
            }
            move_uploaded_file($_FILES['image']['tmp_name'], "images/vouchers/" . $image_name);
            
            if ($voucher_id > 0) {
                $old_img_query = "SELECT image FROM ms_voucher WHERE voucher_id = ?";
                $old_stmt = $conn->prepare($old_img_query);
                $old_stmt->bind_param("i", $voucher_id);
                $old_stmt->execute();
                $old_res = $old_stmt->get_result()->fetch_assoc();
                if ($old_res && !empty($old_res['image'])) {
                    $old_path = "images/vouchers/" . $old_res['image'];
                    if (file_exists($old_path)) unlink($old_path);
                }
            }
        }
    }
    
    if ($voucher_id > 0) {
        if ($image_name !== '') {
            $query = "UPDATE ms_voucher SET voucher_name=?, partner_name=?, description=?, credits_price=?, stock=?, end_date=?, image=? WHERE voucher_id=?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssiissi", $voucher_name, $partner_name, $description, $credits_price, $stock, $end_date, $image_name, $voucher_id);
        } else {
            $query = "UPDATE ms_voucher SET voucher_name=?, partner_name=?, description=?, credits_price=?, stock=?, end_date=? WHERE voucher_id=?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssiisi",
                        $voucher_name,
                        $partner_name,
                        $description,
                        $credits_price,
                        $stock,
                        $end_date,
                        $voucher_id
                    );
        }
        $message = "Voucher updated successfully";
    } else {
        $query = "INSERT INTO ms_voucher (voucher_name, partner_name, description, credits_price, stock, end_date, image) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssiiss", $voucher_name, $partner_name, $description, $credits_price, $stock, $end_date, $image_name);
        $message = "Voucher added successfully";
    }
    
    if ($stmt && $stmt->execute()) {
        header("Location: manage_vouchers.php?status=success&message=" . urlencode($message));
    } else {
        $err = $stmt ? $stmt->error : "Preparation failed";
        header("Location: manage_vouchers.php?status=error&message=" . urlencode("Operation failed: " . $err));
    }
    exit();
}

$edit_voucher = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_query = "SELECT * FROM ms_voucher WHERE voucher_id = ?";
    $stmt = $conn->prepare($edit_query);
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_voucher = $stmt->get_result()->fetch_assoc();
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$query = "SELECT * FROM ms_voucher 
          WHERE is_active = 1 " . 
          ($search ? "AND (voucher_name LIKE '%$search%' OR partner_name LIKE '%$search%')" : "") . 
          " ORDER BY created_at DESC";

$result = mysqli_query($conn, $query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Swapify Admin - Manage Vouchers</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="css/manage_vouchers.css?v=4">
</head>
<body>

<div class="container">
    <div class="page-header">
        <div>
            <h1>Voucher Inventory</h1>
            <p>Admin panel to manage Swapify digital vouchers.</p>
        </div>
        <button class="btn btn-primary" onclick="openModal()">+ Add New Voucher</button>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Voucher</th>
                    <th>Partner</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Created At</th>
                    <th>End Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <?php if (!empty($row['image'])): ?>
                                        <img src="images/vouchers/<?= htmlspecialchars($row['image']) ?>" class="voucher-img">
                                    <?php else: ?>
                                        <div class="voucher-img placeholder">
                                            <?= strtoupper(substr($row['partner_name'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>

                                    <strong><?= htmlspecialchars($row['voucher_name']) ?></strong>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($row['partner_name']) ?></td>
                            <td>🪙 <?= number_format($row['credits_price']) ?></td>
                            <td><span class="badge" style="background:#E0F2FE; color:#0369A1;"><?= $row['stock'] ?> pcs</span></td>
                            <td style="font-size: 12px;"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                            <td style="font-size: 12px; color: #EF4444; font-weight: bold;"><?= date('d M Y', strtotime($row['end_date'])) ?></td>
                            <td>
                                <button class="btn btn-warning" onclick="editVoucher(<?= $row['voucher_id'] ?>)">Edit</button>
                                <button class="btn btn-danger" onclick="deleteVoucher(<?= $row['voucher_id'] ?>, '<?= addslashes($row['voucher_name']) ?>')">Del</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center;">No vouchers found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="voucherModal" class="modal">
    <div class="modal-content">
        <h2 id="modalTitle" style="margin-top:0;">Voucher Form</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="voucher_id" id="voucher_id">
            
            <div class="form-group">
                <label>Voucher Name</label>
                <input type="text" name="voucher_name" id="voucher_name" required>
            </div>
            
            <div class="form-group">
                <label>Partner</label>
                <input type="text" name="partner_name" id="partner_name" required>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="description" rows="2" required></textarea>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Price (Credits)</label>
                    <input type="number" name="credits_price" id="credits_price" required>
                </div>
                <div class="form-group">
                    <label>Stock</label>
                    <input type="number" name="stock" id="stock" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Expiry Date (End Date)</label>
                <input type="date" name="end_date" id="end_date" required>
            </div>
            
            <div class="form-group">
                <label>Voucher Image</label>
                <input type="file" name="image" accept="image/*">
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn btn-primary" style="flex: 2;">Save Changes</button>
                <button type="button" class="btn btn-danger" onclick="closeModal()" style="flex: 1; background:#6B7280; color: white;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.has('status')) {
        Swal.fire({
            icon: urlParams.get('status'),
            title: urlParams.get('status').toUpperCase(),
            text: urlParams.get('message')
        });
    }

    function openModal() {
        document.getElementById('voucher_id').value = '';
        document.querySelector('#voucherModal form').reset();
        document.getElementById('modalTitle').innerText = 'Add New Voucher';
        document.getElementById('voucherModal').classList.add('active');
    }

    function closeModal() { 
        document.getElementById('voucherModal').classList.remove('active'); 
    }

    function editVoucher(id) { 
        window.location.href = 'manage_vouchers.php?edit=' + id; 
    }

    function deleteVoucher(id, name) {
        Swal.fire({ 
            title: 'Delete?', 
            text: "Are you sure you want to remove " + name + "?", 
            icon: 'warning', 
            showCancelButton: true, 
            confirmButtonColor: '#EF4444',
            confirmButtonText: 'Yes, delete it!' 
        }).then((result) => { 
            if (result.isConfirmed) window.location.href = 'manage_vouchers.php?delete=' + id; 
        });
    }

    <?php if ($edit_voucher): ?>
        document.getElementById('voucherModal').classList.add('active');
        document.getElementById('modalTitle').innerText = 'Edit Voucher';
        document.getElementById('voucher_id').value = "<?= $edit_voucher['voucher_id'] ?>";
        document.getElementById('voucher_name').value = "<?= addslashes($edit_voucher['voucher_name']) ?>";
        document.getElementById('partner_name').value = "<?= addslashes($edit_voucher['partner_name']) ?>";
        document.getElementById('description').value = "<?= addslashes($edit_voucher['description']) ?>";
        document.getElementById('credits_price').value = "<?= $edit_voucher['credits_price'] ?>";
        document.getElementById('stock').value = "<?= $edit_voucher['stock'] ?>";
        document.getElementById('end_date').value = "<?= date('Y-m-d', strtotime($edit_voucher['end_date'])) ?>";
    <?php endif; ?>
</script>
</body>
</html>