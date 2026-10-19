<?php 
include('../includes/function.php');
include('../includes/connection.php');

$Now = date('Y-m-d H:i:s');

$users_qry = "SELECT * FROM users LIMIT 200";
$users = mysqli_query($mysqli, $users_qry);

if ($users) {
    $update_status = $mysqli->prepare("UPDATE users SET status = ? WHERE username = ?");

    while ($users_row = mysqli_fetch_assoc($users)) {

        // بررسی دقیق وضعیت تاریخ
        if ($users_row['date'] == '0000-00-00') {
            $status = 1;
        } elseif ($users_row['date'] < $Now) {
            $status = 2;
        } elseif ($users_row['date'] >= $Now) {
            $status = 0;
        } else {
            continue; // اگر وضعیت غیرقابل تشخیص بود، برو به کاربر بعدی
        }

        // اجرای بروزرسانی
        $update_status->bind_param('is', $status, $users_row['username']);
        $update_status->execute();
    }

    $update_status->close();
} else {
    echo "Error: " . mysqli_error($mysqli);
}

mysqli_close($mysqli);
?>
