<?php
require_once __DIR__ . "/../includes/connection.php";
date_default_timezone_set('Asia/Tehran');

function getSetting($mysqli, $key) {
    $query = "SELECT $key FROM settings WHERE id='1'";
    $result = mysqli_query($mysqli, $query);
    if (!$result) {
        throw new Exception("Failed to get setting: " . mysqli_error($mysqli));
    }
    $row = mysqli_fetch_assoc($result);
    return $row[$key];
}

function updateSetting($mysqli, $key, $value) {
    $value = mysqli_real_escape_string($mysqli, $value);
    $query = "UPDATE settings SET $key='$value' WHERE id='1'";
    if (!mysqli_query($mysqli, $query)) {
        throw new Exception("Failed to update setting: " . mysqli_error($mysqli));
    }
}

try {
    $backup_interval = (int)getSetting($mysqli, 'backup_interval');
    $telegram_token = getSetting($mysqli, 'telegram_token');
    $telegram_id = getSetting($mysqli, 'telegram_id');
    $link_get = getSetting($mysqli, 'link_get');
    $last_backup_time = strtotime(getSetting($mysqli, 'last_backup_time'));
    
    $current_time = time();
    $interval_seconds = $backup_interval * 3600;
    $next_backup_time = $last_backup_time + $interval_seconds;
    
    echo "Last Backup Time: " . date('Y-m-d H:i:s', $last_backup_time) . "<br>";
    echo "Current Time: " . date('Y-m-d H:i:s', $current_time) . "<br>";
    echo "Backup Interval: $backup_interval hours ($interval_seconds seconds)<br>";
    echo "Next Backup Time: " . date('Y-m-d H:i:s', $next_backup_time) . "<br>";
    
    if ($current_time >= $next_backup_time) {
        $backupDir = "AutoBackupDB/";
        if (!is_dir($backupDir)) {
            if (!mkdir($backupDir, 0777, true)) {
                throw new Exception("Failed to create backup directory");
            }
        }
        
        $stats = [];
        $queries = [
            'online' => "SELECT COUNT(*) AS count FROM users WHERE online='online'",
            'offline' => "SELECT COUNT(*) AS count FROM users WHERE online='offline' AND status=0",
            'total' => "SELECT SUM(total) AS totalSum FROM users",
            'full' => "SELECT ROUND(SUM(total - full), 0) AS result FROM users",
            'servers' => "SELECT COUNT(*) as num FROM configs",
            'users' => "SELECT COUNT(*) as num FROM users"
        ];
        
        foreach ($queries as $key => $query) {
            $result = mysqli_query($mysqli, $query);
            if (!$result) {
                throw new Exception("Failed to get $key stats: " . mysqli_error($mysqli));
            }
            $row = mysqli_fetch_assoc($result);
            $stats[$key] = number_format(current($row));
        }
        
        $backupFiles = glob($backupDir . "backup_*.sql");
        if ($backupFiles && count($backupFiles) > 2) {
            usort($backupFiles, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            $filesToDelete = array_slice($backupFiles, 0, count($backupFiles) - 2);
            foreach ($filesToDelete as $file) {
                if (file_exists($file) && is_writable($file)) {
                    unlink($file);
                }
            }
        }
        
        $currentDate = date("Y-m-d H-i-s");
        $currentDate2 = date("Y-m-d H:i:s");
        $filename = "backup_" . $currentDate . ".sql";
        $filePath = $backupDir . $filename;
        
        $output = "";
        $tables = [];
        $result = mysqli_query($mysqli, "SHOW TABLES");
        while ($row = $result->fetch_row()) {
            $tables[] = $row[0];
        }
        
        foreach ($tables as $table) {
            $output .= "DROP TABLE IF EXISTS `$table`;\n";
            $createResult = mysqli_query($mysqli, "SHOW CREATE TABLE `$table`");
            $row = $createResult->fetch_row();
            $output .= $row[1] . ";\n\n";
            
            $dataResult = mysqli_query($mysqli, "SELECT * FROM `$table`");
            while ($row = $dataResult->fetch_assoc()) {
                $values = array_map(function($value) use ($mysqli) {
                    if ($value === null) {
                        return "NULL";
                    }
                
                    return "'" . mysqli_real_escape_string($mysqli, (string)$value) . "'";
                }, array_values($row));
                $output .= "INSERT INTO `$table` VALUES (" . implode(", ", $values) . ");\n";
            }
            $output .= "\n";
        }
        
        if (file_put_contents($filePath, $output) === false) {
            throw new Exception("Failed to write backup file");
        }
        
        $caption = "✅ Automatic backup\n";
        $caption .= "🕘 " . $currentDate2 . "\n";
        $caption .= "🟢 Online users: {$stats['online']}\n";
        $caption .= "🔴 Offline users: {$stats['offline']}\n";
        $caption .= "🛍 Buy GB by users: {$stats['total']} GB\n";
        $caption .= "📈 Used GB by users: {$stats['full']} GB\n";
        $caption .= "🌐 Servers: {$stats['servers']}\n";
        $caption .= "📱 Users: {$stats['users']}\n";
        
        $postFields = [
            'file' => new CURLFile(realpath($filePath)),
            'telegram_token' => $telegram_token,
            'telegram_id' => $telegram_id,
            'caption' => $caption,
            'relay_secret' => getSetting($mysqli, 'tg_relay_secret')
        ];
        
        $ch = curl_init($link_get);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: multipart/form-data',
            ],
        ]);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL error: " . $error);
        }
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode >= 400) {
            curl_close($ch);
            throw new Exception("HTTP request failed with status code: " . $httpCode);
        }
        
        curl_close($ch);
        
        $responseData = json_decode($response, true);
        if (!$responseData || !isset($responseData['success']) || !$responseData['success']) {
            throw new Exception("Failed to send backup to endpoint: " . ($responseData['message'] ?? 'Unknown error'));
        }
        
        updateSetting($mysqli, 'last_backup_time', date('Y-m-d H:i:s', $current_time));
        
        echo "✅ Backup created and sent successfully at " . date('Y-m-d H:i:s') . "<br>";
    } else {
        $remaining = $next_backup_time - $current_time;
        echo "⏳ Next backup in: " . gmdate("H:i:s", $remaining) . "<br>";
        exit;
    }
    
} catch (Exception $e) {
    error_log("Backup Error: " . $e->getMessage());
    echo "❌ Error: " . $e->getMessage() . "<br>";
} finally {
    if (isset($mysqli)) {
        mysqli_close($mysqli);
    }
}
?>