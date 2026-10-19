<?php

// تابع کمکی برای نوشتن لاگ
function write_log($message) {
    $logFile =  'upload_log.txt';
    $date = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$date] $message\n", FILE_APPEND);
}

write_log("Request started.");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['thumbnail'])) {

    write_log("Thumbnail file info: " . print_r($_FILES['thumbnail'], true));

    $videoName = pathinfo($_POST['video_name'], PATHINFO_FILENAME);
    write_log("Video name: $videoName");

    $thumbnailDir = 'video/ads/thumbnails';

    if (!is_dir($thumbnailDir)) {
        write_log("Directory does not exist, creating: $thumbnailDir");
        if (!mkdir($thumbnailDir, 0755, true)) {
            write_log("Failed to create directory: $thumbnailDir");
            http_response_code(500);
            echo "Failed to create directory";
            exit;
        } else {
            write_log("Directory created successfully.");
        }
    } else {
        write_log("Directory exists: $thumbnailDir");
    }

    if ($_FILES['thumbnail']['error'] !== UPLOAD_ERR_OK) {
        write_log("Upload error code: " . $_FILES['thumbnail']['error']);
        http_response_code(400);
        echo "Upload error: " . $_FILES['thumbnail']['error'];
        exit;
    }

    $thumbnailPath = $thumbnailDir . '/' . $videoName . '.jpg';
    write_log("Thumbnail path: $thumbnailPath");

    if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $thumbnailPath)) {
        write_log("Thumbnail saved successfully at $thumbnailPath");
        echo "Thumbnail saved successfully";
    } else {
        write_log("Failed to move uploaded file.");
        http_response_code(500);
        echo "Failed to save thumbnail";
    }
} else {
    write_log("No thumbnail uploaded or wrong request method.");
    http_response_code(400);
    echo "No thumbnail uploaded or wrong request method";
}

write_log("Request ended.");
