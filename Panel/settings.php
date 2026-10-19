<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require("includes/function.php");
require("language/language.php");
include("includes/connection.php");
include("includes/session_check.php");        
include('includes/jdf.php');

ensureColumn('settings', 'sales_bot_token', "varchar(255) NOT NULL DEFAULT '0'");
ensureColumn('settings', 'tg_relay_secret', "varchar(255) NOT NULL DEFAULT ''");
date_default_timezone_set('Asia/Tehran');

$admin_username = "";
if(isset($_SESSION['id'])) {
    $adm_qry = "SELECT username FROM admin WHERE id='".$_SESSION['id']."'";
    $adm_res = mysqli_query($mysqli, $adm_qry);
    if($adm_res && $adm_row = mysqli_fetch_assoc($adm_res)) {
        $admin_username = $adm_row['username'];
    }
}
    
$qry = "SELECT * FROM settings WHERE id='1'";
$result = mysqli_query($mysqli, $qry);
$settings_row = mysqli_fetch_assoc($result);

$type = explode(',', $settings_row['item_type']);
$backupDir = "backupDB/";
$lastUpdateFile = $backupDir."/last_backup.txt";

if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

function getLastUpdateDate() {
    global $lastUpdateFile;
    if (file_exists($lastUpdateFile)) {
        return file_get_contents($lastUpdateFile);
    }
    return null;
}
$lastUpdate = getLastUpdateDate();

function isIran() {
    $country = file_get_contents("http://ip-api.com/json/");
    if ($country) {
        $data = json_decode($country, true);
        if (isset($data['countryCode']) && $data['countryCode'] == 'IR') {
            return true;
        }
    }
    return false;
}

// --- AJAX Handler for Telegram Check via External Server ---
if(isset($_POST['check_telegram_api'])){
    $token = $_POST['token'];
    $link_get = $settings_row['link_get'];
    
    // تشخیص موقعیت سرور
    $server_in_iran = isIran();

    if ($server_in_iran) {
        // اگر سرور در ایران است، از سرور واسط استفاده کن
        $url = $link_get;
        $postData = [
            'action' => 'getMe',
            'telegram_token' => $token,
            'relay_secret' => isset($settings_row['tg_relay_secret']) ? $settings_row['tg_relay_secret'] : ''
        ];
    } else {
        // اگر سرور خارج از ایران است، مستقیم به تلگرام وصل شو
        $url = "https://api.telegram.org/bot" . $token . "/getMe";
        $postData = []; // مستقیم GET است یا POST بدون بدنه خاص
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        echo json_encode(['status' => 'error', 'message' => 'خطا در ارتباط: ' . $curl_error]);
    } else {
        $data = json_decode($response, true);
        
        // اگر از سرور واسط استفاده شده، پاسخ ممکن است در telegram_response باشد
        if ($server_in_iran) {
            $telegramData = isset($data['telegram_response']) ? $data['telegram_response'] : $data;
        } else {
            $telegramData = $data;
        }

        if (isset($telegramData['ok']) && $telegramData['ok']) {
            $bot_info = $telegramData['result'];
            echo json_encode([
                'status' => 'success',
                'first_name' => $bot_info['first_name'],
                'username' => isset($bot_info['username']) ? '@' . $bot_info['username'] : 'ندارد'
            ]);
        } else {
            $msg = isset($telegramData['description']) ? $telegramData['description'] : 'توکن نامعتبر است';
            echo json_encode(['status' => 'error', 'message' => $msg]);
        }
    }
    exit;
}
// --- AJAX Handler for User ID Check via External Server ---
if(isset($_POST['check_telegram_user'])){
    $token = $_POST['token'];
    $user_id = $_POST['user_id'];
    $link_get = $settings_row['link_get'];
    
    $postData = [
        'action' => 'getChat',
        'telegram_token' => $token,
        'telegram_id' => $user_id, // شناسه کاربر برای ارسال به سرور
        'relay_secret' => isset($settings_row['tg_relay_secret']) ? $settings_row['tg_relay_secret'] : ''
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $link_get);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    $telegramData = isset($data['telegram_response']) ? $data['telegram_response'] : $data;

    if (isset($telegramData['ok']) && $telegramData['ok']) {
        $chat_info = $telegramData['result'];
        $name = $chat_info['first_name'] ?? '';
        $lastname = $chat_info['last_name'] ?? '';
        $username = isset($chat_info['username']) ? '@' . $chat_info['username'] : 'ندارد';
        echo json_encode(['status' => 'success', 'name' => $name . ' ' . $lastname, 'username' => $username]);
    } else {
        $msg = isset($telegramData['description']) ? $telegramData['description'] : 'شناسه کاربری یافت نشد';
        echo json_encode(['status' => 'error', 'message' => $msg]);
    }
    exit;
}

// کاربران مهمان (Free)
if(isset($_POST['update_guest_quota']) && $_POST['update_guest_quota']=='1'){
    $add_gb = intval($_POST['total_quota_gb']);
    $action = $_POST['quota_action_guest'];

    if($action == 'add'){
        // افزودن به حجم فعلی
        $query = "UPDATE users SET total = total + $add_gb, full = full + $add_gb WHERE permium='Free'";
    } else {
        // جایگزینی (تنظیم کل حجم و مانده)
        $query = "UPDATE users SET total = $add_gb, full = $add_gb WHERE permium='Free'";
    }

    $run = mysqli_query($mysqli, $query);
    if(!$run){
        die("خطا: ".mysqli_error($mysqli));
    }

    $_SESSION['msg'] = "11";
    header("Location: settings.php");
    exit;
}

// کاربران اختصاصی (Pro)
if(isset($_POST['update_permium_quota']) && $_POST['update_permium_quota']=='1'){
    $add_gb = intval($_POST['total_quota_gb_pro']);
    $action = $_POST['quota_action_pro'];

    if($action == 'add'){
        $query = "UPDATE users SET total = total + $add_gb, full = full + $add_gb WHERE permium='Pro'";
    } else {
        $query = "UPDATE users SET total = $add_gb, full = $add_gb WHERE permium='Pro'";
    }

    $run = mysqli_query($mysqli, $query);
    if(!$run){
        die("خطا: ".mysqli_error($mysqli));
    }

    $_SESSION['msg'] = "11";
    header("Location: settings.php");
    exit;
}

if(isset($_POST['update_users_days'])){
    
    // --- مدیریت مهمانان (Free) ---
    if(isset($_POST['update_guest_days']) && $_POST['update_guest_days'] == '1'){
        $days_action = $_POST['days_action'];
        $days_count = intval($_POST['days_count']);
        
        // ۱. اصلاح تاریخ‌های خراب قبل از هر کاری
        $fix_guest_date = "UPDATE `users` SET `date` = CURDATE() WHERE `permium` = 'Free' AND (`date` = '0000-00-00' OR `date` IS NULL)";
        mysqli_query($mysqli, $fix_guest_date);

        if($days_action == 'add'){
            // افزودن روز به تاریخ فعلی و عدد کل
            $query_guest = "UPDATE `users` SET `date` = DATE_ADD(`date`, INTERVAL $days_count DAY), `adate` = `adate` + $days_count WHERE `permium` = 'Free'";
        } else {
            // جایگزینی: تاریخ شروع از امروز + تعداد روز
            $new_date = date("Y-m-d", strtotime("+$days_count days"));
            $query_guest = "UPDATE `users` SET `adate` = '$days_count', `date` = '$new_date' WHERE `permium` = 'Free'";
        }
        mysqli_query($mysqli, $query_guest);
    }

    // --- مدیریت اختصاصی (Pro) ---
    if(isset($_POST['update_permium_days']) && $_POST['update_permium_days'] == '1'){
        // دریافت و پاکسازی ورودی‌ها
        $days_action_pro = $_POST['days_action_pro'];
        $days_count_pro = intval($_POST['days_count_pro']);
        
        // ۱. اصلاح تاریخ‌های خراب قبل از هر کاری
        $fix_pro_date = "UPDATE `users` SET `date` = CURDATE() WHERE `permium` = 'pro' AND (`date` = '0000-00-00' OR `date` IS NULL)";
        mysqli_query($mysqli, $fix_pro_date);

        if($days_action_pro == 'add'){
            // حالت افزودن: اضافه کردن روز به تاریخ فعلی و افزایش تعداد روزها
            $query_permium = "UPDATE `users` SET
                                `date` = DATE_ADD(`date`, INTERVAL $days_count_pro DAY),
                                `adate` = `adate` + $days_count_pro
                              WHERE `permium` = 'pro'";
        } else {
            // حالت جایگزینی (Set): تنظیم تاریخ جدید از امروز
            // محاسبه تاریخ جدید: امروز + تعداد روز
            $new_date_pro = date("Y-m-d", strtotime("+$days_count_pro days"));
            
            $query_permium = "UPDATE `users` SET
                                `adate` = $days_count_pro,
                                `date` = '$new_date_pro'
                              WHERE `permium` = 'pro'";
        }
        // اجرای کوئری
        $result = mysqli_query($mysqli, $query_permium);
        if(!$result){
            echo "خطا در اجرای کوئری: " . mysqli_error($mysqli);
        }
    }
    
    $_SESSION['msg'] = "11";
    header("Location: settings.php");
    exit;
}

if (isset($_POST['fetch_database_output'])) {

    ignore_user_abort(true);
    set_time_limit(0);
    @ini_set('memory_limit', '1024M');
    @ini_set('max_execution_time', '0');
    @ini_set('output_buffering', 'off');
    @ini_set('zlib.output_compression', '0');

    $currentDate  = date("Y-m-d_H-i-s");
    $currentDate2 = date("Y-m-d H:i:s");
    $filename = $settings_row['app_name'] . "_" . $currentDate . ".sql";
    $filePath = rtrim($backupDir, "/\\") . DIRECTORY_SEPARATOR . $filename;

    $mysqli->set_charset("utf8mb4");
    $mysqli->query("SET NAMES utf8mb4");
    $mysqli->query("SET CHARACTER SET utf8mb4");
    $mysqli->query("SET character_set_connection=utf8mb4");

    $fp = fopen($filePath, 'wb');
    if (!$fp) {
        http_response_code(500);
        exit("Cannot create backup file.");
    }

    // Header
    fwrite($fp, "-- Database Backup: " . $settings_row['app_name'] . "\n");
    fwrite($fp, "-- Generated: " . $currentDate2 . "\n");
    fwrite($fp, "SET NAMES utf8mb4;\n");
    fwrite($fp, "SET FOREIGN_KEY_CHECKS=0;\n");
    fwrite($fp, "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n");
    fwrite($fp, "SET time_zone = '+00:00';\n\n");

    $mysqli->query("SET SESSION net_read_timeout=600");
    $mysqli->query("SET SESSION net_write_timeout=600");

    $tables = [];
    $views  = [];

    $res = $mysqli->query("SHOW FULL TABLES");
    while ($row = $res->fetch_array()) {
        $name = $row[0];
        $type = strtoupper($row[1]); // BASE TABLE / VIEW
        if ($type === 'VIEW') $views[] = $name;
        else $tables[] = $name;
    }

    $binaryTypes = ['blob','tinyblob','mediumblob','longblob','binary','varbinary','bit'];

    $chunkSize = 1000;
    $pageSize  = 5000;

    foreach ($tables as $table) {

        $resCreate = $mysqli->query("SHOW CREATE TABLE `$table`");
        if (!$resCreate) {
            fwrite($fp, "-- ERROR SHOW CREATE TABLE `$table`: " . $mysqli->error . "\n\n");
            continue;
        }
        $rowCreate = $resCreate->fetch_row();

        fwrite($fp, "DROP TABLE IF EXISTS `$table`;\n");
        fwrite($fp, $rowCreate[1] . ";\n\n");

        $cols = [];
        $colTypes = []; // name => isBinary
        $resCols = $mysqli->query("SHOW COLUMNS FROM `$table`");
        while ($c = $resCols->fetch_assoc()) {
            $field = $c['Field'];
            $type  = strtolower($c['Type']);
            $isBinary = false;
            foreach ($binaryTypes as $bt) {
                if (strpos($type, $bt) !== false) { $isBinary = true; break; }
            }
            $cols[] = "`" . str_replace("`", "``", $field) . "`";
            $colTypes[$field] = $isBinary;
        }
        $colList = implode(", ", $cols);

        $resCount = $mysqli->query("SELECT COUNT(*) AS cnt FROM `$table`");
        if (!$resCount) {
            fwrite($fp, "-- ERROR COUNT `$table`: " . $mysqli->error . "\n\n");
            continue;
        }
        $totalRows = (int)$resCount->fetch_assoc()['cnt'];

        if ($totalRows === 0) {
            fwrite($fp, "-- `$table` is empty\n\n");
            continue;
        }

        for ($offset = 0; $offset < $totalRows; $offset += $pageSize) {

            $resData = $mysqli->query("SELECT * FROM `$table` LIMIT $offset, $pageSize", MYSQLI_USE_RESULT);
            if (!$resData) {
                fwrite($fp, "-- ERROR SELECT `$table` offset $offset: " . $mysqli->error . "\n\n");
                continue;
            }

            $rowsInBatch = 0;
            $insertHeader = "INSERT INTO `$table` ($colList) VALUES\n";
            $insertLines = [];

            while ($row = $resData->fetch_assoc()) {

                $values = [];
                foreach ($row as $colName => $val) {
                    if ($val === null) {
                        $values[] = "NULL";
                    } else {
                        if (!empty($colTypes[$colName])) {
                            $values[] = "UNHEX('" . bin2hex($val) . "')";
                        } else {
                            $values[] = "'" . $mysqli->real_escape_string($val) . "'";
                        }
                    }
                }

                $insertLines[] = "(" . implode(", ", $values) . ")";
                $rowsInBatch++;

                if ($rowsInBatch >= $chunkSize) {
                    fwrite($fp, $insertHeader . implode(",\n", $insertLines) . ";\n");
                    $insertLines = [];
                    $rowsInBatch = 0;

                    fflush($fp);
                }
            }

            if (!empty($insertLines)) {
                fwrite($fp, $insertHeader . implode(",\n", $insertLines) . ";\n");
                fflush($fp);
            }

            fwrite($fp, "\n");
            $resData->free();
        }

        fwrite($fp, "\n");
    }

    foreach ($views as $view) {
        $resView = $mysqli->query("SHOW CREATE VIEW `$view`");
        if (!$resView) {
            fwrite($fp, "-- ERROR SHOW CREATE VIEW `$view`: " . $mysqli->error . "\n\n");
            continue;
        }
        $rowView = $resView->fetch_row();

        fwrite($fp, "DROP VIEW IF EXISTS `$view`;\n");
        fwrite($fp, $rowView[1] . ";\n\n");
    }

    $trg = $mysqli->query("SHOW TRIGGERS");
    if ($trg) {
        while ($t = $trg->fetch_assoc()) {
            $trgName = $t['Trigger'];
            $resTrg = $mysqli->query("SHOW CREATE TRIGGER `$trgName`");
            if ($resTrg) {
                $ct = $resTrg->fetch_assoc();
                fwrite($fp, "DROP TRIGGER IF EXISTS `$trgName`;\n");
                fwrite($fp, $ct['SQL Original Statement'] . ";\n\n");
            }
        }
    } else {
        fwrite($fp, "-- WARNING: Cannot read triggers: " . $mysqli->error . "\n\n");
    }

    fwrite($fp, "SET FOREIGN_KEY_CHECKS=1;\n");
    fclose($fp);

    // --- آمارگیری برای تلگرام (همون قبلی خودت) ---
    $qry_online = "SELECT COUNT(*) AS count FROM users WHERE online ='online'";
    $total_online = mysqli_fetch_array(mysqli_query($mysqli, $qry_online));
    $total_online = number_format($total_online['count']);

    $qry_offline = "SELECT COUNT(*) AS count FROM users WHERE online = 'offline' AND status = 0";
    $total_offline_result = mysqli_query($mysqli, $qry_offline);
    $total_offline_array = mysqli_fetch_array($total_offline_result);
    $total_offline = number_format($total_offline_array['count']);

    $qry_total = "SELECT SUM(total) AS totalSum FROM users";
    $total_total = mysqli_fetch_array(mysqli_query($mysqli, $qry_total));
    $total_total = number_format(($total_total['totalSum'] / 1024), 2);

    $qry_full = "SELECT ROUND(SUM(total - full), 0) AS result FROM users";
    $total_full = mysqli_fetch_array(mysqli_query($mysqli, $qry_full));
    $total_full = number_format(($total_full['result'] / 1024), 2);

    $qry_servers = "SELECT COUNT(*) as num FROM configs";
    $total_servers = mysqli_fetch_array(mysqli_query($mysqli, $qry_servers));
    $total_servers = $total_servers['num'];

    $qry_users = "SELECT COUNT(*) as num FROM users";
    $total_users = mysqli_fetch_array(mysqli_query($mysqli, $qry_users));
    $total_users = $total_users['num'];

    $caption = "✅ Backup Manual\n";
    $caption .= "🕘 " . $currentDate2 . "\n";
    $caption .= "🟢 Online users: $total_online\n";
    $caption .= "🔴 Offline users: $total_offline\n";
    $caption .= "🛍 Buy GB by users: $total_total GB\n";
    $caption .= "📈 Used GB by users: $total_full GB\n";
    $caption .= "🌐 Servers: $total_servers\n";
    $caption .= "📱 Users: $total_users\n";

    $telegram_token = $settings_row['telegram_token'];
    $telegram_id = $settings_row['telegram_id'];

    try {

        if (!file_exists($filePath) || filesize($filePath) < 100) {
            throw new Exception("Backup file is empty or too small.");
        }

        $postFields = [
            'file' => new CURLFile(realpath($filePath)),
            'telegram_token' => $telegram_token,
            'telegram_id' => $telegram_id,
            'caption' => $caption,
            'relay_secret' => isset($settings_row['tg_relay_secret']) ? $settings_row['tg_relay_secret'] : ''
        ];

        $ch = curl_init($settings_row['link_get']);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_HTTPHEADER => ['Content-Type: multipart/form-data'],
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception("cURL error: " . curl_error($ch));
        }
        curl_close($ch);

        file_put_contents($lastUpdateFile, $currentDate);

        if (file_exists($filePath)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        }

    } catch (Exception $e) {

        error_log("Backup send error: " . $e->getMessage());

        if (file_exists($filePath)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        }

        http_response_code(500);
        exit("Backup failed.");
    }

    exit;
}
if(isset($_POST['import_submit'])) {
    if (isset($_FILES['sql_file']) && $_FILES['sql_file']['error'] == 0) {
        $fileTmpPath = $_FILES['sql_file']['tmp_name'];
        $sqlContent = file_get_contents($fileTmpPath);

        mysqli_query($mysqli, "SET FOREIGN_KEY_CHECKS=0;");

        // حذف جداول موجود
        $result = mysqli_query($mysqli, "SHOW TABLES");
        while ($row = mysqli_fetch_array($result)) {
            mysqli_query($mysqli, "DROP TABLE IF EXISTS `" . $row[0] . "`");
        }

        // اجرای کوئری‌ها خط به خط
        $queries = explode(";\n", $sqlContent);
        foreach ($queries as $query) {
            $query = trim($query);
            if ($query) {
                if (!mysqli_query($mysqli, $query)) {
                    die("خطا در کوئری: ".mysqli_error($mysqli)." | Query: $query");
                }
            }
        }

        mysqli_query($mysqli, "SET FOREIGN_KEY_CHECKS=1;");
        $_SESSION['msg'] = "11";
    } else {
        $_SESSION['msg'] = "فایل بارگذاری نشده است.";
    }

    header("Location: settings.php");
    exit;
}

include("includes/header.php");

if(isset($_POST['guest_submit'])){

    $data = array(
            'app_guest_volume' =>  $_POST['guest_volume'], 
            'api_guest_day'    =>  $_POST['guest_day'],
            'api_guest_status' =>  $_POST['guest_status'],
            'api_guest_opt'    =>  'ALL'
    );
    Update('settings', $data, "WHERE id = '1'");
    $_SESSION['msg']="11";
    header( "Location:settings.php");
    exit;
}

if(isset($_POST['submit'])){
    $img_res=mysqli_query($mysqli,"SELECT * FROM settings WHERE id='1'");
    $img_row=mysqli_fetch_assoc($img_res);
    
    if($_FILES['app_logo']['name']!="")
    {        
        unlink('images/'.$img_row['app_logo']);   
        $app_logo=$_FILES['app_logo']['name'];
        $pic1=$_FILES['app_logo']['tmp_name'];
        $tpath1='images/'.$app_logo;      
        copy($pic1,$tpath1);
          $data = array(
          'app_name'       =>  $_POST['app_name'],
          'app_logo'       =>  $app_logo,  
          'telegram_token' =>  $_POST['telegram_token'],
          'manager_gb'    =>  $_POST['manager_gb'],  
          'manager_day'    =>  $_POST['manager_day'],  
          'manager_multi'    =>  $_POST['manager_multi'],  
          'manager_server'    =>  $_POST['manager_server'],  
          'manager_total'    =>  $_POST['manager_total'],  
          'telegram_id'    =>  $_POST['telegram_id'],                
          'link_get'    =>  $_POST['link_get'],  
          'tg_relay_secret' => isset($_POST['tg_relay_secret']) ? $_POST['tg_relay_secret'] : '',
          'sales_bot_token' => isset($_POST['sales_bot_token']) ? $_POST['sales_bot_token'] : '0',
          'backup_interval'=>  $_POST['backup_interval'],
          );
    }
    else
    {
      $data = array(
          'app_name'       =>  $_POST['app_name'],
          'telegram_token' =>  $_POST['telegram_token'],
          'manager_gb'    =>  $_POST['manager_gb'],  
          'manager_day'    =>  $_POST['manager_day'],  
          'manager_multi'    =>  $_POST['manager_multi'],  
          'manager_server'    =>  $_POST['manager_server'],  
          'manager_total'    =>  $_POST['manager_total'],  
          'telegram_id'    =>  $_POST['telegram_id'],                
          'link_get'    =>  $_POST['link_get'],  
          'tg_relay_secret' => isset($_POST['tg_relay_secret']) ? $_POST['tg_relay_secret'] : '',
          'sales_bot_token' => isset($_POST['sales_bot_token']) ? $_POST['sales_bot_token'] : '0',
          'backup_interval'=>  $_POST['backup_interval'],
      );
    }
    $settings_edit=Update('settings', $data, "WHERE id = '1'");
    $_SESSION['msg']="11";
    header( "Location:settings.php");
    exit;   
}

if(isset($_POST['channel_submit'])){
    $data = array(
            'title_buy'     =>  $_POST['title_buy'],
            'name_buy'      =>  $_POST['name_buy'],
            'title_channel' =>  $_POST['title_channel'],
            'name_channel'  =>  $_POST['name_channel'],
    );
    Update('support', $data, "WHERE id = '1'");
    $_SESSION['msg']="11";
    header( "Location:settings.php");
    exit;
}
?>

<style type="text/css">
  .switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 26px;
  }
  .switch input { opacity: 0; width: 0; height: 0; }
  .slider {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: #ccc;
    -webkit-transition: .4s;
    transition: .4s;
  }
  .slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 5px;
    bottom: 3px;
    background-color: white;
    -webkit-transition: .4s;
    transition: .4s;
  }
  input:checked + .slider { background-color: #e91e63; }
  input:checked + .slider:before { -webkit-transform: translateX(20px); -ms-transform: translateX(20px); transform: translateX(20px); }
  .slider.round { border-radius: 34px; }
  .slider.round:before { border-radius: 50%; }
  
  .info-box {
      padding: 10px;
      margin-top: 5px;
      border-radius: 4px;
      font-size: 13px;
      display: none;
  }
  .info-box.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
  .info-box.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
</style>
<div class="row">
      <div class="col-md-12">
        <div class="card">
      <div class="page_title_block">
            <div class="col-md-5 col-xs-12">
              <div class="page_title">تنظیمات</div>
            </div>
          </div>
          <div class="clearfix"></div>
          <?php if(isset($_SESSION['msg'])){?>
          <div class="row mrg-top">
            <div class="col-md-12">
              <div class="col-md-12 col-sm-12">
                 <div class="alert alert-success alert-dismissible" role="alert"> <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                  <?php echo $client_lang[$_SESSION['msg']] ; ?></a> </div>
              </div>
            </div>
          </div>
          <?php unset($_SESSION['msg']);}?>
          <div class="card-body mrg_bottom">
            <ul class="nav nav-tabs" role="tablist">
                <li role="presentation" class="active"><a href="#app_settings" aria-controls="app_settings" role="tab" data-toggle="tab">تنظیمات اپلیکیشن</a></li>
                <li role="presentation"><a href="#guest_settings" aria-controls="guest_settings" role="tab" data-toggle="tab">تنظیمات مهمان</a></li>
                <li role="presentation"><a href="#users_quota" aria-controls="users_quota" role="tab" data-toggle="tab">مدیریت حجم کاربران</a></li>
                <li role="presentation"><a href="#users_days" aria-controls="users_days" role="tab" data-toggle="tab">مدیریت زمان کاربران</a></li>
                <li role="presentation"><a href="#database_output" aria-controls="database_output" role="tab" data-toggle="tab">پشتیبان دیتابیس</a></li>
                <li role="presentation"><a href="#import_database" aria-controls="import_database" role="tab" data-toggle="tab">وارد کردن دیتابیس</a></li>
            </ul>
           <div class="tab-content">
            <div role="tabpanel" class="tab-pane active" id="app_settings">   
            <form action="" name="settings_from" method="post" class="form form-horizontal" enctype="multipart/form-data">
            <div class="section">
            <div class="section-body">
              <div class="form-group">
                <label class="col-md-3 control-label">نام اپلیکیشن :</label>
                <div class="col-md-6">
                  <input type="text" name="app_name" id="app_name" value="<?php echo $settings_row['app_name'];?>" class="form-control">
                </div>
              </div>
              <?php if($admin_username == "MrCenTury" || $admin_username == "hcentury"){ ?>
              <div class="form-group">
                    <label class="col-md-3 control-label">آدرس سرور ربات :</label>
                    <div class="col-md-6">
                        <input type="text" name="link_get" value="<?php echo $settings_row['link_get']; ?>" style="direction: ltr;" class="form-control" required>
                    </div>
              </div>              
              <?php } ?>
              <div class="form-group">
                    <label class="col-md-3 control-label">توکن ربات تلگرام:</label>
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" name="telegram_token" id="telegram_token" value="<?php echo $settings_row['telegram_token']; ?>" style="direction: ltr;" class="form-control" required>
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-info" onclick="checkTelegramBot()">بررسی</button>
                            </span>
                        </div>
                        <div id="bot_info" class="info-box"></div>
                    </div>
                </div>
              <div class="form-group">
                    <label class="col-md-3 control-label">توکن ربات فروش:</label>
                    <div class="col-md-6">
                        <input type="text" name="sales_bot_token" value="<?php echo isset($settings_row['sales_bot_token']) ? $settings_row['sales_bot_token'] : ''; ?>" style="direction: ltr;" class="form-control" placeholder="توکن ربات فروش (غیر از ربات اعلان)">
                    </div>
              </div>
              <div class="form-group">
                    <label class="col-md-3 control-label">سکرت سرور واسط:</label>
                    <div class="col-md-6">
                        <input type="text" name="tg_relay_secret" value="<?php echo isset($settings_row['tg_relay_secret']) ? $settings_row['tg_relay_secret'] : ''; ?>" style="direction: ltr;" class="form-control" placeholder="اختیاری — همان سکرت فایل رله">
                    </div>
              </div>
              <div class="form-group">
                    <label class="col-md-3 control-label">شناسه کاربر تلگرام:</label>
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="number" name="telegram_id" id="telegram_id" value="<?php echo $settings_row['telegram_id']; ?>" class="form-control" required>
                        </div>
                        <div id="user_info" class="info-box"></div>
                    </div>
                </div>
              <div class="form-group">
                    <label class="col-md-3 control-label">زمان بندی پشتیبان خودکار:</label>
                        <div class="col-md-6">
                        <select name="backup_interval" id="backup_interval" class="select2">
                            <option value="1" <?php if($settings_row['backup_interval']=='1'){?>selected<?php }?>>1 ساعت</option>
                            <option value="3" <?php if($settings_row['backup_interval']=='3'){?>selected<?php }?>>3 ساعت</option>
                            <option value="6" <?php if($settings_row['backup_interval']=='6'){?>selected<?php }?>>6 ساعت</option>
                            <option value="12" <?php if($settings_row['backup_interval']=='12'){?>selected<?php }?>>12 ساعت</option>
                            <option value="24" <?php if($settings_row['backup_interval']=='24'){?>selected<?php }?>>1 روز</option>
                            <option value="72" <?php if($settings_row['backup_interval']=='72'){?>selected<?php }?>>3 روز</option>
                            <option value="168" <?php if($settings_row['backup_interval']=='168'){?>selected<?php }?>>1 هفته</option>
                            <option value="336" <?php if($settings_row['backup_interval']=='336'){?>selected<?php }?>>2 هفته</option>
                            <option value="720" <?php if($settings_row['backup_interval']=='720'){?>selected<?php }?>>1 ماه</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">قیمت هر گیگ حجم همکار :</label>
                    <div class="col-md-6">
                        <input type="number" name="manager_gb" value="<?php echo $settings_row['manager_gb']; ?>" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">قیمت هر روز همکار :</label>
                    <div class="col-md-6">
                        <input type="number" name="manager_day" value="<?php echo $settings_row['manager_day']; ?>" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">قیمت هرکاربر جدید همکار :</label>
                    <div class="col-md-6">
                        <input type="number" name="manager_multi" value="<?php echo $settings_row['manager_multi']; ?>" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">قیمت ساخت کاربر همکار :</label>
                    <div class="col-md-6">
                        <input type="number" name="manager_total" value="<?php echo $settings_row['manager_total']; ?>" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">قیمت ساخت سرور همکار :</label>
                    <div class="col-md-6">
                        <input type="number" name="manager_server" value="<?php echo $settings_row['manager_server']; ?>" class="form-control" required>
                    </div>
                </div>
              <div class="form-group">
                <label class="col-md-3 control-label">لوگو اپلیکیشن :</label>
                <div class="col-md-6">
                  <div class="fileupload_block">
                    <input type="file" name="app_logo" id="fileupload">
                      <?php if($settings_row['app_logo']!="") {?>
                        <div class="fileupload_img"><img type="image" src="images/<?php echo $settings_row['app_logo'];?>" alt="image" /></div>
                      <?php } else {?>
                        <div class="fileupload_img"><img type="image" src="assets/images/add-image.png" alt="image" /></div>
                      <?php }?>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <div class="col-md-9 col-md-offset-3">
                  <button type="submit" name="submit" class="btn btn-primary">ذخیره</button>
                </div>
              </div>
            </div>
            </div>
            </form>
            </div>
            <div role="tabpanel" class="tab-pane" id="guest_settings">
            <form action="" name="guest_settings" method="post" class="form form-horizontal" enctype="multipart/form-data">
            <div class="section">
              <div class="section-body">
                <div class="form-group">
                <label class="col-md-3 control-label">حجم اولیه کاربر (گیگابایت):</label>
                <div class="col-md-6">
                  <input type="text" name="guest_volume" id="guest_volume" value="<?php echo $settings_row['app_guest_volume'];?>" class="form-control">
                </div>
              </div>    
               <div class="form-group">
                <label class="col-md-3 control-label">زمان اکانت مهمان :</label>
                <div class="col-md-6">
                  <input type="number" name="guest_day" id="guest_day" value="<?php echo $settings_row['api_guest_day'];?>" class="form-control">
                </div>
              </div>    
            <div class="form-group">
                <label class="col-md-3 control-label">وضعیت ورود مهمان</label>
                    <div class="col-md-6">
                    <select name="guest_status" id="guest_status" class="select2">
                      <option value="0" <?php if($settings_row['api_guest_status']=='0'){?>selected<?php }?>>خاموش</option>
                      <option value="1" <?php if($settings_row['api_guest_status']=='1'){?>selected<?php }?>>روشن</option>
                    </select>
                </div>
            </div>
                <div class="form-group">
                <div class="col-md-9 col-md-offset-3">
                  <button type="submit" name="guest_submit" class="btn btn-primary">ذخیره</button>
                </div>
                </div>
              </div>
            </div>
            </form>
            </div>
            
<!-- تب مدیریت حجم کاربران -->
<div role="tabpanel" class="tab-pane" id="users_quota">
    <div class="section">
        <div class="section-body">
            <div class="row">

                <!-- کاربران مهمان -->
                <div class="col-md-6">
                    <div class="alert alert-info">
                        <strong>کاربران مهمان (Free)</strong>
                    </div>

                    <form method="post" class="form form-horizontal">

                        <div class="form-group">
                            <label class="col-md-4 control-label">نوع عملیات:</label>
                            <div class="col-md-8">
                                <select name="quota_action_guest" class="form-control">
                                    <option value="add">افزودن به حجم فعلی</option>
                                    <option value="set">جایگزینی (تنظیم کل حجم)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-md-4 control-label">حجم (گیگابایت):</label>
                            <div class="col-md-8">
                                <input type="number"
                                       name="total_quota_gb"
                                       class="form-control"
                                       placeholder="مثلا 1"
                                       min="1"
                                       required>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-md-12">
                                <button type="submit"
                                        name="update_guest_quota"
                                        value="1"
                                        class="btn btn-primary btn-block">
                                    بروزرسانی حجم مهمانان
                                </button>
                            </div>
                        </div>

                    </form>
                </div>


                <!-- کاربران اختصاصی -->
                <div class="col-md-6">
                    <div class="alert alert-warning">
                        <strong>کاربران اختصاصی (Pro)</strong>
                    </div>

                    <form method="post" class="form form-horizontal">

                        <div class="form-group">
                            <label class="col-md-4 control-label">نوع عملیات:</label>
                            <div class="col-md-8">
                                <select name="quota_action_pro" class="form-control">
                                    <option value="add">افزودن به حجم فعلی</option>
                                    <option value="set">جایگزینی (تنظیم کل حجم)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-md-4 control-label">حجم (گیگابایت):</label>
                            <div class="col-md-8">
                                <input type="number"
                                       name="total_quota_gb_pro"
                                       class="form-control"
                                       placeholder="مثلا 1"
                                       min="1"
                                       required>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-md-12">
                                <button type="submit"
                                        name="update_permium_quota"
                                        value="1"
                                        class="btn btn-danger btn-block">
                                    بروزرسانی حجم اختصاصی
                                </button>
                            </div>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

            <!-- تب مدیریت زمان کاربران -->
            <div role="tabpanel" class="tab-pane" id="users_days">
                <form action="" method="post" class="form form-horizontal">
                    <div class="section">
                        <div class="section-body">
                            <div class="row">
                                <!-- بخش کاربران مهمان -->
                                <div class="col-md-6">
                                    <div class="alert alert-info">
                                        <strong>کاربران مهمان (Free)</strong>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">نوع عملیات:</label>
                                        <div class="col-md-8">
                                            <select name="days_action" class="form-control">
                                                <option value="add">افزودن به روزهای فعلی</option>
                                                <option value="set">جایگزینی (تعیین کل روزها)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">تعداد روز:</label>
                                        <div class="col-md-8">
                                            <input type="number" name="days_count" class="form-control" placeholder="مثلا 30" min="1">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-md-12">
                                            <input type="hidden" name="update_guest_days" value="1">
                                            <button type="submit" name="update_users_days" class="btn btn-primary btn-block">بروزرسانی زمان مهمانان</button>
                                        </div>
                                    </div>
                                </div>

                                <!-- بخش کاربران اختصاصی -->
                                <div class="col-md-6">
                                    <div class="alert alert-warning">
                                        <strong>کاربران اختصاصی (Pro)</strong>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">نوع عملیات:</label>
                                        <div class="col-md-8">
                                            <select name="days_action_pro" class="form-control">
                                                <option value="add">افزودن به روزهای فعلی</option>
                                                <option value="set">جایگزینی (تعیین کل روزها)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">تعداد روز:</label>
                                        <div class="col-md-8">
                                            <input type="number" name="days_count_pro" class="form-control" placeholder="مثلا 30" min="1">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-md-12">
                                            <input type="hidden" name="update_permium_days" value="1">
                                            <button type="submit" name="update_users_days" class="btn btn-danger btn-block">بروزرسانی زمان اختصاصی</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div role="tabpanel" class="tab-pane" id="database_output">
                <form action="" method="post" class="form form-horizontal">
                    <div class="section">
                        <div class="section-body">
                            <div class="form-group">
                                <label class="col-md-3 control-label">پشتیبان جدید:</label>
                                <div class="col-md-6">
                                    <button type="submit" name="fetch_database_output" class="btn btn-primary">
                                        دریافت جدیدترین پشتیبان کامل
                                    </button>
                                </div>
                            </div>
                            <br>
                           
                            <div class="form-group">
                                <label class="col-md-3 control-label">آخرین پشتیبان‌های گرفته شده:</label>
                                <div class="col-md-6">
                                    <?php
                                    $backupFiles = glob($backupDir . "*.sql");
                                    if (count($backupFiles) > 0) {
                                        usort($backupFiles, function($a, $b) {
                                            return filemtime($b) - filemtime($a);
                                        });
                                        for ($i = 0; $i < min(3, count($backupFiles)); $i++) {
                                            $filePath = $backupFiles[$i];
                                            $fileName = basename($filePath);
                                            $fileDate = str_replace([$settings_row['app_name']."_", ".sql"], "", $fileName);
                                            $dateParts = explode(" ", $fileDate);
                                            $timeFormatted = isset($dateParts[1]) ? str_replace("-", ":", $dateParts[1]) : "";
                                            $formattedDisplayName = $dateParts[0] . " " . $timeFormatted;
                                            
                                            $displayName = ($i == 0) ? "آخرین بکاپ" : (($i == 1) ? "بکاپ قبلی" : "بکاپ سوم");
                                            
                                            echo '<a href="'.$filePath.'" class="btn btn-success" download>'.$displayName.'</a>';
                                            echo '<div class="backup-date" style="border: 1px solid #ddd; padding: 10px; margin-top: 5px; border-radius: 5px; display: inline-block;direction: ltr;">' . $formattedDisplayName . '</div><br><br>';
                                        }
                                    } else {
                                        echo "<div class='alert alert-warning'>هیچ بکاپی یافت نشد.</div>";
                                    }
                                    ?>
                                </div>
                            </div>
                           
                        </div>
                    </div>
                </form>
                </div>  
            <div role="tabpanel" class="tab-pane" id="import_database">
                <form action="" name="import_database" method="post" class="form form-horizontal" enctype="multipart/form-data">
                    <div class="section">
                        <div class="section-body">
                            <div class="form-group">
                                <label class="col-md-3 control-label">فایل SQL را بارگذاری کنید:</label>
                                <div class="col-md-6">
                                    <input type="file" name="sql_file" class="form-control" accept=".sql" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-md-9 col-md-offset-3">
                                    <button type="submit" name="import_submit" class="btn btn-primary">وارد کردن دیتابیس</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                <?php if (isset($_SESSION['msg'])): ?>
                    <div class="alert alert-info"><?php echo $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
                <?php endif; ?>
             </div>
            </div>   
          </div>
        </div>
      </div>
    </div>
<script type="text/javascript">
  $('a[data-toggle="tab"]').on('show.bs.tab', function(e) {
    localStorage.setItem('activeTab', $(e.target).attr('href'));
  });
  var activeTab = localStorage.getItem('activeTab');
  if(activeTab){
    $('.nav-tabs a[href="' + activeTab + '"]').tab('show');
  }

  function checkTelegramBot() {
      var token = document.getElementById('telegram_token').value;
      var infoBox = document.getElementById('bot_info');
      
      if(!token) {
          infoBox.className = 'info-box error';
          infoBox.style.display = 'block';
          infoBox.innerHTML = 'لطفا توکن را وارد کنید';
          return;
      }

      infoBox.className = 'info-box';
      infoBox.style.display = 'block';
      infoBox.innerHTML = 'در حال بررسی...';

      $.ajax({
          url: 'settings.php',
          type: 'POST',
          data: {check_telegram_api: 1, token: token},
          dataType: 'json',
          success: function(response) {
              if(response.status === 'success') {
                  infoBox.className = 'info-box success';
                  infoBox.innerHTML = '<strong>نام ربات:</strong> ' + response.first_name + '<br><strong>نام کاربری:</strong> ' + response.username;
              } else {
                  infoBox.className = 'info-box error';
                  infoBox.innerHTML = response.message;
              }
          },
          error: function() {
              infoBox.className = 'info-box error';
              infoBox.innerHTML = 'خطا در ارتباط با سرور';
          }
      });
  }

</script>  
<?php include("includes/footer.php");?>