<?php

include('includes/header.php');
include('includes/function.php');
include('language/language.php');

if (isset($_POST['deviceId']) && isset($_POST['status'])) {
    $deviceId = $_POST['deviceId']; 
    $status = $_POST['status'];

    $DeviceQuery = "UPDATE `devices` SET `status` = '$status' WHERE `devices`.`deviceId` = '$deviceId'";
    $DeviceResult = mysqli_query($mysqli, $DeviceQuery);
    
        if ($DeviceResult) {
            $response = array('success' => true, 'message' => 'وضعیت با موفقیت تغییر کرد');
            echo json_encode($response);
        } else {
            echo json_encode(['success' => false, 'message' => 'خطا در به‌روزرسانی جدول users']);
        }
}
?>
