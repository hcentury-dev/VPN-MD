<?php 

include("includes/connection.php");
include('includes/function.php');
include('language/language.php');  

$selectedUsers = $_POST['selectedUsers'];
$selectedUsersText = '';

foreach ($selectedUsers as $user_id) {

    $user_id = intval($user_id);

    $user_qry = "SELECT * FROM users WHERE id = $user_id";
    $user_result = mysqli_query($mysqli, $user_qry);
    $user_row = mysqli_fetch_array($user_result);

    if ($user_row) {

        $selectedUsersText .= "نام کاربری: " . 
                              $user_row['username'] . 
                              " | رمز عبور: " . 
                              $user_row['password'] . 
                              "\n";

    }
}

echo trim($selectedUsersText);
?>