<?php
session_start();
include "includes/db_connect.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $name = trim($_POST['name']);
    $credit_amount = intval($_POST['credit_amount']);
    $price_money = intval($_POST['price_money']);

    if (empty($name)) {
        echo json_encode(['status' => 'error', 'message' => 'Package name is required']);
        exit();
    }

    if ($credit_amount < 1) {
        echo json_encode(['status' => 'error', 'message' => 'Credit amount must be at least 1']);
        exit();
    }

    if ($price_money < 0) {
        echo json_encode(['status' => 'error', 'message' => 'Price cannot be negative']);
        exit();
    }

    $check_query = "SELECT package_id FROM ms_topuppackages WHERE name = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'A package with this name already exists']);
        exit();
    }

    $insert_query = "INSERT INTO ms_topuppackages (name, credit_amount, price_money, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("sii", $name, $credit_amount, $price_money);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Package added successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add package: ' . $conn->error]);
    }
}

elseif ($action === 'edit') {
    $package_id = intval($_POST['package_id']);
    $name = trim($_POST['name']);
    $credit_amount = intval($_POST['credit_amount']);
    $price_money = intval($_POST['price_money']);

    if (empty($name)) {
        echo json_encode(['status' => 'error', 'message' => 'Package name is required']);
        exit();
    }

    if ($credit_amount < 1) {
        echo json_encode(['status' => 'error', 'message' => 'Credit amount must be at least 1']);
        exit();
    }

    if ($price_money < 0) {
        echo json_encode(['status' => 'error', 'message' => 'Price cannot be negative']);
        exit();
    }

    $check_query = "SELECT package_id FROM ms_topuppackages WHERE package_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $package_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Package not found']);
        exit();
    }

    $check_name_query = "SELECT package_id FROM ms_topuppackages WHERE name = ? AND package_id != ?";
    $stmt = $conn->prepare($check_name_query);
    $stmt->bind_param("si", $name, $package_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'A package with this name already exists']);
        exit();
    }

    $update_query = "UPDATE ms_topuppackages SET name = ?, credit_amount = ?, price_money = ? WHERE package_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("siii", $name, $credit_amount, $price_money, $package_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Package updated successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update package: ' . $conn->error]);
    }
}

elseif ($action === 'delete') {
    $package_id = intval($_POST['package_id']);

    $check_query = "SELECT package_id FROM ms_topuppackages WHERE package_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $package_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Package not found']);
        exit();
    }

    $usage_query = "SELECT COUNT(*) as usage_count FROM tr_credittopup WHERE package_id = ?";
    $stmt = $conn->prepare($usage_query);
    $stmt->bind_param("i", $package_id);
    $stmt->execute();
    $usage_result = $stmt->get_result()->fetch_assoc();

    if ($usage_result['usage_count'] > 0) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Cannot delete this package. It has been purchased ' . $usage_result['usage_count'] . ' time(s). Consider editing it instead.'
        ]);
        exit();
    }

    $delete_query = "DELETE FROM ms_topuppackages WHERE package_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $package_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Package deleted successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete package: ' . $conn->error]);
    }
}

else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}

$conn->close();
?>