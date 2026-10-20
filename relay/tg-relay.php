<?php
/**
 * tg-relay.php — رله‌ی تلگرام برای مکانیزم link_get پنل (سرور ایران)
 * ------------------------------------------------------------------
 * این فایل روی سرور خارج (که به api.telegram.org دسترسی دارد) قرار می‌گیرد و
 * دقیقاً قراردادِ خودِ پنل را پیاده می‌کند: پنل وقتی روی سرور ایران است، به‌جای
 * api.telegram.org این آدرس (link_get) را صدا می‌زند و فیلدهای زیر را می‌فرستد:
 *   action = getMe | getChat | sendMessage      (یا آپلود فایل = sendDocument)
 *   telegram_token, telegram_id, text, caption, file
 * و انتظار دارد پاسخ به شکل { "telegram_response": {<پاسخ تلگرام>} } برگردد.
 *
 * امنیت: پروکسی باز نیست — فقط api.telegram.org، و اگر RELAY_SECRET تنظیم شده
 * باشد پنل باید relay_secret را هم بفرستد (در settings پنل فیلدش اضافه شد).
 *
 * نصب: این فایل را روی سرور خارج بگذار، RELAY_SECRET را عوض کن، و URL همین فایل
 * را در پنل → تنظیمات → «سرور واسط (link_get)» بگذار و همان سکرت را در فیلد
 * «سکرت سرور واسط» وارد کن.
 */

const RELAY_SECRET = '';
const TG = 'https://api.telegram.org';

header('Content-Type: application/json; charset=utf-8');

if (RELAY_SECRET !== '') {
    $sec = isset($_POST['relay_secret']) ? $_POST['relay_secret']
         : (isset($_SERVER['HTTP_X_RELAY_SECRET']) ? $_SERVER['HTTP_X_RELAY_SECRET'] : '');
    if (!hash_equals(RELAY_SECRET, (string)$sec)) {
        http_response_code(403);
        echo json_encode(array('ok' => false, 'error' => 'forbidden'));
        exit;
    }
}

$token  = isset($_POST['telegram_token']) ? (string)$_POST['telegram_token'] : '';
$chatId = isset($_POST['telegram_id']) ? (string)$_POST['telegram_id'] : '';
$action = isset($_POST['action']) ? (string)$_POST['action'] : '';

if (!preg_match('/^\d{5,}:[A-Za-z0-9_-]{20,}$/', $token)) {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'error' => 'bad_token'));
    exit;
}

function tg_forward($token, $method, $params, $multipart) {
    $ch = curl_init(TG . '/bot' . $token . '/' . $method);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $multipart ? $params : http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    $r = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($r === false) {
        return array('ok' => false, 'error' => 'relay_curl', 'detail' => $err);
    }
    $j = json_decode($r, true);
    return is_array($j) ? $j : array('ok' => false, 'error' => 'relay_decode');
}

if (isset($_FILES['file']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
    $params = array(
        'chat_id'  => $chatId,
        'caption'  => isset($_POST['caption']) ? $_POST['caption'] : '',
        'document' => new CURLFile(
            $_FILES['file']['tmp_name'],
            isset($_FILES['file']['type']) ? $_FILES['file']['type'] : 'application/octet-stream',
            isset($_FILES['file']['name']) ? $_FILES['file']['name'] : 'backup.sql'
        ),
    );
    $resp = tg_forward($token, 'sendDocument', $params, true);
} else {
    switch ($action) {
        case 'getMe':
            $resp = tg_forward($token, 'getMe', array(), false);
            break;
        case 'getChat':
            $resp = tg_forward($token, 'getChat', array('chat_id' => $chatId), false);
            break;
        case 'sendMessage':
            $resp = tg_forward($token, 'sendMessage', array(
                'chat_id'    => $chatId,
                'text'       => isset($_POST['text']) ? $_POST['text'] : '',
                'parse_mode' => isset($_POST['parse_mode']) ? $_POST['parse_mode'] : 'HTML',
            ), false);
            break;
        default:
            http_response_code(400);
            echo json_encode(array('ok' => false, 'error' => 'bad_action'));
            exit;
    }
}

echo json_encode(array('ok' => true, 'telegram_response' => $resp));
