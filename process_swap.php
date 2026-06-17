<?php
session_start();
include "includes/db_connect.php";

if(isset($_POST['agreement_id']) && isset($_POST['action'])){
    $id = $_POST['agreement_id'];
    $action = $_POST['action'];
    
    if($action == 'accept'){
        $status = 'scheduled';
    } else {
        $status = 'declined';
    }
    
    $query = "UPDATE tr_swapagreement SET status = '$status' WHERE agreement_id = '$id'";
    
    if(mysqli_query($conn, $query)){
        header("Location: dashboard.php?msg=success");
    } else {
        header("Location: dashboard.php?msg=error");
    }
}