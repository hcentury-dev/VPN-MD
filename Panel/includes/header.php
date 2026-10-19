<?php include("includes/connection.php");
      include("includes/session_check.php");
      date_default_timezone_set('Asia/Tehran');

      //Get file name
      $currentFile = $_SERVER["SCRIPT_NAME"];
      $parts = Explode('/', $currentFile);
      $currentFile = $parts[count($parts) - 1];       
      header("Cache-Control: no-cache, must-revalidate");
      header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); 
      
    $SES_qry = "SELECT * FROM admin WHERE id='".$_SESSION['id']."'";
    $SES_result = mysqli_query($mysqli, $SES_qry);
    $SES_row = mysqli_fetch_assoc($SES_result);
    $ADMIN_M = $SES_row['is_admin'];

	$qry = "SELECT * FROM settings WHERE id='1'";
    $result = mysqli_query($mysqli, $qry);
    $settings_row = mysqli_fetch_assoc($result);
    
    $file_path = $settings_row['panel_link'];

?>
<!DOCTYPE html>
<html>
<head>
<meta name="author" content="">
<meta name="description" content="">
<meta http-equiv="Content-Type"content="text/html;charset=UTF-8"/>
<meta name="viewport"content="width=device-width, initial-scale=1.0">
<title><?php echo APP_NAME;?></title>
<link rel="icon" href="images/favico.png" type="image/png">
<link sizes="180x180" href="images/favico.png" rel="apple-touch-icon">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="assets/css/vendor.css">
<link rel="stylesheet" type="text/css" href="assets/css/flat-admin.css">

<link rel="stylesheet" type="text/css" href="assets/css/style-fa.css">
<!-- Theme -->
<link rel="stylesheet" type="text/css" href="assets/css/theme/blue-sky.css">
<link rel="stylesheet" type="text/css" href="assets/css/theme/blue.css">
<link rel="stylesheet" type="text/css" href="assets/css/theme/red.css">
<link rel="stylesheet" type="text/css" href="assets/css/theme/yellow.css">

<link rel="stylesheet" type="text/css" href="assets/bootstrap-tag/bootstrap-tagsinput.css">
<!-- End -->


<script src="assets/ckeditor/ckeditor.js"></script>

  <style type="text/css">
    .btn_edit, .btn_delete{
      padding: 5px 10px !important;
    }
  </style>

</head>
<body>
<div class="app app-default">
  <aside class="app-sidebar" id="sidebar">
    <div class="sidebar-header"> <a class="sidebar-brand" href="home.php"><img src="images/<?php echo APP_LOGO;?>" alt="logo" /></a>
      <button type="button" class="sidebar-toggle"> <i class="fa fa-times"></i> </button>
    </div>
    <div class="sidebar-menu">
      <ul class="sidebar-nav">
        
        <?php if ($ADMIN_M == "true") { ?>
            <style>
                @keyframes blink {
                    0% { opacity: 1; }
                    50% { opacity: 0.3; }
                    100% { opacity: 1; }
                }
                
                .special-item {
                    background: linear-gradient(134deg, #132056, #2f92a98a);
                }
                .special-item a {
                    color: #fff !important;
                }
            </style>
        
            <li class="special-item <?php if($currentFile == $file_path) { echo 'active'; } ?>"> 
                <a href="<?php echo $file_path; ?>" target="_blank">
                    <div class="icon"> <i class="fa fa-link" aria-hidden="true"></i> </div>
                    <div class="title">ورود به پنل همکاران <span></span></div>
                </a> 
            </li>
            <li class="special-item <?php if($currentFile=="manager.php"){echo "active";} ?>"> 
                <a href="manager.php">
                    <div class="icon"> <i class="fa fa-star" aria-hidden="true"></i> </div>
                    <div class="title">مدیریت همکاران <span class="blinking-circle"></span></div>
                </a> 
            </li>
            <li class="special-item <?php if($currentFile=="plans.php"){echo "active";} ?>"> 
                <a href="plans.php">
                    <div class="icon"> <i class="fa fa-tasks" aria-hidden="true"></i> </div>
                    <div class="title">پلن های همکاران <span class="blinking-circle"></span></div>
                </a> 
            </li>
            
            <li class="special-item <?php if($currentFile=="managerServers.php"){echo "active";} ?>"> 
                <a href="managerServers.php">
                    <div class="icon"> <i class="fa fa-android" aria-hidden="true"></i> </div>
                    <div class="title">سرورهای همکاران <span class="blinking-circle"></span></div>
                </a> 
    </li>
        
            <li class="special-item <?php if($currentFile=="managerUsers.php"){echo "active";} ?>"> 
                <a href="managerUsers.php">
                    <div class="icon"> <i class="fa fa-users" aria-hidden="true"></i> </div>
                    <div class="title">کاربران همکاران <span class="blinking-circle"></span></div>
                </a> 
    </li>
        <?php } ?>
        
        <li <?php if($currentFile=="home.php"){?>class="active"<?php }?>> <a href="home.php">
          <div class="icon"> <i class="fa fa-dashboard" aria-hidden="true"></i> </div>
          <div class="title">داشبورد</div>
          </a> 
        </li>

        <li <?php if($currentFile=="pro_server.php" or $currentFile=="pro_server.php"){?>class="active"<?php }?>> <a href="pro_server.php">
          <div class="icon"> <i class="fa fa-android" aria-hidden="true"></i> </div>
          <div class="title">سرورهای اختصاصی</div>
          </a> 
        </li>
        <li <?php if($currentFile=="usersPro.php" or $currentFile=="usersPro.php" or $currentFile=="usersPro.php"){?>class="active"<?php }?>> <a href="usersPro.php">
          <div class="icon"> <i class="fa fa-users" aria-hidden="true"></i> </div>
          <div class="title">کاربران اختصاصی</div>
          </a> 
        </li>
        
        <li <?php if($currentFile=="free_server.php" or $currentFile=="free_server.php"){?>class="active"<?php }?>> <a href="free_server.php">
          <div class="icon"> <i class="fa fa-android" aria-hidden="true"></i> </div>
          <div class="title">سرورهای رایگان</div>
          </a> 
        </li>    
        <li <?php if($currentFile=="usersFree.php" or $currentFile=="usersFree.php" or $currentFile=="usersFree.php"){?>class="active"<?php }?>> <a href="usersFree.php">
          <div class="icon"> <i class="fa fa-users" aria-hidden="true"></i> </div>
          <div class="title">کاربران رایگان</div>
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
          <div class="title">لیست نماینده ها</div>
          </a> 
        </li>
        <li <?php if($currentFile=="news.php"){?>class="active"<?php }?>> <a href="news.php">
          <div class="icon"> <i class="icon fa fa-bullhorn" aria-hidden="true"></i> </div>
          <div class="title">لیست اخبار</div>
          </a> 
        </li>
        <li <?php if($currentFile=="question.php"){?>class="active"<?php }?>> <a href="question.php">
          <div class="icon"> <i class="icon fa fa-comments" aria-hidden="true"></i> </div>
          <div class="title">لیست سوالات</div>
          </a> 
        </li>
        <li <?php if($currentFile=="admins.php"){?>class="active"<?php }?>> <a href="admins.php">
          <div class="icon"> <i class="icon fa fa-diamond" aria-hidden="true"></i> </div>
          <div class="title">لیست مدیران</div>
          </a> 
        </li>
        <li <?php if($currentFile=="massage.php"){?>class="active"<?php }?>> <a href="massage.php">
          <div class="icon"> <i class="fa fa-paper-plane" aria-hidden="true"></i> </div>
          <div class="title">نوتیفکیشن ها</div>
          </a> 
        </li>
        <li <?php if($currentFile=="notification.php"){?>class="active"<?php }?>> <a href="notification.php">
          <div class="icon"> <i class="fa fa-paper-plane" aria-hidden="true"></i> </div>
          <div class="title">اعلان برنامه</div>
          </a> 
        </li>
        <li <?php if($currentFile=="update.php"){?>class="active"<?php }?>> <a href="update.php">
          <div class="icon"> <i class="fa fa-refresh" aria-hidden="true"></i> </div>
          <div class="title">آپدیت اندروید</div>
          </a> 
        </li>
        <li <?php if($currentFile=="settings.php"){?>class="active"<?php }?>> <a href="settings.php">
          <div class="icon"> <i class="fa fa-cog" aria-hidden="true"></i> </div>
          <div class="title">تنظیمات</div>
          </a> 
        </li>
      </ul>
    </div>
     
  </aside>   
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
                  <img class="profile-img" src="assets/images/profile.jpg">
                <?php }?>
                  
              </button>
            </li>
          </ul>
          <ul class="nav navbar-nav navbar-left">
            <li class="navbar-title"><?php echo APP_NAME;?></li>
             
          </ul>
          <ul class="nav navbar-nav navbar-right">
            <li class="dropdown profile"> <a href="profile.php" class="dropdown-toggle" data-toggle="dropdown"> <?php if(PROFILE_IMG){?>               
                  <img class="profile-img" src="images/<?php echo PROFILE_IMG;?>">
                <?php }else{?>
                  <img class="profile-img" src="assets/images/profile.jpg">
                <?php }?>
              <div class="title">پروفایل</div>
              </a>
              <div class="dropdown-menu">
                <div class="profile-info">
                  <h4 class="username">مدیریت حساب</h4>
                </div>
                <ul class="action">
                  <li><a href="profile.php">پروفایل</a></li>                  
                  <li><a href="logout.php">خروج</a></li>
                </ul>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </nav>
    