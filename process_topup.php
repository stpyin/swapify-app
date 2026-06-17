<?php
include "includes/db_connect.php";
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $package_id = $_POST['package_id'];
    $method = "Visa •••• 4242";
    $now = date('Y-m-d H:i:s');

    $query = "INSERT INTO tr_credittopup (user_id, package_id, payment_method, confirmed_at, created_at) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iisss", $user_id, $package_id, $method, $now, $now);

    if ($stmt->execute()) {        
        header("Location: booster.php?status=success");
    } else {
        header("Location: booster.php?status=error");
    }
}