<?php

include('includes/header.php');
include('includes/function.php');
include('language/language.php');

if (isset($_POST['userId']) && isset($_POST['deviceId'])) {
    $userId = $_POST['userId']; 
    $deviceIdToDelete = $_POST['deviceId'];

    $deleteDeviceQuery = "DELETE FROM devices WHERE deviceId = '$deviceIdToDelete'";
    $deleteDeviceResult = mysqli_query($mysqli, $deleteDeviceQuery);
    
        $updateUserQuery = "UPDATE users SET `is_login` = is_login - 1 WHERE id = '$userId'";
        $updateUserResult = mysqli_query($mysqli, $updateUserQuery);

        if ($updateUserResult) {
            echo json_encode(['success' => true, 'message' => 'دستگاه با موفقیت حذف شد']);
        } else {
            echo json_encode(['success' => false, 'message' => 'خطا در به‌روزرسانی جدول users']);
        }
}
?>
