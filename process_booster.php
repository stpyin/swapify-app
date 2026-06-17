<?php
session_start();
include "includes/db_connect.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $booster_id = $_POST['booster_id'];

    $stmt = $conn->prepare("SELECT price FROM ms_boosters WHERE booster_id = ?");
    $stmt->bind_param("i", $booster_id);
    $stmt->execute();
    $booster = $stmt->get_result()->fetch_assoc();

    if (!$booster) {
        echo json_encode(['status' => 'error', 'message' => 'Booster package not found.']);
        exit;
    }

    $price = $booster['price'];

    $stmt = $conn->prepare("SELECT balance FROM ms_usercredits WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_credit = $stmt->get_result()->fetch_assoc();

    if (!$user_credit || $user_credit['balance'] < $price) {
        echo json_encode(['status' => 'error', 'message' => 'Insufficient credits. Please top up first.']);
        exit;
    }

    $conn->begin_transaction();

    try {
        $new_balance = $user_credit['balance'] - $price;
        $stmt = $conn->prepare("UPDATE ms_usercredits SET balance = ?, update_at = NOW() WHERE user_id = ?");
        $stmt->bind_param("ii", $new_balance, $user_id);
        $stmt->execute();

        $stmt = $conn->prepare("INSERT INTO tr_userbooster (user_id, booster_id, purchased_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $user_id, $booster_id);
        $stmt->execute();

        $conn->commit();
        echo json_encode(['status' => 'success']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Transaction failed: ' . $e->getMessage()]);
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}