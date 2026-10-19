<?php
define("DB_SERVER", "localhost");
define("DB_USERNAME", "muwizw_dd");
define("DB_PASSWORD", "m^w6.Ps;BX^uOE}S");
define("DB_DATABASE", "muwizw_dd");
define('GOOGLE_API_KEY_JSON', 'account.json');

$db = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
mysqli_set_charset($db, "utf8mb4");
date_default_timezone_set('Asia/Tehran');

// ─── سوییچ سراسری لاگ دیباگ ──────────────────────────────────────────────────
define('DEBUG_LOG', true);

function dbgLog(string $msg, string $file = 'debug'): void {
    if (!DEBUG_LOG) return;
    $path = __DIR__ . "/{$file}.log";
    if (file_exists($path) && filemtime($path) < strtotime(date('Y-m-d 00:00:00'))) {
        @unlink($path);
    }
    file_put_contents($path, "[" . date('Y-m-d H:i:s') . "] " . $msg . PHP_EOL, FILE_APPEND | LOCK_EX);
}

// ─── احراز هویت چرخشی درخواست (مدل TOTP) ─────────────────────────────────────
define('API_SECRET', '9f2c7a1e5b8d34f6a0c9e28b7d1450f3e6a9b2c8d4f7013a5e8c9b0d2f4a6178');
define('API_AUTH_ENFORCE', false);
define('API_AUTH_WINDOW', 300);

function apiAuthOk(string $ts, string $sig, string $method): bool {
    if ($ts === '' || $sig === '' || !ctype_digit($ts)) return false;
    if (abs(time() - (int)$ts) > API_AUTH_WINDOW) return false;
    $expected = hash_hmac('sha256', $ts . '|' . $method, API_SECRET);
    return hash_equals($expected, $sig);
}

$db->query("CREATE TABLE IF NOT EXISTS `device_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(190) NOT NULL,
    `deviceId` VARCHAR(190) NOT NULL,
    `model` VARCHAR(190) DEFAULT '',
    `token` TEXT,
    `status` VARCHAR(20) NOT NULL DEFAULT 'kicked',
    `reject_count` INT NOT NULL DEFAULT 0,
    `updated_at` DATETIME DEFAULT NULL,
    UNIQUE KEY `uq_user_dev` (`username`,`deviceId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// 	ini_set('display_errors', 1);
//     ini_set('display_startup_errors', 1);
//     error_reporting(E_ALL);
    
// ─── Input parsing ────────────────────────────────────────────────────────────
$method   = $_POST['method']   ?? '';

// ─── گیت احراز هویت چرخشی ────────────────────────────────────────────────────
if (API_AUTH_ENFORCE && $method !== 'usage') {
    $__ts  = (string)($_POST['ts']  ?? '');
    $__sig = (string)($_POST['sig'] ?? '');
    if (!apiAuthOk($__ts, $__sig, $method)) {
        http_response_code(403);
        echo json_encode(["result" => "error", "msg" => "unauthorized"]);
        exit;
    }
}

$username = htmlspecialchars($_POST['username'] ?? '');
$password = htmlspecialchars($_POST['password'] ?? '');
$usage    = htmlspecialchars($_POST['usage']    ?? '');
$idServer = htmlspecialchars($_POST['idServer'] ?? '');

$device = !empty($_POST["deviceIMEI"])
    ? htmlspecialchars($_POST["deviceIMEI"])
    : "Unknown Device ID";

$Model = !empty($_POST["deviceModel"])
    ? htmlspecialchars($_POST["deviceModel"])
    : "Unknown Model";

$Token = !empty($_POST["deviceToken"])
    ? htmlspecialchars($_POST["deviceToken"])
    : "No Token";

$Config = $_POST["deviceConfig"] ?? '';

$IMEI2      = trim($device);
$IMEI3      = preg_replace('/\s+/', '', $IMEI2);
$deviceIMEI = str_replace("-", "", $IMEI3);
$time         = time();
$current_time = date('Y-m-d H:i:s');

function persianToEnglishNumbers($string) {
    $persianNumbers = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    $englishNumbers = range(0, 9);
    return str_replace($persianNumbers, $englishNumbers, $string);
}
$username = persianToEnglishNumbers($username);
$password = persianToEnglishNumbers($password);

// ─── Lazy global table getters ────────────────────────────────────────────────

function getSupport(mysqli $db): array {
    static $cache = null;
    if ($cache === null) {
        $res   = $db->query("SELECT * FROM support");
        $cache = $res ? ($res->fetch_assoc() ?? []) : [];
    }
    return $cache;
}

function getSettings(mysqli $db): array {
    static $cache = null;
    if ($cache === null) {
        $res   = $db->query("SELECT app_name, app_logo, api_guest_status, api_guest_day, app_guest_volume, api_guest_opt FROM settings");
        $cache = $res ? ($res->fetch_assoc() ?? []) : [];
    }
    return $cache;
}

function getApplication(mysqli $db): array {
    static $cache = null;
    if ($cache === null) {
        $res   = $db->query("SELECT * FROM application");
        $cache = $res ? ($res->fetch_assoc() ?? []) : [];
    }
    return $cache;
}

// ─── FCM / Google helpers ─────────────────────────────────────────────────────

function getAccessToken(): string {
    $cacheFile = sys_get_temp_dir() . '/fcm_token_' . md5(GOOGLE_API_KEY_JSON) . '.json';

    if (is_readable($cacheFile)) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if (!empty($cached['token']) && isset($cached['expires']) && $cached['expires'] > time() + 60) {
            return $cached['token'];
        }
    }

    $key         = json_decode(file_get_contents(GOOGLE_API_KEY_JSON), true);
    $clientEmail = $key['client_email'];
    $privateKey  = $key['private_key'];
    $scope       = 'https://www.googleapis.com/auth/firebase.messaging';

    $header  = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $payload = base64_encode(json_encode([
        'iss'   => $clientEmail,
        'scope' => $scope,
        'aud'   => 'https://oauth2.googleapis.com/token',
        'iat'   => time(),
        'exp'   => time() + 3600,
    ]));

    $sig = '';
    openssl_sign("$header.$payload", $sig, $privateKey, OPENSSL_ALGO_SHA256);
    $jwt = "$header.$payload." . base64_encode($sig);

    $response = file_get_contents('https://oauth2.googleapis.com/token', false, stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]),
        ],
    ]));

    $data  = json_decode($response, true);
    $token = $data['access_token'] ?? '';

    if ($token) {
        file_put_contents($cacheFile, json_encode([
            'token'   => $token,
            'expires' => time() + 3600,
        ]), LOCK_EX);
    }

    return $token;
}

function sendFCMNotification($to, $title, $body) {
    // اعتبارسنجی توکن
    if (empty($to) || $to == "Token error" || strlen($to) < 10) {
        error_log("Invalid FCM token: $to");
        return ['error' => 'Invalid token'];
    }

    try {
        $accessToken = getAccessToken();
        if (empty($accessToken)) {
            error_log("Failed to get FCM access token");
            return ['error' => 'No access token'];
        }

        $fields = [
            'message' => [
                'token'        => $to,
                'notification' => ['title' => $title, 'body' => $body],
            ],
        ];

        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Authorization: Bearer $accessToken\r\nContent-Type: application/json",
                'content' => json_encode($fields),
                'timeout' => 5, // 5 ثانیه timeout
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ];

        $context = stream_context_create($options);
        $response = file_get_contents(
            'https://fcm.googleapis.com/v1/projects/vnapps-0012/messages:send',
            false,
            $context
        );

        if ($response === false) {
            $error = error_get_last();
            error_log("FCM HTTP request failed: " . print_r($error, true));
            return ['error' => 'HTTP request failed'];
        }

        $decoded = json_decode($response, true);
        if (isset($decoded['error'])) {
            error_log("FCM API error: " . json_encode($decoded['error']));
            return ['error' => $decoded['error']['message'] ?? 'Unknown FCM error'];
        }

        return $decoded ?? [];
    } catch (Exception $e) {
        error_log("FCM Exception: " . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}
// ─── Utility helpers ──────────────────────────────────────────────────────────

function futureDate($days) {
    $today = new DateTime();
    $today->add(new DateInterval("P{$days}D"));
    return $today->format('Y-m-d H:i:s');
}

function generateRandomString($length = 6) {
    $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $len        = strlen($characters);
    $out        = '';
    for ($i = 0; $i < $length; $i++) {
        $out .= $characters[random_int(0, $len - 1)];
    }
    return $out;
}

function extractNumber($string) {
    $str   = persianToEnglishNumbers($string);
    $parts = explode(' ', $str);
    if (isset($parts[1]) && strtoupper($parts[1]) == 'MB') {
        return intval($parts[0]);
    }
    return 0;
}

function days_left($expiry_date) {
    if (empty($expiry_date) || $expiry_date == '0000-00-00') {
        return 0;
    }

    $tz = new DateTimeZone('Asia/Tehran');

    $now = new DateTime('now', $tz);
    $expiry = new DateTime($expiry_date, $tz);

    if ($expiry <= $now) {
        return 0;
    }

    $seconds = $expiry->getTimestamp() - $now->getTimestamp();

    return (int) ceil($seconds / 86400);
}

function encryptData($data) {
    $key              = "3154b04f1932a28423f1e86bf94657886e99b057b19b79193051e067148fa536";
    $iv               = openssl_random_pseudo_bytes(16);
    $encryptedData    = openssl_encrypt($data, "AES-256-CBC", $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $encryptedData);
}

function decryptConfig($encryptedConfigData, $keyString) {
    try {
        $key            = substr($keyString, 0, 32);
        $encryptedBytes = base64_decode($encryptedConfigData);
        $iv             = substr($encryptedBytes, 0, 16);
        $encryptedData  = substr($encryptedBytes, 16);
        $decryptedData  = openssl_decrypt($encryptedData, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return $decryptedData;
    } catch (Exception $e) {
        error_log("Decryption error: " . $e->getMessage());
        return null;
    }
}

function convertNumbersToStrings($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = convertNumbersToStrings($value);
        }
        return $data;
    } elseif (is_numeric($data)) {
        return (string)$data;
    } elseif (is_float($data)) {
        return (string)$data;
    }
    return $data;
}

function jsonOut($data) {

    $data = convertNumbersToStrings($data);

    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function checkDeviceRequest(mysqli $db, string $username, string $deviceId): ?array {
    if ($username === '' || $deviceId === '') return null;
    $stmt = $db->prepare("SELECT status, reject_count FROM device_requests WHERE username = ? AND deviceId = ?");
    $stmt->bind_param('ss', $username, $deviceId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

// ─── Method dispatch ──────────────────────────────────────────────────────────

if ($method == "login") {

    if (empty($deviceIMEI) || empty($Model) || empty($Token)) {
        echo json_encode(["result" => "error", "msg" => "مقادیر اجباری وارد نشده"]);
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->bind_param('ss', $username, $password);
    $stmt->execute();
    $userResult = $stmt->get_result();
    $userRow    = $userResult->fetch_assoc();
    $userCount  = $userResult->num_rows;

    if ($userCount != 1) {
        echo json_encode(["result" => "current"]);
        exit;
    }

    if ($userRow['active'] != "active") {
        echo json_encode(["result" => "notActive"]);
        exit;
    }

    // ─── سیستم kick/بازگشت ───────────────────────────────────────────────
    $approvedBypass = false;
    $dreq = checkDeviceRequest($db, $username, $deviceIMEI);
    if ($dreq) {
        if ($dreq['status'] === 'blocked') {
            echo json_encode(["result" => "blocked", "msg" => "این دستگاه به‌طور دائم مسدود شده است"]);
            exit;
        } elseif ($dreq['status'] === 'approved') {
            $d = $db->prepare("DELETE FROM device_requests WHERE username = ? AND deviceId = ?");
            $d->bind_param('ss', $username, $deviceIMEI);
            $d->execute();
            $approvedBypass = true;
        } else { // kicked | pending | rejected
            echo json_encode(["result" => "waiting", "status" => $dreq['status'], "reject_count" => (int)$dreq['reject_count']]);
            exit;
        }
    }

    $days_left = days_left($userRow['date']);
    $total_r   = $userRow['total'] * 1024;

    $stmt = $db->prepare("SELECT * FROM devices WHERE username = ? AND deviceId = ?");
    $stmt->bind_param('ss', $username, $deviceIMEI);
    $stmt->execute();
    $deviceResult = $stmt->get_result();
    $deviceCount  = $deviceResult->num_rows;

    $stmt = $db->prepare("SELECT COUNT(*) as active_devices FROM devices WHERE username = ? AND deviceId != ?");
    $stmt->bind_param('ss', $username, $deviceIMEI);
    $stmt->execute();
    $activeDevicesRow = $stmt->get_result()->fetch_assoc();

    // ─── مهاجرت/self-heal بدون اخراج کاربر ───────────────────────────────
    $rebound = false;
    if (!$approvedBypass && $deviceCount == 0 && (int)$activeDevicesRow['active_devices'] >= (int)$userRow['multi']) {

        $tokenUsable = !empty($Token)
            && !in_array($Token, ['No Token', 'Empty token', 'Token error', 'Push init error'], true)
            && strlen($Token) >= 20;

        if ($tokenUsable) {
            $stmtRe = $db->prepare("UPDATE devices SET deviceId = ?, model = ? WHERE username = ? AND token = ?");
            $stmtRe->bind_param('ssss', $deviceIMEI, $Model, $username, $Token);
            $stmtRe->execute();

            if ($stmtRe->affected_rows > 0) {
                $rebound     = true;
                $deviceCount = 1;
            }
        }

        if (!$rebound) {
            echo json_encode(["result" => "duplicate", "msg" => "ظرفیت ورود به این اکانت تکمیل شده"]);
            exit;
        }
    }

    if ($deviceCount > 0) {
        $stmt = $db->prepare("UPDATE devices SET token = ? WHERE deviceId = ?");
        $stmt->bind_param('ss', $Token, $deviceIMEI);
        $stmt->execute();
    } else {
        $stmt = $db->prepare("INSERT INTO devices (username, deviceId, model, token) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $username, $deviceIMEI, $Model, $Token);
        $stmt->execute();

        $stmt = $db->prepare("UPDATE users SET is_login = is_login + 1, login = 'true' WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
    }

    if ($userRow['date'] == '0000-00-00') {
        $adate = date('Y-m-d H:i:s', strtotime('+' . $userRow['adate'] . ' day'));
        $stmt  = $db->prepare("UPDATE users SET date = ? WHERE username = ?");
        $stmt->bind_param('ss', $adate, $username);
        $stmt->execute();
    }

    if ($userRow['date_login'] == '0000-00-00') {
        $date_login = date('Y-m-d');
        $stmt = $db->prepare("UPDATE users SET date_login = ? WHERE username = ?");
        $stmt->bind_param('ss', $date_login, $username);
        $stmt->execute();
    }

    if ($days_left == 0 && $userRow['date'] != '0000-00-00') {
        echo json_encode(["result" => "EndDay", "users" => $userRow, "days" => $days_left]);
        exit;
    } elseif ($userRow['size'] >= $total_r) {
        echo json_encode(["result" => "EndGB", "users" => $userRow, "days" => $days_left]);
        exit;
    }

    echo json_encode(["result" => "success", "users" => $userRow, "days" => $days_left]);

}

elseif ($method == "login_new") {

    $lnLogFile = __DIR__ . '/login_new_debug.log';

    function lnLog($text) {
        if (!DEBUG_LOG) return;
        global $lnLogFile;
        if (file_exists($lnLogFile)) {
            $fileTime   = filemtime($lnLogFile);
            $todayStart = strtotime(date('Y-m-d 00:00:00'));
            if ($fileTime < $todayStart) {
                @unlink($lnLogFile);
            }
        }
        file_put_contents(
            $lnLogFile,
            "[" . date("Y-m-d H:i:s") . "] " . $text . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }

    function jsonResponse($result, $users = null, $settings = null, $days = null, $msg = null) {
        lnLog("result=$result" . ($msg ? " msg=$msg" : ''));
        $response           = new stdClass();
        $response->result   = $result;
        $response->users    = $users;
        $response->settings = $settings;
        $response->days     = $days;
        $response->msg      = $msg;
        echo json_encode($response);
        exit;
    }

    // یک خط خلاصه به‌ازای هر درخواست: شناسه، مدل، وضعیت توکن.
    lnLog("REQ imei=$deviceIMEI model=$Model tok=" . (strlen($Token) >= 20 ? 'ok' : "bad($Token)"));

    if (empty($deviceIMEI) || empty($Model)) {
        jsonResponse("error", null, null, null, "مقادیر اجباری وارد نشده");
    }

    $NON_UNIQUE_TOKENS = [
        'No Token',
        'Empty token',
        'Token error',
        'Push init error',
        // 'HONOR_CONSTANT_TOKEN_HERE', // ← رشته‌ی ثابت هونور را اینجا بگذار
    ];

    $tokenInvalid = empty($Token)
        || in_array($Token, $NON_UNIQUE_TOKENS, true)
        || strlen($Token) < 20;

    if ($tokenInvalid) {
        $guestKey = $deviceIMEI;
    } else {
        $guestKey = $deviceIMEI . '|' . md5($Token);
    }

    $stmt = $db->prepare("SELECT * FROM `users` WHERE deviceIMEI = ? AND owner = 'APP'");
    $stmt->bind_param("s", $guestKey);
    $stmt->execute();
    $result = $stmt->get_result();
    $row    = $result->fetch_assoc();
    $count  = $result->num_rows;

    if ($count >= 1) {
        lnLog("MATCH user=" . ($row['username'] ?? '-')
            . " active=" . var_export($row['active'] ?? null, true)
            . " date=" . ($row['date'] ?? '-')
            . " is_login=" . ($row['is_login'] ?? '-'));
    } else {
        lnLog("NO match -> guest creation");
    }

    if ($count === 1) {
        $rowusername = $row["username"];
        $day         = $row["date"];
        $days_left   = days_left($day);
        $total_r     = $row['total'] * 1024;

        $is_login   = $row['is_login'] + 1;
        $stmtUpdate = $db->prepare("UPDATE users SET is_login = ?, login = 'true' WHERE username = ? AND owner = 'APP'");
        $stmtUpdate->bind_param("is", $is_login, $rowusername);
        $stmtUpdate->execute();

        if ($row['date'] === '0000-00-00') {
            $adate    = date('Y-m-d H:i:s', strtotime('+' . $row['adate'] . ' day'));
            $stmtDate = $db->prepare("UPDATE `users` SET date = ? WHERE username = ?");
            $stmtDate->bind_param("ss", $adate, $rowusername);
            $stmtDate->execute();
        }

        if ($row["active"] !== "active") {
            jsonResponse("block");
        }

        $row7 = getSettings($db);

        if ($days_left === "0") {
            jsonResponse("EndDay", $row, $row7, $days_left);
        } elseif ($row['size'] >= $total_r) {
            jsonResponse("EndGB", $row, $row7, $days_left);
        } elseif ($row['login'] === 'true') {
            if ($row['date_login'] == '0000-00-00') {
                $date_login = date('Y-m-d');
                $stmtDL = $db->prepare("UPDATE users SET date_login = ? WHERE username = ?");
                $stmtDL->bind_param('ss', $date_login, $rowusername);
                $stmtDL->execute();
            }
            jsonResponse("success1", $row, $row7, $days_left);
        } else {
            if ($row['date_login'] == '0000-00-00') {
                $date_login = date('Y-m-d');
                $stmtDL = $db->prepare("UPDATE users SET date_login = ? WHERE username = ?");
                $stmtDL->bind_param('ss', $date_login, $rowusername);
                $stmtDL->execute();
            }
            jsonResponse("success2", $row, $row7, $days_left);
        }

    } else {

        $row7 = getSettings($db);

        if ($row7['api_guest_status'] !== '1') {
            jsonResponse("off", null, null, null, "در حال حاضر ورود بصورت مهمان بسته می باشد");
        }

        $stmtAgent = $db->prepare("SELECT from_id FROM agents WHERE `id` = 1");
        $stmtAgent->execute();
        $rowS = $stmtAgent->get_result()->fetch_assoc();

        $user_pass = generateRandomString();
        $next_day  = date('Y-m-d H:i:s', strtotime("+{$row7['api_guest_day']} days"));
        $date      = date('Y-m-d');

        $stmtDevice = $db->prepare("INSERT INTO `devices` (`username`, `deviceId`, `model`, `token`) VALUES (?, ?, ?, ?)");
        $stmtDevice->bind_param("ssss", $user_pass, $deviceIMEI, $Model, $Token);
        $stmtDevice->execute();

        $stmtUser = $db->prepare("INSERT INTO `users`(`username`, `password`, `subscription`, `total`, `full`, `deviceIMEI`, `date`, `adate`, `date_buy`, `owner`, `from_id`, `id_from`, `login`, `is_login`, `multi`, `permium`, `oprator`) VALUES (?, ?, 'Guest User Free', ?, ?, ?, ?, ?, ?, 'APP', ?, ?, 'true', '1', '1', 'Free', ?)");
        $stmtUser->bind_param("ssddsssssss", $user_pass, $user_pass, $row7['app_guest_volume'], $row7['app_guest_volume'], $guestKey, $next_day, $row7['api_guest_day'], $date, $rowS['from_id'], $rowS['from_id'], $row7['api_guest_opt']);

        if ($stmtUser->execute()) {
            $stmtSelect = $db->prepare("SELECT * FROM `users` WHERE username = ? AND password = ?");
            $stmtSelect->bind_param("ss", $user_pass, $user_pass);
            $stmtSelect->execute();
            $resultUser = $stmtSelect->get_result();
            $newUser    = $resultUser->fetch_assoc();
            $days_left  = days_left($newUser['date']);
            lnLog("guest created user=$user_pass");
            if ($newUser['date_login'] == '0000-00-00') {
                $date_login = date('Y-m-d');
                $stmtDL = $db->prepare("UPDATE users SET date_login = ? WHERE username = ?");
                $stmtDL->bind_param('ss', $date_login, $user_pass);
                $stmtDL->execute();
            }
            jsonResponse("success", $newUser, $row7, $days_left);
        } else {
            lnLog("guest insert FAILED err=" . $stmtUser->error);
            jsonResponse("error", null, null, null, "خطا در ثبت کاربر مهمان");
        }
    }
}

elseif ($method == "details") {

    dbgLog("details START user=$username imei=$deviceIMEI model=$Model", 'details');

    if (empty($deviceIMEI) AND empty($Model)) {
        echo json_encode(["result" => "error", "msg" => "مقادیر اجباری وارد نشده"]);
        exit;
    }

    $stmtTok = $db->prepare("UPDATE `devices` SET `token` = ? WHERE `deviceId` = ?");
    $stmtTok->bind_param("ss", $Token, $deviceIMEI);
    $stmtTok->execute();

    $stmtDev = $db->prepare("SELECT deviceId, model, size, status FROM devices WHERE username = ?");
    $stmtDev->bind_param("s", $username);
    $stmtDev->execute();
    $devicesResult = $stmtDev->get_result();
    $devices = [];
    while ($rowDev = $devicesResult->fetch_assoc()) {
        $sizeVal   = (int)$rowDev['size'];
        $fmtSize   = ($sizeVal >= 1024) ? round($sizeVal / 1024, 2) . " گیگ" : $sizeVal . " مگ";
        $fmtStatus = ($rowDev['status'] === "active") ? "فعال" : "مسدود";
        $devices[] = [
            'deviceId' => $rowDev['deviceId'],
            'model'    => $rowDev['model'],
            'size'     => $fmtSize,
            'status'   => $fmtStatus,
        ];
    }
    $stmtDev->close();
    $devicesPayload = ['devices' => $devices];

    $stmtQ = $db->prepare("SELECT * FROM devices WHERE username = ? AND deviceId = ?");
    $stmtQ->bind_param("ss", $username, $deviceIMEI);
    $stmtQ->execute();
    $resultq = $stmtQ->get_result();
    $userCountq = $resultq->num_rows;
    $rowq = $resultq->fetch_assoc();
    $vtoken = $rowq["token"] ?? '';

    if ($userCountq == 0) {
        $dreq = checkDeviceRequest($db, $username, $deviceIMEI);
        if ($dreq) {
            if ($dreq['status'] === 'blocked') {
                echo json_encode(["result" => "blocked", "msg" => "این دستگاه به‌طور دائم مسدود شده است"]);
                exit;
            } elseif ($dreq['status'] === 'approved') {
                echo json_encode(["result" => "approved"]);
                exit;
            } else { // kicked | pending | rejected
                echo json_encode(["result" => "waiting", "status" => $dreq['status'], "reject_count" => (int)$dreq['reject_count']]);
                exit;
            }
        }
        dbgLog("details device_not_found user=$username imei=$deviceIMEI", 'details');
        echo json_encode(["result" => "device_not_found", "msg" => "شناسه دستگاه یافت نشد یا دستگاه فعال نیست."]);
        exit;
    }

    // اطلاعات کاربر
    $stmtUser = $db->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmtUser->bind_param("ss", $username, $password);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();
    $userCount = $resultUser->num_rows;
    $row = $resultUser->fetch_assoc();

    if ($userCount == 0) {
        dbgLog("details bad_credentials user=$username", 'details');
        echo json_encode(["result" => "current", "msg" => "نام کاربری یا پسورد اشتباه"]);
        exit;
    }

    $day = $row["date"] ?? '';
    $daysUser = $row["days"] ?? 0;
    $gb_left = $row['gb'] ?? 0;
    $gb_full = $row['full'] ?? 0;

    // محاسبه روز باقی‌مانده
    $days_left_raw = days_left($day);
    $days_left = max(0, (int)$days_left_raw);

    $total_r = max(0, (float)($row['total'] ?? 0) * 1024);
    $size_r = max(0, (float)($row['size'] ?? 0));

    // وضعیت سرویس
    if ($rowq['status'] != 'active') {
        $resstatus = "deactive";
    } elseif ($days_left == 0) {
        $resstatus = "EndDay";
    } elseif ($size_r >= $total_r) {
        $resstatus = "EndGB";
    } else {
        $resstatus = "active";
    }

    // ========== ارسال نوتیفیکیشن روز (با مدیریت کامل خطا) ==========
    if ($userCount == 1 && $days_left < 1 && $daysUser == 0) {
        $tokens = array_filter(explode(",", $vtoken));
        foreach ($tokens as $deviceToken) {
            // حذف توکن‌های نامعتبر
            if (empty($deviceToken) || $deviceToken == "Token error" || strlen($deviceToken) < 10) {
                continue;
            }
            try {
                $message = "کمتر از " . ($days_left == 0 ? "نیم" : $days_left) . " روز به اتمام زمان اشتراک شما مانده است.";
                $fcmRes = sendFCMNotification($deviceToken, "اخطار روز", $message);
            } catch (Throwable $e) {
            }
        }
        // به‌روزرسانی days حتی اگر ارسال ناموفق بود
        $stmtUpDays = $db->prepare("UPDATE `users` SET days = 1 WHERE username = ?");
        $stmtUpDays->bind_param("s", $username);
        $stmtUpDays->execute();
    } elseif ($days_left >= 1 && $daysUser == 1) {
        $stmtUpDays = $db->prepare("UPDATE `users` SET days = 0 WHERE username = ?");
        $stmtUpDays->bind_param("s", $username);
        $stmtUpDays->execute();
    }
    // ========== بخش نوتیفیکیشن حجم (همینطور) ==========
    $gb_full_safe = max(0, (float)$gb_full);
    $gb_left_str = (string)$gb_full_safe;
    if (strpos($gb_left_str, '.') !== false) {
        $gb_left_int = (int)explode('.', $gb_left_str)[0];
    } else {
        $gb_left_int = (int)$gb_full_safe;
    }

    if ($userCount == 1 && $gb_left_int < 1 && $gb_left_int != 0 && $gb_left == 0) {
        $tokens = array_filter(explode(",", $vtoken));
        foreach ($tokens as $deviceToken) {
            try {
                $message = "کمتر از $gb_full_safe گیگ به اتمام حجم اشتراک شما مانده است.";
                $fcmRes2 = sendFCMNotification($deviceToken, "اخطار حجم", $message);
            } catch (Throwable $e) {
            }
        }
        $stmtUpGB = $db->prepare("UPDATE `users` SET gb = 1 WHERE username = ?");
        $stmtUpGB->bind_param("s", $username);
        $stmtUpGB->execute();
    } elseif ($gb_left_int >= 1 && $gb_left == 1) {
        $stmtUpGB = $db->prepare("UPDATE `users` SET gb = 0 WHERE username = ?");
        $stmtUpGB->bind_param("s", $username);
        $stmtUpGB->execute();
    }

    // به‌روزرسانی date_login در صورت نیاز
    if ($row['date_login'] == '0000-00-00') {
        $date_login = date('Y-m-d');
        $stmtDL = $db->prepare("UPDATE users SET date_login = ? WHERE username = ?");
        $stmtDL->bind_param('ss', $date_login, $username);
        $stmtDL->execute();
    }

    if ($userCount > 0) {
        // محاسبه حجم باقی‌مانده
        $Atime = max(0, (float)$row['total'] * 1024 - (float)$row['size']);
        $Btime = $Atime / 1024;
        $status = $row["active"];
        $total_r = max(0, (float)$row['total'] * 1024);

        $stmtFull = $db->prepare("UPDATE `users` SET full = ? WHERE username = ?");
        $stmtFull->bind_param("ds", $Btime, $username);
        $stmtFull->execute();

        // دریافت اطلاعات agents
        $stmtA1 = $db->prepare("SELECT from_id FROM agents WHERE id = 1");
        $stmtA1->execute();
        $ones = $stmtA1->get_result()->fetch_assoc()['from_id'] ?? '';

        $sss = $row['id_from'];
        $stmtA2 = $db->prepare("SELECT buy, renew, support, amozesh, app, logo FROM agents WHERE from_id = ?");
        $stmtA2->bind_param("s", $sss);
        $stmtA2->execute();
        $resultA2 = $stmtA2->get_result();

        if ($resultA2->num_rows == 1) {
            $agents = $resultA2->fetch_assoc();
        } else {
            $stmtA3 = $db->prepare("SELECT buy, renew, support, amozesh, app, logo FROM agents WHERE from_id = ?");
            $stmtA3->bind_param("s", $ones);
            $stmtA3->execute();
            $agents = $stmtA3->get_result()->fetch_assoc() ?? [];
        }

        // آدرس لوگو
        if (isset($_SERVER['HTTPS'])) {
            $file_path = 'https://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']) . '/panel/images/logo/';
        } else {
            $file_path = 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']) . '/panel/images/logo/';
        }
        $agents['logo'] = $file_path . ($agents['logo'] ?? '');

        // اخبار جدید
        $lowerUsername = strtolower($username);
        $stmtNws = $db->prepare("SELECT COUNT(*) AS total_news FROM news INNER JOIN users ON FIND_IN_SET(users.agent, news.agent) > 0 WHERE users.username = ? AND news.users != '' AND LOWER(news.users) NOT LIKE CONCAT('%|', ?, '|%') AND news.status = 'active'");
        $stmtNws->bind_param('ss', $lowerUsername, $lowerUsername);
        $stmtNws->execute();
        $missingTables = $stmtNws->get_result()->fetch_assoc()['total_news'] ?? 0;

        // نوتیفیکیشن‌ها
        $stmtNotif = $db->prepare("
            SELECT
                notification.id,
                notification.agent,
                notification.users,
                notification.title,
                notification.text,
                notification.link,
                notification.date,
                CASE
                    WHEN LOWER(notification.users) NOT LIKE CONCAT('%|', ?, '|%') AND FIND_IN_SET(?, notification.agent) > 0 THEN 'active'
                    WHEN LOWER(notification.users) LIKE CONCAT('%|', ?, '|%') THEN 'deactive'
                    WHEN FIND_IN_SET(?, notification.agent) = 0 THEN 'deactive'
                    ELSE 'active'
                END AS status
            FROM notification
        ");
        $stmtNotif->bind_param('ssss', $lowerUsername, $row['agent'], $lowerUsername, $row['agent']);
        $stmtNotif->execute();
        $resultNotif = $stmtNotif->get_result();
        $notifications = [];
        while ($notif = $resultNotif->fetch_assoc()) {
            $notifications = $notif;
        }

        // نمایش total
        if ($row['total'] === "1024000") {
            $displaytotal = 'نامحدود';
        } elseif ($row['total'] < 1000) {
            $displaytotal = $row['total'];
        } else {
            $displaytotal = ($row['total'] / 1000);
        }

        // مقدار درست (به گیگ) که چند خط بالاتر محاسبه و در دیتابیس ذخیره شد
        $row['full'] = (string)$Btime;

        // درخواست‌های در انتظارِ بازگشت (برای نمایش به لیدر و تأیید/رد)
        $pendingRequests = [];
        $stmtPR = $db->prepare("SELECT deviceId, model, reject_count, updated_at FROM device_requests WHERE username = ? AND status = 'pending' ORDER BY updated_at DESC");
        $stmtPR->bind_param("s", $username);
        $stmtPR->execute();
        $prRes = $stmtPR->get_result();
        while ($pr = $prRes->fetch_assoc()) {
            $pendingRequests[] = [
                'deviceId'     => $pr['deviceId'],
                'model'        => $pr['model'],
                'reject_count' => (int)$pr['reject_count'],
                'date'         => $pr['updated_at'],
            ];
        }

        $responseBase = [
            'news'         => $missingTables,
            'support'      => getSupport($db),
            'settings'     => getSettings($db),
            'application'  => getApplication($db),
            'notification' => $notifications,
            'agents'       => $agents,
            'users'        => $row,
            'credit'       => $days_left,
            'status'       => $resstatus,
            'devices'      => $devicesPayload,
            'requests'     => $pendingRequests,
        ];

        // تعیین نتیجه نهایی بر اساس وضعیت
        if ($status == "active") {
            if ($rowq['status'] != 'active') {
                $finalResult = array_merge(['result' => 'suspended'], $responseBase);
            } elseif ($days_left == 0) {
                $finalResult = array_merge(['result' => 'EndDay'], $responseBase);
            } elseif ($size_r >= $total_r) {
                $finalResult = array_merge(['result' => 'EndGB'], $responseBase);
            } else {
                $finalResult = array_merge(['result' => 'success'], $responseBase);
            }
        } else {
            $finalResult = array_merge(['result' => 'suspended'], $responseBase);
        }

        dbgLog("details result={$finalResult['result']} user=$username days=$days_left", 'details');
        jsonOut($finalResult);

    } else {
        // این قسمت هرگز اجرا نمی‌شود چون قبلاً بررسی شد، اما برای اطمینان
        echo json_encode(["result" => "current", "msg" => "نام کاربری یا پسورد اشتباه"]);
        exit;
    }
}

elseif ($method == "getAServers") {

    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result    = $stmt->get_result();
    $row       = $result->fetch_array(MYSQLI_ASSOC);
    $status    = $row["active"];
    $total_r   = $row['total'] * 1024;
    $day       = $row["date"];
    $days_left = days_left($day);
    $agent     = $row["agent"];

    $stmtConf  = $db->prepare("SELECT * FROM configs WHERE agent LIKE ?");
    $agentLike = "%$agent%";
    $stmtConf->bind_param("s", $agentLike);
    $stmtConf->execute();
    $data = $stmtConf->get_result()->fetch_all(MYSQLI_ASSOC);

    if (isset($_SERVER['HTTPS'])) {
        $file_path = 'https://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']) . '/panel/images/Flag/';
    } else {
        $file_path = 'http://'  . $_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']) . '/panel/images/Flag/';
    }

    foreach ($data as $key => $val) {
        $data[$key]['src']  = encryptData($file_path . $val['src']);
        $data[$key]['link'] = encryptData($val['link']);
    }

    $stmtDev = $db->prepare("SELECT status FROM devices WHERE username = ? AND deviceId = ?");
    $stmtDev->bind_param('ss', $username, $deviceIMEI);
    $stmtDev->execute();
    $deviceResult = $stmtDev->get_result();

    if ($status == "active") {

        $deviceRow = $deviceResult->fetch_assoc();
        if ($deviceRow['status'] != 'active') {
            jsonOut(['result' => 'suspended', 'credit' => $days_left, 'confs' => $data]);
        }
        if ($days_left == 0) {
            jsonOut(['result' => 'EndDay', 'credit' => $days_left, 'confs' => $data]);
        } else {
            if ($row['size'] >= $total_r) {
                jsonOut(['result' => 'EndGB', 'credit' => $days_left, 'confs' => $data]);
            } else {
                jsonOut(['result' => 'success', 'credit' => $days_left, 'confs' => $data]);
            }
        }

    } elseif ($status == "deactive") {
        jsonOut(['result' => 'suspended', 'credit' => $days_left, 'confs' => $data]);
    }
}

elseif ($method == "logout") {

    if (empty(trim($username)) || empty($deviceIMEI)) {
        echo '{"result":"error", "msg":"نام کاربری یا شناسه دستگاه خالی است."}';
        exit;
    }

    $stmt = $db->prepare("SELECT deviceId FROM `devices` WHERE deviceId = ? AND username = ?");
    $stmt->bind_param("ss", $deviceIMEI, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $count  = $result->num_rows;

    if ($count == 1) {

        $row         = $result->fetch_assoc();
        $deviceField = $row['deviceId'];

        if (strpos($deviceField, $deviceIMEI) !== false) {

            $stmt2 = $db->prepare("SELECT permium, is_login FROM users WHERE username = ?");
            $stmt2->bind_param("s", $username);
            $stmt2->execute();
            $row2 = $stmt2->get_result()->fetch_assoc();

            if ($row2['permium'] == "Pro") {
                $deleteStmt = $db->prepare("DELETE FROM `devices` WHERE deviceId = ? AND username = ?");
                $deleteStmt->bind_param("ss", $deviceIMEI, $username);
                $deleteStmt->execute();
            }

            $stmt3 = $db->prepare("UPDATE `users` SET is_login = is_login - 1 WHERE username = ?");
            $stmt3->bind_param("s", $username);
            $stmt3->execute();

            if ($row2['is_login'] == 1) {
                $stmt4 = $db->prepare("UPDATE `users` SET login = 'false' WHERE username = ?");
                $stmt4->bind_param("s", $username);
                $stmt4->execute();
            }

            echo '{"result":"true"}';
        } else {
            echo '{"result":"device_not_found"}';
        }

    } else {
        echo '{"result":"false"}';
    }
}

elseif ($method == "usage") {

    // =========================================
    // LOG FILE
    // =========================================
    $logFile = __DIR__ . '/usage_debug.log';

    function usageLog($text)
    {
        if (!DEBUG_LOG) return;
        global $logFile;

        if (file_exists($logFile)) {
            $fileTime = filemtime($logFile);
            $todayStart = strtotime(date('Y-m-d 00:00:00'));
            if ($fileTime < $todayStart) {
                unlink($logFile);
            }
        }

        file_put_contents(
            $logFile,
            "[" . date("Y-m-d H:i:s") . "] " . $text . PHP_EOL,
            FILE_APPEND
        );
    }

    function normalizeConfig($link)
    {
        $link = trim($link);
        $link = urldecode($link);
        $link = preg_replace('/#.*$/', '', $link);
        $link = preg_replace('/\s+/', '', $link);
        return strtolower($link);
    }

    // =========================================
    // START
    // =========================================
    usageLog("========================================");
    usageLog("NEW REQUEST");

    usageLog("username=$username");
    usageLog("deviceIMEI=$deviceIMEI");
    usageLog("idServer=$idServer");
    usageLog("usage=$usage");

    usageLog("RAW_APP_CONFIG=" . $Config);

    // =========================================
    // GET USER
    // =========================================
    $stmtRate = $db->prepare(
        "SELECT 
            last_update,
            total,
            size,
            active,
            date
         FROM `users`
         WHERE username = ? AND password = ?"
    );

    $stmtRate->bind_param("ss", $username, $password);
    $stmtRate->execute();

    $userRow = $stmtRate->get_result()->fetch_assoc();

    usageLog("USER_ROW=" . json_encode($userRow));

    if (!$userRow) {

        usageLog("USER NOT FOUND");

        echo 'DICONNECT';
        exit;
    }

    $elapsed = !empty($userRow['last_update'])
        ? time() - (int)strtotime($userRow['last_update'])
        : 999;

    usageLog("elapsed=$elapsed");

    // =========================================
    // DEVICE CHECK
    // =========================================
    $stmtq = $db->prepare(
        "SELECT status, size
         FROM `devices`
         WHERE username = ? AND deviceId = ?"
    );

    $stmtq->bind_param("ss", $username, $deviceIMEI);
    $stmtq->execute();

    $row2 = $stmtq->get_result()->fetch_assoc();

    usageLog("DEVICE_ROW=" . json_encode($row2));

    // =========================================
    // DEVICE STATUS
    // =========================================
    if (!$row2 || $row2['status'] != "active") {

        usageLog("DEVICE NOT ACTIVE");

        echo 'DICONNECT';

        $stmtDis = $db->prepare(
            "UPDATE `users`
             SET online='offline', last_update=?
             WHERE username=?"
        );

        $stmtDis->bind_param("ss", $current_time, $username);
        $stmtDis->execute();

        exit;
    }

    // =========================================
    // SERVER CHECK
    // =========================================
    $stmtServer = $db->prepare(
        "SELECT link, status, size
         FROM `configs`
         WHERE id = ? AND status = 'true'"
    );

    $stmtServer->bind_param("i", $idServer);
    $stmtServer->execute();

    $configRow = $stmtServer->get_result()->fetch_assoc();

    usageLog("CONFIG_ROW=" . json_encode($configRow));

    if (!$configRow) {

        usageLog("CONFIG NOT FOUND");

        echo 'DICONNECT';

        $stmtDis = $db->prepare(
            "UPDATE `users`
             SET online='offline', last_update=?
             WHERE username=?"
        );

        $stmtDis->bind_param("ss", $current_time, $username);
        $stmtDis->execute();

        exit;
    }

    // =========================================
    // CONFIG DEBUG ONLY
    // =========================================
    $dbLink  = trim($configRow['link']);
    $appLink = trim($Config);

    usageLog("DB_LINK=" . $dbLink);
    usageLog("APP_LINK=" . $appLink);

    usageLog("DB_MD5=" . md5($dbLink));
    usageLog("APP_MD5=" . md5($appLink));

    $normalizedDB  = normalizeConfig($dbLink);
    $normalizedAPP = normalizeConfig($appLink);

    usageLog("NORMALIZED_DB=" . $normalizedDB);
    usageLog("NORMALIZED_APP=" . $normalizedAPP);

    // فقط لاگ
    if ($normalizedDB != $normalizedAPP) {

        usageLog("CONFIG MISMATCH DETECTED");

    } else {

        usageLog("CONFIG OK");
    }

    usageLog("CONTINUE NORMAL USAGE FLOW");

    // =========================================
    // USAGE
    // =========================================
    $Twousage = extractNumber($usage);

    usageLog("EXTRACTED_USAGE=$Twousage");

    if ($Twousage < 0 || $Twousage > 3000) {

        usageLog("INVALID USAGE");

        $Twousage = 0;
    }

    $MAX_MB_PER_SEC = 20; // حداکثر سرعت پایدار قابل‌قبول (~24Mbps) — در صورت نیاز تنظیم کن

    $elapsedSafe = (int)$elapsed;
    if ($elapsedSafe < 1)   $elapsedSafe = 30;  // اولین گزارش یا اختلاف ساعت — یک بازه‌ی معمول فرض کن
    if ($elapsedSafe > 600) $elapsedSafe = 600; // سقف پنجره — آفلاین طولانی سهمیه جمع نمی‌کند

    $rateCap = (int)min(3000, $elapsedSafe * $MAX_MB_PER_SEC);

    if ($Twousage > $rateCap) {

        usageLog("RATE_CLAMP: reported=$Twousage elapsed={$elapsedSafe}s cap=$rateCap");

        $Twousage = $rateCap;
    }

    $maxUserSize     = $userRow['total'] * 1024;
    $remainingQuota  = max(0, $maxUserSize - $userRow['size']);
    $appliedUsage    = min($Twousage, $remainingQuota);

    usageLog("REMAINING_QUOTA=$remainingQuota APPLIED_USAGE=$appliedUsage");

    // =========================================
    // UPDATE USAGE
    // =========================================
    if ($appliedUsage > 0) {

        usageLog("ADDING_USAGE=$appliedUsage");

        // USER
        $stmtU = $db->prepare(
            "UPDATE `users`
             SET size = size + ?
             WHERE username = ?"
        );

        $stmtU->bind_param("is", $appliedUsage, $username);
        $stmtU->execute();

        usageLog("USER SIZE UPDATED");

        // DEVICE
        $stmtD = $db->prepare(
            "UPDATE `devices`
             SET size = size + ?
             WHERE deviceId = ? AND username = ?"
        );

        $stmtD->bind_param("iss", $appliedUsage, $deviceIMEI, $username);
        $stmtD->execute();

        usageLog("DEVICE SIZE UPDATED");

        // CONFIG
        $stmtC = $db->prepare(
            "UPDATE `configs`
             SET size = size + ?
             WHERE id = ?"
        );

        $stmtC->bind_param("ii", $appliedUsage, $idServer);
        $stmtC->execute();

        usageLog("CONFIG SIZE UPDATED");
    }
    
    // =========================================
    // CONFIG CHECK
    // =========================================
    
    if ($normalizedDB != $normalizedAPP) {
    
        usageLog("CONFIG MISMATCH DETECTED");
    
        usageLog("SEND DIC TO CLIENT");
    
        echo 'DIC';
    
        $stmtDis = $db->prepare(
            "UPDATE `users`
             SET online='offline', last_update=?
             WHERE username=?"
        );
    
        $stmtDis->bind_param("ss", $current_time, $username);
        $stmtDis->execute();
    
        exit;
    
    } else {
    
        usageLog("CONFIG OK");
    }
    
    // =========================================
    // FINAL CHECK
    // =========================================
    $newSize = $userRow['size'] + $appliedUsage;

    usageLog("newSize=$newSize");
    usageLog("maxUserSize=$maxUserSize");

    if (
        $newSize >= $maxUserSize ||
        $userRow['active'] == "deactive" ||
        $current_time >= $userRow['date']
    ) {

        usageLog("FINAL RESULT = DICONNECT");

        echo 'DICONNECT';

        $stmtDis = $db->prepare(
            "UPDATE `users`
             SET online='offline', last_update=?
             WHERE username=?"
        );

        $stmtDis->bind_param("ss", $current_time, $username);
        $stmtDis->execute();

    } else {

        usageLog("FINAL RESULT = CONNECT");

        echo 'CONNECT';

        $stmtCon = $db->prepare(
            "UPDATE `users`
             SET online='online', last_update=?
             WHERE username=?"
        );

        $stmtCon->bind_param("ss", $current_time, $username);
        $stmtCon->execute();
    }

    usageLog("END REQUEST");
}

elseif ($method == "delete") {

    $stmt = $db->prepare("SELECT id FROM `users` WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    echo ($result->num_rows == 1) ? '{"result":"true"}' : '{"result":"false"}';
    $stmt->close();
}

elseif ($method == "notification") {

    if (!isset($username)) {
        echo json_encode(["result" => "error", "message" => "Username is required"]);
        exit;
    }

    $username = htmlspecialchars($username);

    $stmtAgent = $db->prepare("SELECT agent FROM users WHERE username = ?");
    $stmtAgent->bind_param("s", $username);
    $stmtAgent->execute();
    $resultAgent = $stmtAgent->get_result();

    if ($resultAgent->num_rows == 0) {
        echo json_encode(["result" => "error", "message" => "User not found"]);
        exit;
    }

    $user_agent = $resultAgent->fetch_assoc()['agent'];

    $stmtNotif = $db->prepare("SELECT * FROM notification WHERE FIND_IN_SET(?, agent) > 0");
    $stmtNotif->bind_param("s", $user_agent);
    $stmtNotif->execute();
    $resultNotifications  = $stmtNotif->get_result();
    $countNotifications   = $resultNotifications->num_rows;

    if ($countNotifications > 0) {
        $notificationsArray = [];

        $stmtUpdate = $db->prepare("UPDATE notification SET users = CONCAT(IFNULL(users, ''), '|', ?, '|') WHERE id = ?");

        while ($res = $resultNotifications->fetch_assoc()) {
            $notification_id = $res['id'];

            if (strpos("|" . $res['users'] . "|", "|$username|") === false) {
                $stmtUpdate->bind_param("si", $username, $notification_id);
                $stmtUpdate->execute();
            }

            $notificationsArray[] = [
                "id"       => (string)$res['id'],
                "title_fa" => $res['title_fa'],
                "title_en" => $res['title_en'],
                "text_fa"  => $res['text_fa'],
                "text_en"  => $res['text_en'],
                "status"   => $res['status'],
                "date"     => $res['date'],
                "users"    => $res['users'],
                "agent"    => $res['agent'],
            ];
        }

        echo json_encode(["result" => "success", "notifications" => $notificationsArray]);
    } else {
        echo json_encode(["result" => "none"]);
    }
}

elseif ($method == "setting") {

    if (isset($_SERVER['HTTPS'])) {
        $file_path = 'https://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']) . '/panel/images/';
    } else {
        $file_path = 'http://'  . $_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']) . '/panel/images/';
    }

    $stmt = $db->prepare("SELECT app_name, app_logo FROM `settings` WHERE id = 1");
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!empty($row['app_logo'])) {
        $row['app_logo'] = $file_path . $row['app_logo'];
    }

    echo json_encode(["result" => "true", "settings" => $row]);
    exit;
}

elseif ($method == "news") {

    if (!isset($username)) {
        echo json_encode(["result" => "error", "message" => "Username is required"]);
        exit;
    }

    $username = htmlspecialchars($username);

    $stmtAgent = $db->prepare("SELECT agent FROM users WHERE username = ?");
    $stmtAgent->bind_param("s", $username);
    $stmtAgent->execute();
    $resultAgent = $stmtAgent->get_result();

    if ($resultAgent->num_rows == 0) {
        echo json_encode(["result" => "error", "message" => "User not found"]);
        exit;
    }

    $user_agent = $resultAgent->fetch_assoc()['agent'];

    $stmtNews = $db->prepare("SELECT * FROM news WHERE FIND_IN_SET(?, agent) > 0 AND status = 'active'");
    $stmtNews->bind_param("s", $user_agent);
    $stmtNews->execute();
    $resultNews = $stmtNews->get_result();
    $countNews  = $resultNews->num_rows;

    if ($countNews > 0) {
        $activeNewsArray = [];

        $stmtUpdate = $db->prepare("UPDATE news SET users = CONCAT(IFNULL(users, ''), '|', ?, '|') WHERE id = ?");
        $tz         = new DateTimeZone('Asia/Tehran');

        while ($res = $resultNews->fetch_assoc()) {
            $news_id = $res['id'];

            if (strpos("|" . $res['users'] . "|", "|$username|") === false) {
                $stmtUpdate->bind_param("si", $username, $news_id);
                $stmtUpdate->execute();
            }

            $date = new DateTime($res['date']);
            $date->setTimezone($tz);
            $tehranDate = $date->format('Y-m-d H:i:s');

            $activeNewsArray[] = [
                "id"       => (string)$res['id'],
                "title_fa" => $res['title_fa'],
                "title_en" => $res['title_en'],
                "text_fa"  => $res['text_fa'],
                "text_en"  => $res['text_en'],
                "main"     => $res['main'],
                "status"   => $res['status'],
                "date"     => $tehranDate,
                "users"    => $res['users'],
                "agent"    => $res['agent'],
            ];
        }

        echo json_encode(["result" => "success", "news" => $activeNewsArray]);
    } else {
        echo json_encode(["result" => "none"]);
    }
}

elseif ($method == "questions") {

    if (!isset($username)) {
        echo json_encode(["result" => "error", "message" => "Username is required"]);
        exit;
    }

    $username = "| " . htmlspecialchars($username) . " |";

    $result_news = $db->query("SELECT * FROM questions WHERE status = 'active'");
    $count       = mysqli_num_rows($result_news);

    if ($count > 0) {
        $activeNewsArray = [];
        while ($res = mysqli_fetch_array($result_news, MYSQLI_ASSOC)) {
            $activeNewsArray[] = $res;
        }

        if (!empty($activeNewsArray)) {
            echo json_encode(["result" => "success", "news" => $activeNewsArray]);
        } else {
            echo json_encode(["result" => "none"]);
        }
    } else {
        echo json_encode(["result" => "none"]);
    }
}

elseif ($method == "devices") {

    $stmt1 = $db->prepare("SELECT model FROM `devices` WHERE username = ?");
    $stmt1->bind_param("s", $username);
    $stmt1->execute();
    $devicesResult = $stmt1->get_result();

    $devices = [];
    while ($row = $devicesResult->fetch_assoc()) {
        $devices[] = $row['model'];
    }
    $stmt1->close();

    $stmt2 = $db->prepare("SELECT COUNT(*) AS total_tables, is_login FROM `users` WHERE username = ?");
    $stmt2->bind_param("s", $username);
    $stmt2->execute();
    $userInfo = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();

    echo json_encode([
        'devices'  => $devices,
        'total'    => $userInfo['total_tables'] ?? 0,
        'is_login' => $userInfo['is_login']     ?? null,
    ]);
}

elseif ($method == "removeDevice") {

    $targetId = htmlspecialchars($_POST['targetDeviceId'] ?? '');

    if (empty($username) || empty($password) || empty($targetId)) {
        echo json_encode(["result" => "error", "msg" => "پارامتر ناقص"]);
        exit;
    }

    // احراز هویت اکانت
    $stmtAuth = $db->prepare("SELECT COUNT(*) AS c FROM users WHERE username = ? AND password = ?");
    $stmtAuth->bind_param("ss", $username, $password);
    $stmtAuth->execute();
    if ((int)$stmtAuth->get_result()->fetch_assoc()['c'] === 0) {
        echo json_encode(["result" => "error", "msg" => "نام کاربری یا پسورد اشتباه"]);
        exit;
    }

    // درخواست‌دهنده باید خودش دستگاهِ ثبت‌شده‌ی همین اکانت باشد
    $stmtReq = $db->prepare("SELECT COUNT(*) AS c FROM devices WHERE username = ? AND deviceId = ?");
    $stmtReq->bind_param("ss", $username, $deviceIMEI);
    $stmtReq->execute();
    if ((int)$stmtReq->get_result()->fetch_assoc()['c'] === 0) {
        echo json_encode(["result" => "error", "msg" => "دستگاه شما در این اکانت مجاز نیست"]);
        exit;
    }

    // نمی‌توان دستگاه فعلی را حذف کرد
    if ($targetId === $deviceIMEI) {
        echo json_encode(["result" => "error", "msg" => "نمی‌توانید دستگاه فعلی را حذف کنید"]);
        exit;
    }

    // اطلاعات دستگاه هدف را قبل از حذف نگه دار (برای ثبت در device_requests)
    $stmtInfo = $db->prepare("SELECT model, token FROM devices WHERE username = ? AND deviceId = ?");
    $stmtInfo->bind_param("ss", $username, $targetId);
    $stmtInfo->execute();
    $tInfo = $stmtInfo->get_result()->fetch_assoc();

    $stmtDel = $db->prepare("DELETE FROM devices WHERE username = ? AND deviceId = ?");
    $stmtDel->bind_param("ss", $username, $targetId);
    $stmtDel->execute();
    $removed = $stmtDel->affected_rows;

    if ($removed > 0) {
        $tModel = $tInfo['model'] ?? '';
        $tToken = $tInfo['token'] ?? '';
        $stmtKick = $db->prepare(
            "INSERT INTO device_requests (username, deviceId, model, token, status, reject_count, updated_at)
             VALUES (?, ?, ?, ?, 'kicked', 0, NOW())
             ON DUPLICATE KEY UPDATE
                model = VALUES(model),
                token = VALUES(token),
                status = IF(status = 'blocked', 'blocked', 'kicked'),
                updated_at = NOW()"
        );
        $stmtKick->bind_param("ssss", $username, $targetId, $tModel, $tToken);
        $stmtKick->execute();

        $stmtCnt = $db->prepare("SELECT COUNT(*) AS c FROM devices WHERE username = ?");
        $stmtCnt->bind_param("s", $username);
        $stmtCnt->execute();
        $remain = (int)$stmtCnt->get_result()->fetch_assoc()['c'];

        $loginFlag = $remain > 0 ? 'true' : 'false';
        $stmtUpd = $db->prepare("UPDATE users SET is_login = ?, login = ? WHERE username = ?");
        $stmtUpd->bind_param("iss", $remain, $loginFlag, $username);
        $stmtUpd->execute();

        echo json_encode(["result" => "true", "removed" => $removed, "is_login" => $remain]);
    } else {
        echo json_encode(["result" => "false", "msg" => "دستگاه یافت نشد"]);
    }
}

elseif ($method == "requestRejoin") {

    if (empty($username) || empty($password) || empty($deviceIMEI)) {
        echo json_encode(["result" => "error", "msg" => "پارامتر ناقص"]);
        exit;
    }

    $stmtAuth = $db->prepare("SELECT COUNT(*) AS c FROM users WHERE username = ? AND password = ?");
    $stmtAuth->bind_param("ss", $username, $password);
    $stmtAuth->execute();
    if ((int)$stmtAuth->get_result()->fetch_assoc()['c'] === 0) {
        echo json_encode(["result" => "error", "msg" => "نام کاربری یا پسورد اشتباه"]);
        exit;
    }

    // وضعیت فعلی درخواست
    $stmtCur = $db->prepare("SELECT status, reject_count FROM device_requests WHERE username = ? AND deviceId = ?");
    $stmtCur->bind_param("ss", $username, $deviceIMEI);
    $stmtCur->execute();
    $cur = $stmtCur->get_result()->fetch_assoc();

    if ($cur && $cur['status'] === 'blocked') {
        echo json_encode(["result" => "blocked", "msg" => "این دستگاه به‌طور دائم مسدود شده است"]);
        exit;
    }

    // upsert به pending
    $stmtUp = $db->prepare(
        "INSERT INTO device_requests (username, deviceId, model, token, status, reject_count, updated_at)
         VALUES (?, ?, ?, ?, 'pending', 0, NOW())
         ON DUPLICATE KEY UPDATE
            model = VALUES(model),
            token = VALUES(token),
            status = 'pending',
            updated_at = NOW()"
    );
    $stmtUp->bind_param("ssss", $username, $deviceIMEI, $Model, $Token);
    $stmtUp->execute();

    // اعلان به لیدرها (دستگاه‌های فعالِ اکانت)
    $stmtLead = $db->prepare("SELECT token FROM devices WHERE username = ? AND token IS NOT NULL AND token != ''");
    $stmtLead->bind_param("s", $username);
    $stmtLead->execute();
    $leadRes = $stmtLead->get_result();
    while ($lead = $leadRes->fetch_assoc()) {
        foreach (array_filter(explode(",", $lead['token'])) as $lt) {
            sendFCMNotification($lt, "درخواست ورود دستگاه جدید", "دستگاه «$Model» منتظر تأیید شماست.");
        }
    }

    echo json_encode(["result" => "pending"]);
}

elseif ($method == "respondRejoin") {

    $targetId = htmlspecialchars($_POST['targetDeviceId'] ?? '');
    $action   = htmlspecialchars($_POST['action'] ?? '');

    if (empty($username) || empty($password) || empty($targetId) || empty($action)) {
        echo json_encode(["result" => "error", "msg" => "پارامتر ناقص"]);
        exit;
    }

    $stmtAuth = $db->prepare("SELECT COUNT(*) AS c FROM users WHERE username = ? AND password = ?");
    $stmtAuth->bind_param("ss", $username, $password);
    $stmtAuth->execute();
    if ((int)$stmtAuth->get_result()->fetch_assoc()['c'] === 0) {
        echo json_encode(["result" => "error", "msg" => "نام کاربری یا پسورد اشتباه"]);
        exit;
    }

    // پاسخ‌دهنده باید دستگاهِ فعالِ اکانت باشد
    $stmtReq = $db->prepare("SELECT COUNT(*) AS c FROM devices WHERE username = ? AND deviceId = ?");
    $stmtReq->bind_param("ss", $username, $deviceIMEI);
    $stmtReq->execute();
    if ((int)$stmtReq->get_result()->fetch_assoc()['c'] === 0) {
        echo json_encode(["result" => "error", "msg" => "دستگاه شما مجاز به تأیید نیست"]);
        exit;
    }

    $stmtCur = $db->prepare("SELECT status, reject_count, token, model FROM device_requests WHERE username = ? AND deviceId = ?");
    $stmtCur->bind_param("ss", $username, $targetId);
    $stmtCur->execute();
    $cur = $stmtCur->get_result()->fetch_assoc();

    if (!$cur) {
        echo json_encode(["result" => "error", "msg" => "درخواستی یافت نشد"]);
        exit;
    }

    if ($action === 'approve') {
        $stmtA = $db->prepare("UPDATE device_requests SET status = 'approved', updated_at = NOW() WHERE username = ? AND deviceId = ?");
        $stmtA->bind_param("ss", $username, $targetId);
        $stmtA->execute();
        if (!empty($cur['token'])) {
            sendFCMNotification($cur['token'], "درخواست تأیید شد", "اکنون می‌توانید وارد شوید.");
        }
        echo json_encode(["result" => "approved"]);
    } else { // reject
        $newCount = (int)$cur['reject_count'] + 1;
        $newStatus = $newCount >= 3 ? 'blocked' : 'rejected';
        $stmtR = $db->prepare("UPDATE device_requests SET status = ?, reject_count = ?, updated_at = NOW() WHERE username = ? AND deviceId = ?");
        $stmtR->bind_param("siss", $newStatus, $newCount, $username, $targetId);
        $stmtR->execute();
        if (!empty($cur['token'])) {
            $m = $newStatus === 'blocked'
                ? "درخواست شما رد شد و دستگاه مسدود گردید."
                : "درخواست شما رد شد.";
            sendFCMNotification($cur['token'], "درخواست رد شد", $m);
        }
        echo json_encode(["result" => $newStatus, "reject_count" => $newCount]);
    }
}