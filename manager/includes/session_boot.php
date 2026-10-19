<?php
// Session bootstrap — پنل همکار (partner/reseller panel)
// نام و مسیر کوکی اختصاصی تا با پنل مدیر روی یک دامنه تداخل نکند.
// نکته: اگر پنل همکار را در مسیری غیر از /manager/ نصب کردی، فقط مقدار path
// را به همان مسیر تغییر بده (یا برای ساده‌ترین حالت، فقط session_name کافی است).
if (session_status() === PHP_SESSION_NONE) {
    session_name('WIZ_PARTNER');
    if (!headers_sent()) {
        session_set_cookie_params(['path' => '/manager/']);
    }
    session_start();
}
