<?php 

include('includes/header.php');
include('includes/function.php');
include('language/language.php'); 
include('includes/jdf.php'); 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    
    $user_qry = "SELECT * FROM users WHERE id='" . $user_id . "'";
    $user_result = mysqli_query($mysqli, $user_qry);
    $user_row = mysqli_fetch_assoc($user_result);
    $username = $user_row['username'];
    
    if (is_numeric($user_id)) {

        $query = "UPDATE `users` SET `is_login` = '0', login = 'false', deviceIMEI = '0', online = 'offline' WHERE `users`.`id` = $user_id";
        $result = mysqli_query($mysqli, $query);
        
        if ($result) {

            $delete_devices_query = "DELETE FROM `devices` WHERE `username` = '$username'";
            $delete_result = mysqli_query($mysqli, $delete_devices_query);
            
            if ($delete_result) {
                echo "User status updated and devices deleted successfully.";
            } else {
                echo "Error deleting devices: " . mysqli_error($mysqli);
            }
        } else {
            echo "Error updating user: " . mysqli_error($mysqli);
        }
    } else {
        echo "Invalid user ID";
    }
} else {
    echo "Invalid request";
}
?>
