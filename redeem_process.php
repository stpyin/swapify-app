<?php
session_start();
include "includes/db_connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$voucher_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : null;

if (!$voucher_id) {
    header("Location: voucher.php?status=error&message=Voucher ID not found.");
    exit();
}

$query = "SELECT v.credits_price, v.stock, c.balance 
          FROM ms_voucher v 
          JOIN ms_usercredits c ON c.user_id = '$user_id'
          WHERE v.voucher_id = '$voucher_id'";

$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    header("Location: voucher.php?status=error&message=User credit data not initialized.");
    exit();
}

$price = $data['credits_price'];
$current_balance = $data['balance'];
$stock = $data['stock'];

if ($current_balance < $price) {
    header("Location: voucher.php?status=error&message=Insufficient balance! You need " . number_format($price) . " credits.");
    exit();
}

if ($stock <= 0) {
    header("Location: voucher.php?status=error&message=Voucher is out of stock.");
    exit();
}

mysqli_begin_transaction($conn);

try {
    $update_balance = "UPDATE ms_usercredits 
                       SET balance = balance - $price, 
                           update_at = NOW() 
                       WHERE user_id = '$user_id'";
    if (!mysqli_query($conn, $update_balance)) throw new Exception("Failed to update balance.");

    $update_stock = "UPDATE ms_voucher SET stock = stock - 1 WHERE voucher_id = '$voucher_id'";
    if (!mysqli_query($conn, $update_stock)) throw new Exception("Failed to update stock.");

    $insert_history = "INSERT INTO tr_redeemvoucher (voucher_id, user_id, redeem_date, status) 
                       VALUES ('$voucher_id', '$user_id', NOW(), 'available')";
    
    if (!mysqli_query($conn, $insert_history)) throw new Exception("Failed to insert history.");

    mysqli_commit($conn);
    header("Location: voucher.php?status=success&message=Redeem successful!");

} catch (Exception $e) {
    mysqli_rollback($conn);
    header("Location: voucher.php?status=error&message=Transaction failed.");
}
?>