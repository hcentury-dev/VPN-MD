<?php
include('../includes/connection.php');
include('../includes/function.php');
date_default_timezone_set('Asia/Tehran');

$batch_size = 200;

$query = "SELECT * FROM users WHERE online = 'online' LIMIT ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $batch_size);
$stmt->execute();
$users_result = $stmt->get_result();

while ($user_row = $users_result->fetch_assoc()) {
    $last_update_timestamp = strtotime($user_row['last_update']);
    $current_time = strtotime(date('Y-m-d H:i:s'));

    $time_difference = abs($current_time - $last_update_timestamp);

    if ($time_difference > 60) {
        $username = $mysqli->real_escape_string($user_row['username']);
        $sql = "UPDATE users SET online = 'offline' WHERE username = ?";
        
        $stmt_update = $mysqli->prepare($sql);
        $stmt_update->bind_param("s", $username);
        $stmt_update->execute();
        
        $stmt_update->close();
    }
}

$stmt->close();
$mysqli->close();
?>
