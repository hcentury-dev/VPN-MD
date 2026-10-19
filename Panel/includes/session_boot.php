<?php
// Session bootstrap — پنل مدیر (admin panel)
// نام سشن اختصاصی تا وقتی پنل مدیر و پنل همکار (manager/) روی یک دامنه نصب
// می‌شوند، کوکی سشن‌شان یکی نشود و خروج/لاگین یکی، دیگری را خارج یا خراب نکند.
if (session_status() === PHP_SESSION_NONE) {
    session_name('WIZ_ADMIN');
    if (!headers_sent()) {
        session_set_cookie_params(['path' => '/']);
    }
    session_start();
}
