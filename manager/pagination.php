<?php
// تعریف مقدار پیش‌فرض برای $limit
$limit = isset($limit) && $limit > 0 ? $limit : 10; // تعداد رکورد در هر صفحه (پیش‌فرض 10)

// محاسبه total_pages
if (!isset($total_pages)) {
    // فرض کنید تعداد کل رکوردها در متغیر $total_records ذخیره شده است
    $total_records = 100; // مثال: مقدار ثابت برای آزمایش
    $total_pages = ceil($total_records / $limit);
}

// بررسی مقدار $page
if (!isset($_GET['page']) || $_GET['page'] < 1) {
    $page = 1;
} else {
    $page = intval($_GET['page']);
}

if ($page == 0) {
    $page = 1;
}

$prev = $page - 1;
$next = $page + 1;
$lastpage = ceil($total_pages / $limit);
$LastPagem1 = $lastpage - 1;

$paginate = '';

if ($lastpage > 1) {
    $paginate .= "<ul class='pagination'>";
    // قبلی
    if ($page > 1) {
        $paginate .= "<li><a href='$targetpage?page=$prev' aria-label='Previous'>&laquo;</a></li>";
    } else {
        $paginate .= "<li class='disabled'><span>&laquo;</span></li>";
    }

    // صفحات
    if ($lastpage < 7 + ($stages * 2)) {
        for ($counter = 1; $counter <= $lastpage; $counter++) {
            if ($counter == $page) {
                $paginate .= "<li class='active'><span>$counter</span></li>";
            } else {
                $paginate .= "<li><a href='$targetpage?page=$counter'>$counter</a></li>";
            }
        }
    } elseif ($lastpage > 5 + ($stages * 2)) {
        if ($page < 1 + ($stages * 2)) {
            for ($counter = 1; $counter < 4 + ($stages * 2); $counter++) {
                if ($counter == $page) {
                    $paginate .= "<li class='active'><span>$counter</span></li>";
                } else {
                    $paginate .= "<li><a href='$targetpage?page=$counter'>$counter</a></li>";
                }
            }
            $paginate .= "<li><a href='$targetpage?page=$LastPagem1'>$LastPagem1</a></li>";
            $paginate .= "<li><a href='$targetpage?page=$lastpage'>$lastpage</a></li>";
        } elseif ($lastpage - ($stages * 2) > $page && $page > ($stages * 2)) {
            $paginate .= "<li><a href='$targetpage?page=$LastPagem1'>$LastPagem1</a></li>";
            $paginate .= "<li><a href='$targetpage?page=$lastpage'>$lastpage</a></li>";
            for ($counter = $page - $stages; $counter <= $page + $stages; $counter++) {
                if ($counter == $page) {
                    $paginate .= "<li class='active'><span>$counter</span></li>";
                } else {
                    $paginate .= "<li><a href='$targetpage?page=$counter'>$counter</a></li>";
                }
            }
            $paginate .= "<li><a href='$targetpage?page=$LastPagem1'>$LastPagem1</a></li>";
            $paginate .= "<li><a href='$targetpage?page=$lastpage'>$lastpage</a></li>";
        } else {
            $paginate .= "<li><a href='$targetpage?page=$LastPagem1'>$LastPagem1</a></li>";
            $paginate .= "<li><a href='$targetpage?page=$lastpage'>$lastpage</a></li>";
            for ($counter = $lastpage - (2 + ($stages * 2)); $counter <= $lastpage; $counter++) {
                if ($counter == $page) {
                    $paginate .= "<li class='active'><span>$counter</span></li>";
                } else {
                    $paginate .= "<li><a href='$targetpage?page=$counter'>$counter</a></li>";
                }
            }
        }
    }

    // بعدی
    if ($page < $lastpage) {
        $paginate .= "<li><a href='$targetpage?page=$next' aria-label='Next'>&raquo;</a></li>";
    } else {
        $paginate .= "<li class='disabled'><span>&raquo;</span></li>";
    }

    $paginate .= "</ul>";
}

echo $paginate;
?>
