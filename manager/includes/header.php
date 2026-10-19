<?php
    include("connection.php");
    include("session_check.php");
    include("jdf.php");
    date_default_timezone_set('Asia/Tehran');
    $sql = "SELECT * FROM settings";
    $result = mysqli_query($mysqli, $sql);
    $row = mysqli_fetch_assoc($result);
    $manager_gb = $row['manager_gb'];
    $manager_day = $row['manager_day'];
    $manager_total = $row['manager_total'];
    $manager_multi = $row['manager_multi'];
    $manager_server = $row['manager_server'];
    $SES_qry = "SELECT * FROM manager WHERE id='".$_SESSION['id']."'";
    $SES_result = mysqli_query($mysqli, $SES_qry);
    $SES_row = mysqli_fetch_assoc($SES_result);
    $agent = $SES_row['owner'];
    $qry_total = "SELECT SUM(total) AS totalSum FROM users WHERE agent = '$agent'";
        $total_total = mysqli_fetch_array(mysqli_query($mysqli, $qry_total));
        $total_total = $total_total['totalSum'];
    $qry_adate = "SELECT SUM(adate) AS adateSum FROM users WHERE agent = '$agent'";
        $total_adate = mysqli_fetch_array(mysqli_query($mysqli, $qry_adate));
        $total_adate = $total_adate['adateSum'];
    $qry_users="SELECT COUNT(*) as num FROM users WHERE agent = '$agent'";
        $total_users = mysqli_fetch_array(mysqli_query($mysqli,$qry_users));
        $total_users = $total_users['num'];
    $qry_multi = "SELECT SUM(multi) AS multiSum FROM users WHERE agent = '$agent'";
        $total_multi = mysqli_fetch_array(mysqli_query($mysqli,$qry_multi));
        $total_multi = $total_multi['multiSum'];
    $qry_servers="SELECT COUNT(*) as num FROM configs WHERE agent = '$agent'";
        $total_servers= mysqli_fetch_array(mysqli_query($mysqli,$qry_servers));
        $total_servers = $total_servers['num'];
    $date_now = new DateTime();
    $date_expire = new DateTime($SES_row['expire']);
    $timestamp = strtotime($SES_row['expire']);
    $shamsi_date = jdate("Y/m/d", $timestamp);
    $shamsi_time = jdate("H:i", $timestamp);
    $now = time();
    $diff = $timestamp - $now;
    if ($diff > 0) {
        $days = floor($diff / (60 * 60 * 24));
        $hours = floor(($diff % (60 * 60 * 24)) / (60 * 60));
        $minutes = floor(($diff % (60 * 60)) / 60);
        $remaining_time = "از $days روز و $hours ساعت و $minutes دقیقه";
    } else {
        $remaining_time = "مدت اعتبار به پایان رسیده است";
    }
    function toPersianNumbers($number) {
    $persian_numbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    return str_replace(range(0, 9), $persian_numbers, $number);
}
    $shamsi_date = toPersianNumbers($shamsi_date);
    $remaining_time = toPersianNumbers($remaining_time);
    if (!isset($SES_row['status'])){
        session_start();
        unset($_SESSION["name"]);
        header("Location:index.php");
        exit;
    } else if ($SES_row['status'] == 'deactive'){
        session_start();
        unset($_SESSION["name"]);
        header("Location:index.php");
        exit;
    }
    else if ($SES_row['type'] == '0' && $date_now > $date_expire) {
   mysqli_query ($mysqli,"UPDATE `manager` SET `status` = 'expire' WHERE `manager`.`id` ='".$_SESSION['id']."'");
        session_start();
        unset($_SESSION["name"]);
        header("Location:index.php");
        exit;
    }
    $size = $SES_row['size'];
    $day = $SES_row['day'];
    $total = $SES_row['total'];
    $multi = $SES_row['multi'];
    $servers = $SES_row['servers'];
    $type = $SES_row['type'];
    $accountType = '';
    $acc = 'نوع اکانت: ';
    if ($type == 1) {
        //GB
        $addGB = $size - $total_total;
        $accountGB = "حجم: ".$addGB. ' گیگ';
        //DAY
        $addDAY = $day - $total_adate;
        $accountDAY = "زمان: ".$addDAY. ' روز';
        //GB
        $addTOTAL = $total - $total_users;
        $accountTOTAL = "ساخت کاربر : ".$addTOTAL. ' عدد';
        //GB
        $addMULTI = $multi - $total_multi;
        $accountMULTI = "مولتی اکانت : ".$addMULTI. ' عدد';
        //GB
        $addSERVERS = $servers - $total_servers;
        $accountSERVERS = "ساخت سرور: ".$addSERVERS. ' عدد';
        // Profile
        $accountType = $acc.'محدودیت اشتراکی';
    } elseif ($type == 2) {
            $accountType = $acc.'کیف پول';
            $accountType2 = number_format($SES_row['inventory']). ' تومان';
    } else {
        $addTOTAL = $total - $total_users;
        $addSERVERS = $servers - $total_servers;
        if ($addTOTAL > "10240"){
            $accountTOTAL = "🔅 کاربر نامحدود";
        } else {
            $accountTOTAL = $addTOTAL. ' عدد کاربر مانده';
        }
        if ($addSERVERS > "10240"){
            $accountSERVERS = "🔅 سرور نامحدود";
        } else {
            $accountSERVERS = $addSERVERS. ' عدد سرور مانده';
        }
    }
    $currentFile = $_SERVER["SCRIPT_NAME"];
    $parts = Explode('/', $currentFile);
    $currentFile = $parts[count($parts) - 1];       
    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
?>
<!DOCTYPE html>
<html>
<head>
<meta name="author" content="">
<meta name="description" content="">
<meta http-equiv="Content-Type"content="text/html;charset=UTF-8"/>
<meta name="viewport"content="width=device-width, initial-scale=1.0">
<title>پنل همکاران</title>
<link rel="icon" href="images/manager1.png" type="image/png">
<link sizes="180x180" href="images/manager1.png" rel="apple-touch-icon">
<link rel="stylesheet" type="text/css" href="assets/css/vendor.css">
<link rel="stylesheet" type="text/css" href="assets/css/flat-admin.css">
<link rel="stylesheet" type="text/css" href="assets/css/style-fa.css">
<!-- Theme -->
<link rel="stylesheet" type="text/css" href="assets/css/theme/blue-sky.css">
<link rel="stylesheet" type="text/css" href="assets/css/theme/blue.css">
<link rel="stylesheet" type="text/css" href="assets/css/theme/red.css">
<link rel="stylesheet" type="text/css" href="assets/css/theme/yellow.css">
<!-- For Bootstrap Tags -->
<link rel="stylesheet" type="text/css" href="assets/bootstrap-tag/bootstrap-tagsinput.css">
<!-- End -->
<script src="assets/ckeditor/ckeditor.js"></script>
  <style type="text/css">
    .btn_edit, .btn_delete{
      padding: 5px 10px !important;
    }

    /* --- استایل‌های مدرن سایدبار --- */
    .app-sidebar {
        background: #1e293b !important; /* رنگ تیره مدرن */
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    }
    .sidebar-header {
        padding: 15px;
        text-align: center;
    }
    .sidebar-brand img {
        height: 50px;
        object-fit: contain;
    }
    .sidebar-menu ul {
        padding: 10px 0;
    }
    .sidebar-menu ul li a {
        color: #cbd5e1;
        padding: 12px 20px;
        display: flex;
        align-items: center;
        transition: all 0.3s;
        border-left: 3px solid transparent;
    }
    .sidebar-menu ul li a:hover, .sidebar-menu ul li.active a {
        background: rgba(255,255,255,0.05);
        color: #fff;
        border-left-color: #3b82f6;
    }
    .sidebar-menu ul li a .icon {
        width: 30px;
        font-size: 1.1em;
        text-align: center;
    }
    .sidebar-menu ul li a .title {
        font-weight: 400;
        font-size: 0.95em;
    }

    /* --- کارت‌های وضعیت در سایدبار --- */
    .sidebar-stats-card {
        background: rgba(255,255,255,0.03);
        margin: 10px 15px;
        padding: 12px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        color: #fff;
        border: 1px solid rgba(255,255,255,0.05);
    }
    .sidebar-stats-icon {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-left: 10px;
        font-size: 14px;
    }
    .sidebar-stats-content {
        flex: 1;
    }
    .sidebar-stats-title {
        font-size: 0.75rem;
        opacity: 0.7;
        margin-bottom: 2px;
    }
    .sidebar-stats-value {
        font-weight: bold;
    }
    .bg-icon-blue { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
    .bg-icon-green { background: rgba(16, 185, 129, 0.2); color: #34d399; }
    .bg-icon-purple { background: rgba(139, 92, 246, 0.2); color: #a78bfa; }
    .bg-icon-orange { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
    .bg-icon-red { background: rgba(239, 68, 68, 0.2); color: #f87171; }

    /* --- تایمر انقضا --- */
    .expire-timer-box {
        background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        margin: 15px;
        padding: 15px;
        border-radius: 10px;
        text-align: center;
        color: #fff;
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
    }
    .expire-timer-title {
        opacity: 0.9;
        margin-bottom: 5px;
    }
    .expire-timer-value {
        font-size: 1.1rem;
        font-weight: bold;
        font-family: monospace;
        direction: ltr;
    }
    .expire-date-text {
        font-size: 0.75rem;
        margin-top: 5px;
        opacity: 0.8;
        border-top: 1px solid rgba(255,255,255,0.2);
        padding-top: 5px;
    }

    /* --- منوی پروفایل بالا --- */
    .navbar-right .profile > a {
        display: flex;
        align-items: center;
        padding: 0 10px;
        color: #555;
    }
    .profile-img {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e2e8f0;
    }
    .dropdown-menu.profile-dropdown {
        width: 250px;
        border: none;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        border-radius: 10px;
        padding: 0;
        margin-top: 15px;
        overflow: hidden;
    }
    .profile-header {
        background: #f8fafc;
        padding: 15px;
        border-bottom: 1px solid #e2e8f0;
        text-align: center;
    }
    .profile-header h4 {
        margin: 0;
        font-size: 1rem;
        color: #334155;
    }
    .profile-body ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .profile-body ul li a {
        display: block;
        padding: 10px 20px;
        color: #64748b;
        font-size: 0.9rem;
        transition: 0.2s;
    }
    .profile-body ul li a:hover {
        background: #f1f5f9;
        color: #3b82f6;
    }
  </style>
</head>
<body>
<div class="app app-default">
  <aside class="app-sidebar" id="sidebar">
    <div class="sidebar-header"> 
        <a class="sidebar-brand" href="home.php">
            <img src="images/<?php echo APP_LOGO;?>" alt="app logo" />
        </a>
      <button type="button" class="sidebar-toggle"> <i class="fa fa-times"></i> </button>
    </div>
<div class="sidebar-menu">
    <!-- شروع کانتینر اصلی شیک -->
    <div class="sidebar-info-container">
        
        <!-- منوی اصلی -->
        <ul class="sidebar-nav" style="margin-bottom: 0; padding-bottom: 10px;">
            <li <?php if($currentFile=="home.php"){?>class="active"<?php }?>> <a href="home.php">
              <div class="icon"> <i class="fa fa-dashboard" aria-hidden="true"></i> </div>
              <div class="title">داشبورد</div>
              </a>
            </li>
            <li <?php if($currentFile=="pro_server.php" or $currentFile=="pro_server.php"){?>class="active"<?php }?>> <a href="pro_server.php">
              <div class="icon"> <i class="fa fa-android" aria-hidden="true"></i> </div>
              <div class="title">سرورها</div>
              </a>
            </li>
            <li <?php if($currentFile=="usersPro.php" or $currentFile=="usersPro.php" or $currentFile=="usersPro.php"){?>class="active"<?php }?>> <a href="usersPro.php">
              <div class="icon"> <i class="fa fa-users" aria-hidden="true"></i> </div>
              <div class="title">کاربران</div>
              </a>
            </li>
            <li <?php if($currentFile=="users_online.php" or $currentFile=="users_online.php" or $currentFile=="users_online.php"){?>class="active"<?php }?>> <a href="users_online.php">
              <div class="icon"> <i class="fa fa-globe" aria-hidden="true"></i> </div>
              <div class="title">کاربران آنلاین</div>
              </a>
            </li>
            <li <?php if($currentFile=="users_de.php" or $currentFile=="users_de.php" or $currentFile=="users_de.php"){?>class="active"<?php }?>> <a href="users_de.php">
              <div class="icon"> <i class="fa fa-user-secret" aria-hidden="true"></i> </div>
              <div class="title">کاربران منقضی</div>
              </a>
            </li>        
            <li <?php if($currentFile=="agents.php"){?>class="active"<?php }?>> <a href="agents.php">
              <div class="icon"> <i class="icon fa fa-coffee" aria-hidden="true"></i> </div>
              <div class="title">مدیریت نماینده</div>
              </a>
            </li>
            <li <?php if($currentFile=="logout.php"){?>class="active"<?php }?>> <a href="logout.php">
              <div class="icon"> <i class="icon fa fa-sign-out" aria-hidden="true"></i> </div>
              <div class="title">خروج از پنل</div>
              </a>
            </li>
        </ul>

        <!-- بخش اطلاعات وضعیت (زیر منو) -->
        <div class="sidebar-stats-wrapper" style="padding: 15px; border-top: 1px solid #eee;">
            <?php if ($type == 1) { ?>
                <!-- کارت‌های وضعیت اشتراکی -->
                <div class="sidebar-stats-card" style="display: block; text-align: center; margin-top: 10px;">
                    <div class="sidebar-stats-content">
                        <div class="sidebar-stats-title">حجم باقی‌مانده</div>
                        <div class="sidebar-stats-value"><?php echo $accountGB; ?></div>
                    </div>
                </div>
                <div class="sidebar-stats-card" style="display: block; text-align: center; margin-top: 10px;">
                    <div class="sidebar-stats-content">
                        <div class="sidebar-stats-title">زمان باقی‌مانده</div>
                        <div class="sidebar-stats-value"><?php echo $accountDAY; ?></div>
                    </div>
                </div>
                <div class="sidebar-stats-card" style="display: block; text-align: center; margin-top: 10px;">
                    <div class="sidebar-stats-content">
                        <div class="sidebar-stats-title">ساخت کاربر</div>
                        <div class="sidebar-stats-value"><?php echo $accountTOTAL; ?></div>
                    </div>
                </div>
                <div class="sidebar-stats-card" style="display: block; text-align: center; margin-top: 10px;">
                    <div class="sidebar-stats-content">
                        <div class="sidebar-stats-title">مولتی اکانت</div>
                        <div class="sidebar-stats-value"><?php echo $accountMULTI; ?></div>
                    </div>
                </div>
                <div class="sidebar-stats-card" style="display: block; text-align: center; margin-top: 10px;">
                    <div class="sidebar-stats-content">
                        <div class="sidebar-stats-title">ساخت سرور</div>
                        <div class="sidebar-stats-value"><?php echo $accountSERVERS; ?></div>
                    </div>
                </div>
            <?php } elseif ($type == 2 && $size == 20) { ?>
                <!-- لیست قیمت‌ها -->
                <div class="sidebar-stats-card" style="display: block;">
                    <div class="sidebar-stats-content" style="display: block; width: 100%; box-sizing: border-box; text-align: center;">
                        <div class="sidebar-stats-title">موجودی کیف پول</div>
                        <div class="sidebar-stats-value"><?php echo $accountType2; ?></div>
                    </div>
                    <div class="sidebar-stats-content" style="display: block; width: 100%; box-sizing: border-box; text-align: center; margin-top: 10px;">
                        <div class="sidebar-stats-title">هر گیگابایت</div>
                        <div class="sidebar-stats-value"><?php echo number_format($manager_gb); ?> تومان</div>
                    </div>
                    <div class="sidebar-stats-content" style="display: block; width: 100%; box-sizing: border-box; text-align: center; margin-top: 10px;">
                        <div class="sidebar-stats-title">هر روز</div>
                        <div class="sidebar-stats-value"><?php echo number_format($manager_day); ?> تومان</div>
                    </div>
                    <div class="sidebar-stats-content" style="display: block; width: 100%; box-sizing: border-box; text-align: center; margin-top: 10px;">
                        <div class="sidebar-stats-title">هر اکانت</div>
                        <div class="sidebar-stats-value"><?php echo number_format($manager_total); ?> تومان</div>
                    </div>
                </div>
            <?php } elseif ($type == 2 && $size == 10) { ?>
                <!-- لیست قیمت‌ها -->
                <div class="sidebar-stats-card" style="display: block;">
                    <div class="sidebar-stats-content" style="display: block; width: 100%; box-sizing: border-box; text-align: center;">
                        <div class="sidebar-stats-title">موجودی کیف پول</div>
                        <div class="sidebar-stats-value"><?php echo $accountType2; ?></div>
                    </div>
                </div>
            <?php } else { ?>
                <!-- تایمر نامحدود -->
                <div class="expire-timer-box" style="text-align: center; margin-top: 10px;">
                    <div class="expire-timer-title">پایان اعتبار پنل</div>
                    <div class="expire-timer-value" id="countdown"><?php echo $remaining_time; ?></div>
                    <div class="expire-date-text"><?php echo $shamsi_time . " - " . $shamsi_date; ?></div>
                </div>
                <div class="sidebar-stats-card" style="display: block; text-align: center; margin-top: 10px;">
                    <div class="sidebar-stats-content">
                        <div class="sidebar-stats-title">محدودیت کاربر</div>
                        <div class="sidebar-stats-value"><?php echo $accountTOTAL; ?></div>
                    </div>
                </div>
                <div class="sidebar-stats-card" style="display: block; text-align: center; margin-top: 10px;">
                    <div class="sidebar-stats-content">
                        <div class="sidebar-stats-title">محدودیت سرور</div>
                        <div class="sidebar-stats-value"><?php echo $accountSERVERS; ?></div>
                    </div>
                </div>
            <?php }?>
        </div>
    </div>
    <!-- پایان کانتینر اصلی -->
</div>  </aside>   
  <div class="app-container">
    <nav class="navbar navbar-default" id="navbar">
      <div class="container-fluid">
        <div class="navbar-collapse collapse in">
          <ul class="nav navbar-nav navbar-mobile">
            <li>
              <button type="button" class="sidebar-toggle"> <i class="fa fa-bars"></i> </button>
            </li>
            <li class="logo"> <a class="navbar-brand" href="#"><?php echo APP_NAME;?></a> </li>
            <li>
              <button type="button" class="navbar-toggle">
                <?php if(PROFILE_IMG){?>               
                  <img class="profile-img" src="images/<?php echo PROFILE_IMG;?>">
                <?php }else{?>
                  <img class="profile-img" src="assets/images/profile.png">
                <?php }?>
              </button>
            </li>
          </ul>
          <ul class="nav navbar-nav navbar-left">
            <li class="navbar-title">پنل همکاران</li>
          </ul>
          <ul class="nav navbar-nav navbar-right">
            <li class="dropdown profile">
              <a href="javascript:void(0);" onclick="toggleProfileMenu()">
                <?php if(PROFILE_IMG) { ?>               
                  <img class="profile-img" src="images/<?php echo PROFILE_IMG;?>">
                <?php } else { ?>
                  <img class="profile-img" src="assets/images/profile.png">
                <?php } ?>
                <div class="title" style="margin-right: 10px;">پروفایل</div>
              </a>
              <div class="dropdown-menu profile-dropdown">
                <div class="profile-header">
                    <h4>مدیریت حساب کاربری</h4>
                </div>
                <div class="profile-body">
                    <ul>
                        <?php if ($type == 1) { ?>
                            <li><a><?php echo $accountType; ?></a></li>  
                        <?php } elseif ($type == 2) { ?>
                            <li><a><?php echo $accountType; ?></a></li>                  
                            <li><a><?php echo $accountType2; ?></a></li>  
                        <?php } else { ?>
                            <li><a><?php echo $shamsi_time . " - " . $shamsi_date; ?></a></li>
                            <li><a><?php echo $remaining_time; ?></a></li>
                        <?php } ?>
                        <li style="border-top: 1px solid #eee;"><a href="logout.php" style="color: #ef4444;"><i class="fa fa-sign-out"></i> خروج از حساب</a></li>
                    </ul>
                </div>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </nav>
    
    <!-- اسکریپت ساده برای بستن منو در موبایل -->
    <script>
        function toggleProfileMenu() {
            var menu = document.querySelector('.profile .dropdown-menu');
            if (menu.style.display === 'block') {
                menu.style.display = 'none';
            } else {
                menu.style.display = 'block';
            }
        }
        
        // بستن منو با کلیک بیرون
        window.onclick = function(event) {
            if (!event.target.matches('.profile-img') && !event.target.matches('.title') && !event.target.closest('.profile')) {
                var dropdowns = document.getElementsByClassName("dropdown-menu");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.style.display === 'block') {
                        openDropdown.style.display = 'none';
                    }
                }
            }
        }
    </script>