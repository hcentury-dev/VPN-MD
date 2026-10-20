<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

$DB_HOST = 'localhost';
$DB_USER = 'YOUR_DB_USER';
$DB_PASS = 'YOUR_DB_PASS';
$DB_NAME = 'YOUR_DB_NAME';

function out($arr, $code = 200) {
    http_response_code($code);
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

$db = @mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if (!$db) { out(['ok' => false, 'error' => 'db']); }
mysqli_set_charset($db, 'utf8mb4');

mysqli_query($db, "CREATE TABLE IF NOT EXISTS `chat_messages` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(190) NOT NULL,
    `sender` varchar(10) NOT NULL DEFAULT 'user',
    `operator` varchar(190) NOT NULL DEFAULT '',
    `text` text NOT NULL,
    `is_read` tinyint(1) NOT NULL DEFAULT 0,
    `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `username` (`username`),
    KEY `is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci");

$action   = isset($_POST['action']) ? trim((string)$_POST['action']) : '';
$username  = isset($_POST['username']) ? trim((string)$_POST['username']) : '';
if ($username === '' || mb_strlen($username) > 190) { out(['ok' => false, 'error' => 'username']); }

if ($action === 'send') {
    $text = isset($_POST['text']) ? trim((string)$_POST['text']) : '';
    if ($text === '') { out(['ok' => false, 'error' => 'empty']); }
    if (mb_strlen($text) > 2000) { $text = mb_substr($text, 0, 2000); }
    $stmt = mysqli_prepare($db, "INSERT INTO `chat_messages` (`username`,`sender`,`text`,`is_read`) VALUES (?, 'user', ?, 0)");
    mysqli_stmt_bind_param($stmt, 'ss', $username, $text);
    $okq = mysqli_stmt_execute($stmt);
    $id = mysqli_insert_id($db);
    mysqli_stmt_close($stmt);
    out(['ok' => (bool)$okq, 'id' => $id]);
}

if ($action === 'fetch') {
    $afterId = isset($_POST['after_id']) ? (int)$_POST['after_id'] : 0;
    $stmt = mysqli_prepare($db, "SELECT `id`,`sender`,`operator`,`text`,`is_read`,`date` FROM `chat_messages` WHERE `username` = ? AND `id` > ? ORDER BY `id` ASC LIMIT 200");
    mysqli_stmt_bind_param($stmt, 'si', $username, $afterId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($r = mysqli_fetch_assoc($res)) {
        $rows[] = [
            'id'       => (int)$r['id'],
            'sender'   => $r['sender'],
            'operator' => $r['operator'],
            'text'     => $r['text'],
            'is_read'  => (int)$r['is_read'],
            'date'     => $r['date'],
        ];
    }
    mysqli_stmt_close($stmt);
    mysqli_query($db, "UPDATE `chat_messages` SET `is_read` = 1 WHERE `username` = '" . mysqli_real_escape_string($db, $username) . "' AND `sender` = 'operator'");
    out(['ok' => true, 'messages' => $rows]);
}

out(['ok' => false, 'error' => 'bad_action']);
