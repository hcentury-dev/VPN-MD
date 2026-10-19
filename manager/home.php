<?php
include('includes/header.php');
include('language/language.php');

// 	ini_set('display_errors', 1);
//     ini_set('display_startup_errors', 1);
//     error_reporting(E_ALL);

// دریافت اطلاعات مدیر (Agent) با احتیاط
if (isset($_SESSION['id'])) {
    $stmt = $mysqli->prepare("SELECT username, owner FROM manager WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $username = $row['username'];
        $agent = $row['owner'];
    } else {
        die("خطا در احراز هویت");
    }
    $stmt->close();
} else {
    die("دسترسی غیرمجاز");
}

// --- بهینه‌سازی: دریافت تمام آمار در یک کوئری ---
$sql_stats = "
    SELECT
        COUNT(CASE WHEN online = 'online' THEN 1 END) AS count_online,
        COUNT(CASE WHEN online = 'offline' AND status = 0 THEN 1 END) AS count_offline,
        COUNT(CASE WHEN is_login = '0' THEN 1 END) AS count_login,
        COALESCE(SUM(total), 0) AS sum_total,
        COALESCE(SUM(size), 0) AS sum_full,
        COUNT(CASE WHEN permium = 'Pro' THEN 1 END) AS count_premium,
        COUNT(*) AS count_users,
        COUNT(CASE WHEN status = '2' THEN 1 END) AS count_status2,
        COUNT(CASE WHEN status = '0' THEN 1 END) AS count_status0,
        COUNT(CASE WHEN status = '1' THEN 1 END) AS count_status1
    FROM users
    WHERE agent = ?
";

$stmt = $mysqli->prepare($sql_stats);
$stmt->bind_param("s", $agent);
$stmt->execute();
$stats_result = $stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stmt->close();

// فرمت کردن اعداد
$total_online = number_format($stats['count_online']);
$total_offline = number_format($stats['count_offline']);
$total_login = number_format($stats['count_login']);

// اصلاح شده: تبدیل حجم کل از مگابایت به گیگابایت
$total_total = number_format((float)($stats['sum_total'] ?? 0));
$total_full = number_format((float)($stats['sum_full'] ?? 0));

$total_permium = $stats['count_premium'];
$total_users = $stats['count_users'];
$total_users_status2 = number_format($stats['count_status2']); // منقضی
$total_users_status0 = number_format($stats['count_status0']); // فعال
$total_users_status1 = number_format($stats['count_status1']); // وضعیت دیگر

// محاسبه کاربران فعال و مسدود شده
$total_nFree = number_format($stats['count_status0'] + $stats['count_status1']); 
$total_Free = number_format($stats['count_status2']); 

// دریافت تعداد سرورها
$stmt = $mysqli->prepare("SELECT COUNT(*) as num FROM configs WHERE agent = ?");
$stmt->bind_param("s", $agent);
$stmt->execute();
$server_res = $stmt->get_result();
$total_servers = $server_res->fetch_assoc()['num'];
$stmt->close();

// --- بخش نمودار: محاسبه داده‌های ۷ روز گذشته ---
$chart_labels = [];
$chart_purchased = [];
$chart_consumed = [];

// آرایه‌ای برای نگهداری تاریخ‌ها
$dates_array = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date("Y-m-d", strtotime("-$i days"));
    $dates_array[] = $date;
    $chart_labels[] = jdate("l", strtotime($date)); // نام روز به فارسی
}

// 1. دریافت خریدها در یک کوئری (GROUP BY)
// استفاده از ستون date_buy برای خرید
$dates_string = "'" . implode("','", $dates_array) . "'";
$sql_buy_chart = "SELECT DATE(date_buy) as buy_date, SUM(total) as daily_buy FROM users WHERE agent = ? AND DATE(date_buy) IN ($dates_string) GROUP BY DATE(date_buy)";

$stmt_buy = $mysqli->prepare($sql_buy_chart);
$stmt_buy->bind_param("s", $agent);
$stmt_buy->execute();
$res_buy = $stmt_buy->get_result();

$buy_data = [];
if ($res_buy) {
    while ($row = $res_buy->fetch_assoc()) {
        if (isset($row['buy_date'])) {
            $buy_data[$row['buy_date']] = $row['daily_buy'];
        }
    }
}
$stmt_buy->close();

// 2. دریافت مصرف‌ها در یک کوئری (GROUP BY)
// استفاده از ستون date برای انقضا (طبق لیست ارسالی شما)
$sql_use_chart = "SELECT DATE(`date`) as expire_date, SUM(total - full) as daily_use FROM users WHERE agent = ? AND DATE(`date`) IN ($dates_string) GROUP BY DATE(`date`)";

$stmt_use = $mysqli->prepare($sql_use_chart);
$stmt_use->bind_param("s", $agent);
$stmt_use->execute();
$res_use = $stmt_use->get_result();

$use_data = [];
if ($res_use) {
    while ($row = $res_use->fetch_assoc()) {
        if (isset($row['expire_date'])) {
            $use_data[$row['expire_date']] = $row['daily_use'];
        }
    }
}
$stmt_use->close();

// پر کردن آرایه‌های نمودار با تطبیق تاریخ‌ها
foreach ($dates_array as $d) {
    // تبدیل مگابایت به گیگابایت (تقسیم بر 1024)
    $buy_val = isset($buy_data[$d]) ? $buy_data[$d] : 0;
    $chart_purchased[] = round($buy_val); 

    $use_val = isset($use_data[$d]) ? $use_data[$d] : 0;
    $chart_consumed[] = round($use_val); 
}
?>       
<style>
    /* استایل‌های سفارشی برای داشبورد مدرن */
    .dashboard-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        color: #fff;
        position: relative;
        overflow: hidden;
        display: block;
        text-decoration: none;
        margin-bottom: 20px;
    }
    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        text-decoration: none;
        color: #fff;
    }
    .card-icon {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 3rem;
        opacity: 0.2;
        transition: all 0.3s ease;
    }
    .dashboard-card:hover .card-icon {
        opacity: 0.4;
        transform: translateY(-50%) scale(1.1);
    }
    .card-content {
        padding: 25px;
        padding-right: 80px; /* فضا برای آیکون */
        position: relative;
        z-index: 2;
    }
    .card-title {
        font-weight: 500;
        margin-bottom: 5px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .card-value {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 5px;
    }
    .card-sub {
        font-size: 0.8rem;
        opacity: 0.8;
        border-top: 1px solid rgba(255,255,255,0.2);
        padding-top: 5px;
        margin-top: 5px;
    }
    /* رنگ‌های گرادینت */
    .bg-gradient-blue { background: linear-gradient(45deg, #2196F3, #21CBF3); }
    .bg-gradient-green { background: linear-gradient(45deg, #11998e, #38ef7d); }
    .bg-gradient-orange { background: linear-gradient(45deg, #f12711, #f5af19); }
    .bg-gradient-purple { background: linear-gradient(45deg, #834d9b, #d04ed6); }
    .bg-gradient-red { background: linear-gradient(45deg, #eb3349, #f45c43); }
    .bg-gradient-teal { background: linear-gradient(45deg, #00b09b, #96c93d); }
    .bg-gradient-dark { background: linear-gradient(45deg, #434343, #000000); }
    .bg-gradient-info { background: linear-gradient(45deg, #2193b0, #6dd5ed); }
    /* استایل کانتینر نمودار */
    .chart-container {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        padding: 20px;
        margin-bottom: 20px;
        position: relative;
        height: 400px; /* ارتفاع ثابت برای نمودار */
        width: 100%;
    }
    .chart-header {
        margin-bottom: 15px;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .chart-title {
        font-size: 1.1rem;
        font-weight: bold;
        color: #333;
    }
    /* ریسپانسیو */
    @media (max-width: 768px) {
        .card-icon { right: 15px; font-size: 2.5rem; }
        .card-content { padding: 20px; padding-right: 60px; }
        .card-value { font-size: 1.5rem; }
        .chart-container { height: 300px; padding: 10px; }
    }
</style>
<!-- فراخوانی کتابخانه Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<div class="row mrg-top">
    <div class="col-md-12">
        <div class="col-md-12 col-sm-12">
            <?php
            if (isset($_SESSION['msg2']) && isset($client_lang[$_SESSION['msg2']])) {
                echo '<div class="alert alert-danger alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>' .
                        $client_lang[$_SESSION['msg2']] .
                      '</div>';
                unset($_SESSION['msg2']);
            } elseif (isset($_SESSION['msg3']) && isset($client_lang[$_SESSION['msg3']])) {
                echo '<div class="alert alert-success" role="alert">' .
                        $client_lang[$_SESSION['msg3']] .
                      '</div>';
                unset($_SESSION['msg3']);
            }
            ?>  
        </div>
    </div>
</div>
<?php $_SESSION['msg3'] = "101"; ?>
<div class="row">
    <!-- ردیف اول: وضعیت کاربران -->
    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <a href="#" class="dashboard-card bg-gradient-blue">
            <i class="fa fa-wifi card-icon"></i>
            <div class="card-content">
                <div class="card-title">کاربران آنلاین</div>
                <div class="card-value"><?php echo $total_online; ?></div>
                <div class="card-sub">در حال حاضر متصل</div>
            </div>
        </a>
    </div>
    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <a href="#" class="dashboard-card bg-gradient-orange">
            <i class="fa fa-power-off card-icon"></i>
            <div class="card-content">
                <div class="card-title">کاربران آفلاین</div>
                <div class="card-value"><?php echo $total_offline; ?></div>
                <div class="card-sub">قطع ارتباط</div>
            </div>
        </a>
    </div>
    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <a href="#" class="dashboard-card bg-gradient-dark">
            <i class="fa fa-user-times card-icon"></i>
            <div class="card-content">
                <div class="card-title">وارد نشده</div>
                <div class="card-value"><?php echo $total_login; ?></div>
                <div class="card-sub">عدم ورود به اکانت</div>
            </div>
        </a>
    </div>
</div>
<div class="row">
    <!-- ردیف دوم: آمار حجم و اکانت‌ها -->
    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <a href="#" class="dashboard-card bg-gradient-info">
            <i class="fa fa-hdd card-icon"></i>
            <div class="card-content">
                <div class="card-title">حجم خریداری شده</div>
                <div class="card-value"><?php echo $total_total; ?> <small>GB</small></div>
                <div class="card-sub">مصرف شده: <?php echo $total_full; ?> GB</div>
            </div>
        </a>
    </div>
    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <a href="#" class="dashboard-card bg-gradient-blue">
            <i class="fa fa-user-clock card-icon"></i>
            <div class="card-content">
                <div class="card-title">کاربران منقضی</div>
                <div class="card-value"><?php echo $total_users_status2; ?></div>
                <div class="card-sub">اعتبار تمام شده</div>
            </div>
        </a>
    </div>
    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <a href="#" class="dashboard-card bg-gradient-teal">
            <i class="fa fa-user-check card-icon"></i>
            <div class="card-content">
                <div class="card-title">کاربران فعال</div>
                <div class="card-value"><?php echo $total_nFree; ?></div>
                <div class="card-sub">مسدود شده: <?php echo $total_Free; ?></div>
            </div>
        </a>
    </div>
</div>
<!-- ردیف سوم: نمودار آمار -->
<div class="row">
    <div class="col-md-12">
        <div class="chart-container">
            <div class="chart-header">
                <div class="chart-title">روند مصرف و خرید حجم (30 روز گذشته)</div>
                <div>
                    <span style="font-size: 12px; color: #666;">بر اساس تاریخ خرید و انقضا</span>
                </div>
            </div>
            <div style="position: relative; height: 320px; width: 100%;">
                <canvas id="trafficChart"></canvas>
            </div>
        </div>
    </div>
</div>
<script>
    // تبدیل آرایه‌های PHP به جاوااسکریپت
    const labels = <?php echo json_encode($chart_labels); ?>;
    const dataPurchased = <?php echo json_encode($chart_purchased); ?>;
    const dataConsumed = <?php echo json_encode($chart_consumed); ?>;
    
    // تنظیمات نمودار
    const ctx = document.getElementById('trafficChart').getContext('2d');
    const trafficChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'حجم خریداری شده (GB)',
                    data: dataPurchased,
                    borderColor: '#2196F3', // آبی
                    backgroundColor: 'rgba(33, 150, 243, 0.1)',
                    borderWidth: 3,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#2196F3',
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    fill: true,
                    tension: 0.4 // انحنای نرم خط
                },
                {
                    label: 'حجم مصرفی (GB)',
                    data: dataConsumed,
                    borderColor: '#38ef7d', // سبز
                    backgroundColor: 'rgba(56, 239, 125, 0.1)',
                    borderWidth: 3,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#38ef7d',
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: {
                            family: 'Tahoma',
                            size: 12
                        },
                        usePointStyle: true,
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    titleFont: { size: 14 },
                    bodyFont: { size: 13 },
                    padding: 10,
                    cornerRadius: 8,
                    displayColors: true
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    },
                    ticks: {
                        font: { family: 'Tahoma' }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: { family: 'Tahoma' }
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });
</script>
<?php include("includes/footer.php");?>