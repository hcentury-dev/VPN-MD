<?php
header('Content-Type: application/json');
$targetDir = "images/uploads/";

if (!file_exists($targetDir)) {
    mkdir($targetDir, 0755, true);
}

if (isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $type = $_POST['type'] ?? 'image';
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $allowed = ($type === 'image') ? ['jpg', 'jpeg', 'png', 'gif'] : ['mp4', 'avi', 'mov'];

    if (!in_array(strtolower($ext), $allowed)) {
        echo json_encode(['status' => 'error', 'msg' => 'نوع فایل مجاز نیست.']);
        exit;
    }

    $filename = uniqid("ad_") . "." . $ext;
    $destination = $targetDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        echo json_encode(['status' => 'success', 'filename' => $filename, 'path' => $destination]);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'آپلود انجام نشد.']);
    }
} else {
    echo json_encode(['status' => 'error', 'msg' => 'فایل دریافت نشد.']);
}
