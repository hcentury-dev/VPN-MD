<?php
    
    require_once __DIR__ . '/session_boot.php';
    ob_start();

    header("Content-Type: text/html;charset=UTF-8");
    
    if ($_SERVER['HTTP_HOST'] == "localhost" || $_SERVER['HTTP_HOST'] == "192.168.1.103") {
        define('DB_USER', 'demo');
        define('DB_PASSWORD', 'NDa2SiYjNk4S7BAw');
        define('DB_HOST', 'localhost');
        define('DB_NAME', 'demo');
    } else {
        define('DB_USER', 'demo');
        define('DB_PASSWORD', 'NDa2SiYjNk4S7BAw');
        define('DB_HOST', 'localhost');
        define('DB_NAME', 'demo');
    }
    
    $mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    
    if ($mysqli->connect_errno) {
        echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
        exit;
    }
    
    mysqli_query($mysqli, "SET NAMES 'utf8'");
    
    $setting_qry = "SELECT * FROM settings WHERE id='1'";
    $setting_result = mysqli_query($mysqli, $setting_qry);
    $settings_details = mysqli_fetch_assoc($setting_result);
    
    if (!$settings_details) {
        echo "Error fetching settings.";
        exit;
    }
    
    define("APP_API_KEY", 'YtmpgwcUzCbeh1JlsK0gR0njtXm9g9lAUt4pzsPZhsH8a');
    define("APP_NAME", $settings_details['app_name']);
    define("APP_LOGO", "manager1.png");
    
    if (isset($_SESSION['id'])) {
        $profile_qry = "SELECT * FROM manager WHERE id='" . $_SESSION['id'] . "'";
        $profile_result = mysqli_query($mysqli, $profile_qry);
        $profile_details = mysqli_fetch_assoc($profile_result);
    
        define("PROFILE_IMG", isset($profile_details['image']) ? $profile_details['image'] : 'default.jpg');
    } else {
        define("PROFILE_IMG", 'default.jpg');
    }
    
    
    ?>
