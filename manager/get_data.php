<?php 

include("includes/connection.php");
include('includes/function.php');
include('language/language.php');  

$selectedUsers = $_POST['selectedUsers'];
$selectedUsersText = '';

foreach ($selectedUsers as $user_id) {
    $user_qry = "SELECT * FROM users WHERE id = $user_id";
    $user_result = mysqli_query($mysqli, $user_qry);
    $user_row = mysqli_fetch_array($user_result);

    if ($user_row) {
         $selectedUsersText .= "نام کاربری: " . $user_row['username'] . "\n" . "رمز عبور: " . $user_row['password'] . "\n __________ \n";
    } else {
        echo "مشکلی در دریافت اطلاعات کاربر با شناسه $user_id به وجود آمده است.";
    }
}

echo $selectedUsersText;
?>
