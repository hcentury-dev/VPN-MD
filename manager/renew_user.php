<?php 

    if ($_SERVER['HTTP_HOST'] == "localhost" || $_SERVER['HTTP_HOST'] == "192.168.1.103") {
        define('DB_USER', 'alpha');
        define('DB_PASSWORD', '15350000');
        define('DB_HOST', 'localhost');
        define('DB_NAME', 'alpha');
    } else {
        define('DB_USER', 'alpha');
        define('DB_PASSWORD', '15350000');
        define('DB_HOST', 'localhost');
        define('DB_NAME', 'alpha');
    }
    
    $mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    
    mysqli_query($mysqli, "SET NAMES 'utf8'");
    
    date_default_timezone_set('Asia/Tehran');
header('Content-Type: application/json');

    if ($_POST['user_id']) {
        
        $user_id = $_POST['user_id'];
        $agent_id = $_POST['agent_id'];
        
        $user_qry = "SELECT * FROM users WHERE id='" . $user_id . "'";
        $user_result = mysqli_query($mysqli, $user_qry);
        $user_row = mysqli_fetch_assoc($user_result); 
    
        $total = $user_row['total'];
        $adate = $user_row['adate'];
        $price = $user_row['price'];

        $date = new DateTime();
        $date->modify("+$adate days");
        $new_date = $date->format('Y-m-d');    
        
        if ($user_row['date'] != "0000-00-00") {

            $check_query = "SELECT inventory FROM `manager` WHERE `id` = '$agent_id'";
            $check_result = mysqli_query($mysqli, $check_query);
            $check_row = mysqli_fetch_assoc($check_result);
        
            if ($check_row && $check_row['inventory'] >= $price) {
                mysqli_query($mysqli, "UPDATE `users` SET `size` = '0', full = $total, adate = '$adate', date = '$new_date' WHERE `users`.`id` = $user_id");
                mysqli_query($mysqli, "UPDATE `manager` SET inventory = inventory - $price WHERE `manager`.`id` = '$agent_id'");
                echo json_encode(["success" => true, "message" => "تمدید با موفقیت انجام شد"]);
            } else {
                echo json_encode(["success" => false, "message" => "موجودی نماینده کافی نیست"]);
            }
        }

    } else {
        echo json_encode(["success" => false, "message" => "درخواست نامعتبر"]);
    }
    
?>
