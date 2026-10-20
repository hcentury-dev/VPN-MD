<?php
    include("includes/header.php");
    require("includes/function.php");
    require("language/language.php");
    include('includes/jdf.php');

    mysqli_query($mysqli, "CREATE TABLE IF NOT EXISTS `chat_messages` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `username` varchar(190) NOT NULL,
        `sender` varchar(10) NOT NULL DEFAULT 'user',
        `operator` varchar(190) NOT NULL DEFAULT '',
        `text` text NOT NULL,
        `is_read` tinyint(1) NOT NULL DEFAULT 0,
        `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`), KEY `username` (`username`), KEY `is_read` (`is_read`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci");

    $operator = isset($SES_row['username']) ? $SES_row['username'] : 'operator';

    if (isset($_POST['reply_user'], $_POST['reply_text'])) {
        $ru = $_POST['reply_user'];
        $rt = trim($_POST['reply_text']);
        if ($rt !== '') {
            if (mb_strlen($rt) > 2000) { $rt = mb_substr($rt, 0, 2000); }
            $stmt = mysqli_prepare($mysqli, "INSERT INTO `chat_messages` (`username`,`sender`,`operator`,`text`,`is_read`) VALUES (?, 'operator', ?, ?, 0)");
            mysqli_stmt_bind_param($stmt, 'sss', $ru, $operator, $rt);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        header("Location: chat.php?u=" . urlencode($ru));
        exit;
    }

    $selected = isset($_GET['u']) ? $_GET['u'] : '';
    if ($selected !== '') {
        $stmt = mysqli_prepare($mysqli, "UPDATE `chat_messages` SET `is_read`=1 WHERE `username`=? AND `sender`='user'");
        mysqli_stmt_bind_param($stmt, 's', $selected);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    $convos = [];
    $cq = mysqli_query($mysqli, "SELECT `username`, MAX(`id`) AS last_id, MAX(`date`) AS last_date,
            SUM(CASE WHEN `sender`='user' AND `is_read`=0 THEN 1 ELSE 0 END) AS unread
        FROM `chat_messages` GROUP BY `username` ORDER BY last_id DESC LIMIT 300");
    if ($cq) { while ($row = mysqli_fetch_assoc($cq)) { $convos[] = $row; } }
?>
<html lang="fa">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>چت پشتیبانی</title>
<style>
    .chat-wrap { display:flex; gap:12px; direction:rtl; }
    .chat-list { width:300px; max-height:70vh; overflow:auto; border:1px solid #e3e3e3; border-radius:8px; }
    .chat-list a { display:flex; justify-content:space-between; align-items:center; padding:10px 12px; border-bottom:1px solid #f0f0f0; color:#333; text-decoration:none; }
    .chat-list a.active { background:#eef4ff; }
    .chat-badge { background:#e53935; color:#fff; border-radius:10px; padding:1px 7px; font-size:11px; }
    .chat-main { flex:1; display:flex; flex-direction:column; border:1px solid #e3e3e3; border-radius:8px; max-height:70vh; }
    .chat-msgs { flex:1; overflow:auto; padding:14px; background:#fafafa; }
    .bubble { max-width:70%; margin:6px 0; padding:8px 12px; border-radius:12px; font-size:14px; line-height:1.7; word-wrap:break-word; }
    .bubble.user { background:#fff; border:1px solid #e3e3e3; margin-left:auto; }
    .bubble.op { background:#132056; color:#fff; margin-right:auto; }
    .bubble .meta { display:block; font-size:10px; opacity:.6; margin-top:3px; }
    .chat-send { display:flex; gap:8px; padding:10px; border-top:1px solid #eee; }
    .chat-send input[type=text]{ flex:1; }
</style>
</head>
<body>
<div class="row"><div class="col-xs-12"><div class="card mrg_bottom">
    <div class="page_title_block"><div class="col-md-12 col-xs-12"><div class="page_title">چت پشتیبانی آنلاین</div></div></div>
    <div class="clearfix"></div>
    <div class="row mrg-top"><div class="col-md-12">
        <div class="chat-wrap">
            <div class="chat-list">
                <?php if (empty($convos)) { ?>
                    <div style="padding:16px;color:#999;">هنوز گفتگویی نیست.</div>
                <?php } foreach ($convos as $c) {
                    $u = $c['username'];
                    $un = (int)$c['unread'];
                    $active = ($u === $selected) ? 'active' : '';
                ?>
                    <a class="<?php echo $active; ?>" href="chat.php?u=<?php echo urlencode($u); ?>">
                        <span><?php echo htmlspecialchars($u); ?></span>
                        <?php if ($un > 0) { ?><span class="chat-badge"><?php echo $un; ?></span><?php } ?>
                    </a>
                <?php } ?>
            </div>
            <div class="chat-main">
                <?php if ($selected === '') { ?>
                    <div style="margin:auto;color:#999;padding:30px;">یک گفتگو را انتخاب کنید.</div>
                <?php } else {
                    $mq = mysqli_prepare($mysqli, "SELECT `sender`,`operator`,`text`,`date` FROM `chat_messages` WHERE `username`=? ORDER BY `id` ASC LIMIT 500");
                    mysqli_stmt_bind_param($mq, 's', $selected);
                    mysqli_stmt_execute($mq);
                    $mr = mysqli_stmt_get_result($mq);
                ?>
                    <div class="chat-msgs">
                        <?php while ($m = mysqli_fetch_assoc($mr)) {
                            $isUser = ($m['sender'] === 'user');
                            $cls = $isUser ? 'user' : 'op';
                            $who = $isUser ? htmlspecialchars($selected) : htmlspecialchars($m['operator'] !== '' ? $m['operator'] : 'اپراتور');
                        ?>
                            <div class="bubble <?php echo $cls; ?>">
                                <?php echo nl2br(htmlspecialchars($m['text'])); ?>
                                <span class="meta"><?php echo $who . ' · ' . jdate('H:i Y/m/d', strtotime($m['date'])); ?></span>
                            </div>
                        <?php } mysqli_stmt_close($mq); ?>
                    </div>
                    <form class="chat-send" method="post" action="chat.php">
                        <input type="hidden" name="reply_user" value="<?php echo htmlspecialchars($selected); ?>">
                        <input type="text" name="reply_text" placeholder="پاسخ..." autocomplete="off" required>
                        <button type="submit" class="btn btn-primary">ارسال</button>
                    </form>
                <?php } ?>
            </div>
        </div>
    </div></div>
</div></div></div>
</body>
<?php include('includes/footer.php');?>
