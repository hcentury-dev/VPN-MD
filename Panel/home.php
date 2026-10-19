<?php
include('includes/header.php');
include('language/language.php');

// استایل‌های سفارشی تم دارک و آیفون
echo '<style>
    /* تنظیمات کلی صفحه - تم دارک */
    body {
        background-color: #000000 !important;
        background-image: radial-gradient(circle at 50% 0%, #1a1a1a 0%, #000000 100%);
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        color: #ffffff;
        min-height: 100vh;
    }

    /* استایل کارت‌های حبابی آیفون */
    .card {
        background: rgba(50, 50, 50, 0.7) !important;
        backdrop-filter: blur(20px) !important;
        -webkit-backdrop-filter: blur(20px) !important;
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
        border-radius: 20px !important;
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37) !important;
        transition: transform 0.2s cubic-bezier(0.25, 0.46, 0.45, 0.94), background 0.3s !important;
        color: #fff !important;
        text-decoration: none !important;
        display: block;
        overflow: hidden;
        position: relative;
        height: 100%; /* اطمینان از یکسان بودن ارتفاع کارت‌ها */
    }

    /* افکت هاور نرم */
    .card:hover {
        transform: scale(1.02) !important;
        background: rgba(50, 50, 50, 0.7) !important;
        border-color: rgba(255, 255, 255, 0.3) !important;
    }

    /* تنظیمات بدنه کارت */
    .card-body {
        padding: 20px !important;
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-height: 120px; /* حداقل ارتفاع برای کارت‌های کم محتوا */
    }

    /* تنظیمات متن */
    .card .title {
        font-size: 13px;
        font-weight: 500;
        color: rgba(255, 255, 255, 0.7);
        margin-bottom: 8px;
        line-height: 1.4;
    }

    /* تنظیمات مقدار عدد - ریسپانسیو */
    .card .value {
        font-size: 24px;
        font-weight: 700;
        color: #ffffff;
        text-shadow: 0 1px 2px rgba(0,0,0,0.5);
        line-height: 1.2;
        word-wrap: break-word; /* شکستن متن در صورت طولانی بودن */
    }

    /* آیکون‌ها */
    .card .icon {
        color: rgba(255, 255, 255, 0.9);
        opacity: 0.8;
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
    }

    /* بازنویسی رنگ‌های قدیمی */
    .card-blue-light, .card-yellow-light, .card-selver, 
    .card-skyeblue-light, .card-green-light, .card-pink-light, 
    .card-alicerose-light, .card-green2 {
        background: rgba(30, 30, 30, 0.6) !important; 
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
    }
    
    /* استایل آلرت */
    .alert-danger {
        background: rgba(220, 53, 69, 0.9) !important;
        color: white !important;
        border: none !important;
        border-radius: 15px !important;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    }

    /* مدیا کوئری برای موبایل */
    @media (max-width: 768px) {
        .card .value {
            font-size: 20px;
        }
        .card .title {
            font-size: 12px;
        }
    }
</style>';

/////
$qry_online="SELECT COUNT(*) AS count FROM users WHERE online ='online'";
$total_online = mysqli_fetch_array(mysqli_query($mysqli,$qry_online));
$total_online = number_format($total_online['count']);
$qry_offline = "SELECT COUNT(*) AS count FROM users WHERE online = 'offline' AND status = 0";
$total_offline_result = mysqli_query($mysqli, $qry_offline);
$total_offline_array = mysqli_fetch_array($total_offline_result);
$total_offline = number_format($total_offline_array['count']);
$qry_login="SELECT COUNT(*) AS count FROM users WHERE is_login ='0'";
$total_login = mysqli_fetch_array(mysqli_query($mysqli,$qry_login));
$total_login = number_format($total_login['count']);
/////
$qry_total = "SELECT SUM(total) AS totalSum FROM users WHERE total != 1024";
$total_total = mysqli_fetch_array(mysqli_query($mysqli, $qry_total));
$total_total_value = $total_total['totalSum'];
$qry_full = "SELECT IFNULL(SUM(size), 0) AS result FROM users WHERE total != 1024";
$total_full = mysqli_fetch_array(mysqli_query($mysqli, $qry_full));
$total_full_value = $total_full['result'];

// تابع اصلاح شده برای تبدیل دقیق و بدون اعشار
function formatSize($value) {
    $value = floatval($value);
    
    if ($value >= 1048576) { // ترابایت
        return number_format(round($value / 1048576)) . " ترابایت";
    } elseif ($value >= 1024) { // گیگابایت
        $gb = $value / 1024;
        return number_format(round($gb)) . " گیگابایت";
    } else { // مگابایت
        return number_format(round($value)) . " مگابایت";
    }
}

$total_total_formatted = formatSize($total_total_value); 
$total_full_formatted = formatSize($total_full_value);

$qry_Bot="SELECT COUNT(*) AS count FROM users WHERE owner = 'bot'";
$total_Bot = mysqli_fetch_array(mysqli_query($mysqli,$qry_Bot));
$total_Bot = number_format($total_Bot['count']);
$qry_nBot="SELECT COUNT(*) AS count FROM users WHERE owner != 'bot'";
$total_nBot = mysqli_fetch_array(mysqli_query($mysqli,$qry_nBot));
$total_nBot = number_format($total_nBot['count']);
$qry_Free="SELECT COUNT(*) AS count FROM users WHERE permium = 'Free'";
$total_Free = mysqli_fetch_array(mysqli_query($mysqli,$qry_Free));
$total_Free = number_format($total_Free['count']);
$qry_nFree="SELECT COUNT(*) AS count FROM users WHERE permium NOT LIKE 'Free'";
$total_nFree = mysqli_fetch_array(mysqli_query($mysqli,$qry_nFree));
$total_nFree = number_format($total_nFree['count']);
$qry_servers="SELECT COUNT(*) as num FROM configs";
$total_servers= mysqli_fetch_array(mysqli_query($mysqli,$qry_servers));
$total_servers = $total_servers['num'];
$qry_permium="SELECT COUNT(*) as num FROM users WHERE permium = 'Pro'";
$total_permium = mysqli_fetch_array(mysqli_query($mysqli,$qry_permium));
$total_permium = $total_permium['num'];
$qry_users="SELECT COUNT(*) as num FROM users";
$total_users = mysqli_fetch_array(mysqli_query($mysqli,$qry_users));
$total_users = $total_users['num'];
$qry_users_status2 = "SELECT COUNT(*) AS num 
FROM users 
WHERE users.date != '0000-00-00' 
AND users.date <= CURDATE()";

$total_users_status2 = mysqli_fetch_array(
    mysqli_query($mysqli, $qry_users_status2)
);

$total_users_status2 = $total_users_status2['num'];
$qry_users_status1="SELECT COUNT(*) as num FROM users WHERE users.status ='0'";
$total_users_status1 = mysqli_fetch_array(mysqli_query($mysqli,$qry_users_status1));
$total_users_status1 = $total_users_status1['num'];
$qry_users_status0="SELECT COUNT(*) as num FROM users WHERE users.status ='1'";
$total_users_status0 = mysqli_fetch_array(mysqli_query($mysqli,$qry_users_status0));
$total_users_status0 = $total_users_status0['num'];
////////////////////////////////////////////////////////////////////////////////////////
$qry_agents="SELECT COUNT(*) as num FROM agents";
$total_agents = mysqli_fetch_array(mysqli_query($mysqli,$qry_agents));
$total_agents = $total_agents['num'];
$qry_news="SELECT COUNT(*) as num FROM news";
$total_news = mysqli_fetch_array(mysqli_query($mysqli,$qry_news));
$total_news = $total_news['num'];
$qry_questions="SELECT COUNT(*) as num FROM questions";
$total_questions = mysqli_fetch_array(mysqli_query($mysqli,$qry_questions));
$total_questions = $total_questions['num'];
////////////////////////////////////////////////////////////////////////////////////////
$qry_admins="SELECT COUNT(*) as num FROM admin";
$total_admins = mysqli_fetch_array(mysqli_query($mysqli,$qry_admins));
$total_admins = $total_admins['num'];
$qry_notification="SELECT COUNT(*) as num FROM notification";
$total_notification = mysqli_fetch_array(mysqli_query($mysqli,$qry_notification));
$total_notification = $total_notification['num'];
$qry_application="SELECT COUNT(*) as num FROM application";
$total_application = mysqli_fetch_array(mysqli_query($mysqli,$qry_application));
$total_application = $total_application['num'];
?>       
  <div class="row mrg-top">
    <div class="col-md-12">
      <div class="col-md-12 col-sm-12">
        <?php if(isset($_SESSION['msg'])){?>
        <div class="alert alert-danger alert-dismissible" role="alert"> <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
         <?php echo $client_lang[$_SESSION['msg']] ; ?></a> </div>
        <?php unset($_SESSION['msg']);}?>
      </div>
    </div>
  </div>
    <div class="row">
      <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12"> <a href="#" class="card card-banner card-blue-light">
        <div class="card-body">
          <div class="content">
            <div class="title">تعداد کاربران آنلاین</div>
            <div class="value"><span class="sign"></span><?php echo $total_online;?> عدد</div>
          </div>
        </div>
        </a>
        </div>
      <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12"> <a href="#" class="card card-banner card-yellow-light">
        <div class="card-body">
          <div class="content">
            <div class="title">تعداد کاربران آفلاین</div>
            <div class="value"><span class="sign"></span><?php echo $total_offline;?> عدد</div>
          </div>
        </div>
        </a>
        </div>
      <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12"> <a href="#" class="card card-banner card-selver">
        <div class="card-body">
          <div class="content">
            <div class="title">تعداد کاربران وارد نشده</div>
            <div class="value"><span class="sign"></span><?php echo $total_login;?> عدد</div>
          </div>
        </div>
        </a>
        </div>
    </div>
    <div class="row">
      <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12"> <a href="#" class="card card-banner card-skyeblue-light">
        <div class="card-body">
          <div class="content">
            <div class="title">کل حجم خریداری شده کاربران</div>
            <div class="value"><span class="sign"></span><?php echo $total_total_formatted;?></div>
            <br><br>
            <div class="title">کل حجم مصرف شده کاربران</div>
            <div class="value"><span class="sign"></span><?php echo $total_full_formatted;?></div>
          </div>
        </div>
        </a>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12"> <a href="#" class="card card-banner card-skyeblue-light">
        <div class="card-body">
          <div class="content">
            <div class="title">تعداد اکانت های ساخت مدیریت</div>
            <div class="value"><span class="sign"></span><?php echo $total_nBot;?> عدد</div>
            <br><br>
            <div class="title">تعداد اکانت های ساخت ربات</div>
            <div class="value"><span class="sign"></span><?php echo $total_Bot;?> عدد</div>
          </div>
        </div>
        </a>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12"> <a href="#" class="card card-banner card-skyeblue-light">
        <div class="card-body">
          <div class="content">
            <div class="title">تعداد کاربران پرمیوم</div>
            <div class="value"><span class="sign"></span><?php echo $total_nFree;?> عدد</div>
            <br><br>
            <div class="title">تعداد کاربران مهمان</div>
            <div class="value"><span class="sign"></span><?php echo $total_Free;?> عدد</div>
          </div>
        </div>
        </a>
        </div>
    </div>
    <div class="row">
      <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12"> <a href="#" class="card card-banner card-green-light">
        <div class="card-body"> <i class="icon fa fa-sitemap fa-4x"></i>
          <div class="content">
            <div class="title">سرورها</div>
            <div class="value"><span class="sign"></span><?php echo $total_servers;?></div>
          </div>
        </div>
        </a>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12"> <a href="#" class="card card-banner card-yellow-light">
        <div class="card-body"> <i class="icon fa fa-star fa-4x"></i>
          <div class="content">
            <div class="title">اکانت ها</div>
            <div class="value"><span class="sign"></span><?php echo $total_permium;?></div>
          </div>
        </div>
        </a>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12"> <a href="#" class="card card-banner card-blue-light">
        <div class="card-body"><i class="icon fa fa-users fa-4x"></i>
          <div class="content">
            <div class="title">کاربران</div>
            <div class="value"><span class="sign"></span><?php echo $total_users;?></div>
          </div>
        </div>
        </a> </div>
    </div>
    <div class="row">
      <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12"> <a href="#" class="card card-banner card-pink-light">
        <div class="card-body"> <i class="icon fa fa-user-secret fa-4x"></i>
          <div class="content">
            <div class="title">کاربران منقضی</div>
            <div class="value"><span class="sign"></span><?php echo $total_users_status2;?></div>
          </div>
        </div>
        </a>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12"> <a href="#" class="card card-banner card-alicerose-light">
        <div class="card-body"><i class="icon fa fa-user fa-4x"></i>
          <div class="content">
            <div class="title">کاربران فعال</div>
            <div class="value"><span class="sign"></span><?php echo $total_users_status1;?></div>
          </div>
        </div>
        </a> </div><div class="col-lg-4 col-md-6 col-sm-6 col-xs-12"> <a href="#" class="card card-banner card-selver">
        <div class="card-body"> <i class="icon fa fa-user-times fa-4x"></i>
          <div class="content">
            <div class="title">کاربران غیرفعال</div>
            <div class="value"><span class="sign"></span><?php echo $total_users_status0;?></div>
          </div>
        </div>
        </a>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12"> <a href="#" class="card card-banner card-yellow-light">
        <div class="card-body"> <i class="icon fa fa-coffee fa-4x"></i>
          <div class="content">
            <div class="title">نماینده ها</div>
            <div class="value"><span class="sign"></span><?php echo $total_agents;?></div>
          </div>
        </div>
        </a>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12"> <a href="#" class="card card-banner card-blue-light">
        <div class="card-body"> <i class="icon fa fa-bullhorn fa-4x"></i>
          <div class="content">
            <div class="title">اخبار ها</div>
            <div class="value"><span class="sign"></span><?php echo $total_news;?></div>
          </div>
        </div>
        </a>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12"> <a href="#" class="card card-banner card-green-light">
        <div class="card-body"><i class="icon fa fa-comments fa-4x"></i>
          <div class="content">
            <div class="title">سوالات</div>
            <div class="value"><span class="sign"></span><?php echo $total_questions;?></div>
          </div>
        </div>
        </a> </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12"> <a href="#" class="card card-banner card-green2">
            <div class="card-body"> <i class="icon fa fa-diamond fa-4x"></i>
              <div class="content">
                <div class="title">مدیران</div>
                <div class="value"><span class="sign"></span><?php echo $total_admins;?></div>
              </div>
            </div>
            </a>
        </div>
    </div>
<?php include("includes/footer.php");?>