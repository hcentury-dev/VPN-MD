<?php 

    include('includes/header.php'); 
    include('includes/function.php');
    include('language/language.php');  

// 	ini_set('display_errors', 1);
//     ini_set('display_startup_errors', 1);
//     error_reporting(E_ALL);
    
    if(isset($_SESSION['id']))
	{
		$manager_qry="SELECT * FROM manager where id='".$_SESSION['id']."'";
		$manager_result=mysqli_query($mysqli,$manager_qry);
		$manager_row=mysqli_fetch_assoc($manager_result);
		$agent = $manager_row['owner'];
		$agent_id = $manager_row['id'];
	}
	
    function futureDate($days) {
        $today = new DateTime();
        $today->add(new DateInterval("P{$days}D"));
        return $today->format('Y-m-d');
    }
    	
    if (isset($_POST['search'])) {
    
        $search_value = addslashes($_POST['search_value']);
        $search_show_users = addslashes($_POST['search_show_users']);
        $search_full = (float) $_POST['search_gb'];
        $days_limit = (int) $_POST['search_day'];
        $search_show = isset($_POST['search_show']) && (int)$_POST['search_show'] > 0 ? (int)$_POST['search_show'] : 80;
    
        $current_date = date('Y-m-d');
        $user_qry = "SELECT * FROM users WHERE 1=1";
        $user_qry .= " AND users.permium = 'Pro' AND agent = '$agent'";
    
        if (!empty($search_value)) {
            $user_qry .= " AND users.username LIKE '%$search_value%'";
        }
        if (!empty($search_show_users)) {
            $user_qry .= " AND users.multi <= $search_show_users";
        }
        if (!empty($search_full)) {
            $user_qry .= " AND CAST(users.full AS DECIMAL(10,2)) <= $search_full";
        }
        if (!empty($days_limit)) {
            $user_qry .= " AND DATEDIFF(users.date, '$current_date') <= $days_limit";
        }
        
        $order_clauses = [];
        if (!empty($days_limit)) {
            $order_clauses[] = "DATEDIFF(users.date, '$current_date') DESC";
        }
        if (!empty($search_full)) {
            $user_qry .= " AND CAST(users.full AS DECIMAL(10,2)) <= $search_full";
        }
        
        $order_clauses[] = "CAST(users.full AS DECIMAL(10,2)) DESC";
        
        if (!empty($days_limit)) {
            $order_clauses[] = "DATEDIFF(users.date, '$current_date') DESC";
        }
        if (!empty($search_show_users)) {
            $order_clauses[] = "users.multi ASC";
        }
        
        $order_clauses[] = "users.id DESC";
        
        $user_qry .= " ORDER BY " . implode(", ", $order_clauses) . " LIMIT $search_show";
        
        $users_result = mysqli_query($mysqli, $user_qry);
    
    } else {
        
        $tableName="users";        
        $targetpage = "usersPro.php";  
        $limit = 80; 
        
    	$query = "SELECT COUNT(*) as num FROM $tableName WHERE $tableName.permium ='Pro' AND agent = '$agent' ORDER BY `$tableName`.`id`";
        $total_pages = mysqli_fetch_array(mysqli_query($mysqli,$query));
        $total_pages = $total_pages['num'];
        
        $stages = 1;
        $page=1;
        if(isset($_GET['page'])){
            $page = mysqli_real_escape_string($mysqli,$_GET['page']);
        }
        if($page){
            $start = ($page - 1) * $limit; 
        } else {
            $start = 1;    
        }   
        
    	$users_qry="SELECT * FROM $tableName WHERE $tableName.permium ='Pro' AND agent = '$agent' ORDER BY `$tableName`.`id` DESC LIMIT $start, $limit";  
        $users_result=mysqli_query($mysqli,$users_qry);
    }
    
    if (isset($_GET['user_id'])) {
        
        $user_id = (int) $_GET['user_id'];
        $query = "SELECT * FROM users WHERE id = $user_id";
        $result = mysqli_query($mysqli, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $username = mysqli_real_escape_string($mysqli, $row['username']);
            $deleteQuery = "DELETE FROM devices WHERE username = '$username'";
            mysqli_query($mysqli, $deleteQuery);
            $deleteUserQuery = "DELETE FROM users WHERE id = $user_id";
            mysqli_query($mysqli, $deleteUserQuery);
        } else {
            $_SESSION['msg'] = "51";
        }
        header("Location: usersPro.php");
        exit;
    }

    if(isset($_GET['status_deactive_id'])) {
        $data = array('active'  =>  'deactive');
        $edit_status=Update('users', $data, "WHERE id = '".$_GET['status_deactive_id']."'");
        $_SESSION['msg2']="14";
        header( "Location:usersPro.php");
        exit;
    }
    if(isset($_GET['status_active_id'])) {
        $data = array('active'  =>  'active');
        $edit_status=Update('users', $data, "WHERE id = '".$_GET['status_active_id']."'");
        $_SESSION['msg2']="13";
        header( "Location:usersPro.php");
        exit;
    }
    if(isset($_GET['login_deactive_id'])) {
        $data = array('login'  =>  'false');
        $edit_status=Update('users', $data, "WHERE id = '".$_GET['login_deactive_id']."'");
        $_SESSION['msg2']="26";
        header( "Location:usersPro.php");
        exit;
    }
    if(isset($_GET['login_active_id'])) {
        $data = array('login'  =>  'true');
        $edit_status=Update('users', $data, "WHERE id = '".$_GET['login_active_id']."'");
        $_SESSION['msg2']="26";
        header( "Location:usersPro.php");
        exit;
    }
    if(isset($_POST['action'])) {
        if($_POST['action'] == 'delete_selected') {
            if(isset($_POST['selected_users']) && !empty($_POST['selected_users'])) {
                foreach($_POST['selected_users'] as $user_id) {
                    $del_qry = "SELECT * FROM users WHERE id = $user_id";  
                    $del_result = mysqli_query($mysqli, $del_qry);
                    $rowDel = mysqli_fetch_assoc($del_result);
                    $username = $rowDel['username'];
                    $deleteQuery = "DELETE FROM devices WHERE username = '$username'";
                    mysqli_query($mysqli, $deleteQuery);
                    $deleteUserQuery = "DELETE FROM users WHERE id = $user_id";
                    mysqli_query($mysqli, $deleteUserQuery);
                }
                $_SESSION['msg2'] = "29";
                header("Location: usersPro.php");
                exit;
            } else {
                $_SESSION['msg2'] = "30";
                header("Location: usersPro.php");
                exit;
            }
            
        } elseif($_POST['action'] == 'enable_selected') {
            if(isset($_POST['selected_users']) && !empty($_POST['selected_users'])) {
                foreach($_POST['selected_users'] as $user_id) {
                    $data = array('active'  =>  'active');
                    Update('users', $data, "WHERE id = '".$user_id."'");
                }
                $_SESSION['msg2'] = "31";
                header("Location: usersPro.php");
                exit;
            } else {
                $_SESSION['msg2'] = "32";
                header("Location: usersPro.php");
                exit;
            }
            
        } elseif($_POST['action'] == 'disable_selected') {
            if(isset($_POST['selected_users']) && !empty($_POST['selected_users'])) {
                foreach($_POST['selected_users'] as $user_id) {
                    $data = array('active'  =>  'deactive');
                    Update('users', $data, "WHERE id = '".$user_id."'");
                }
                $_SESSION['msg2'] = "33";
                header("Location: usersPro.php");
                exit;
            } else {
                $_SESSION['msg2'] = "34";
                header("Location: usersPro.php");
                exit;
            }
        }
    }
    if (isset($_GET['reset'])) {

    $reset_qry = "SELECT * FROM users WHERE id = '" . $_GET['reset'] . "'";  
    $reset_result = mysqli_query($mysqli, $reset_qry);
    
    if (mysqli_num_rows($reset_result) > 0) {
        $user = mysqli_fetch_assoc($reset_result);
        $utotal = $user['total'];
        $data = array('size' => '0', 'full' => $utotal);
        
        $edit_status = Update('users', $data, "WHERE id = '" . $_GET['reset'] . "'");
        
        if ($edit_status) {
            $_SESSION['msg'] = "26";
        } else {
            $_SESSION['msg'] = "53";
        }
    } else {
        $_SESSION['msg'] = "54";
    }

    header("Location: usersPro.php");
    exit;
}

?>

<div class="row">
    <div class="col-xs-12">
        <div class="card mrg_bottom">
            <div class="page_title_block" style="margin-top: 20px;">
                <div class="col-md-6 col-xs-12">
                    <form method="post" action="">
                        <div class="form-group">
                            <label>جستجو :</label>
                            <input aria-controls="DataTables_Table_0" type="search" name="search_value" class="form-control" placeholder="جستجو">
                        </div>                                
                </div>
                <div class="col-md-3 col-xs-12">
                        <div class="form-group">
                            <label>تعداد نمایش :</label>
                            <input type="number" name="search_show" class="form-control" placeholder="10">
                        </div>
                </div>
                <div class="col-md-3 col-xs-12">
                        <div class="form-group">
                            <label>تعداد کاربر مولتی :</label>
                            <input type="number" name="search_show_users" class="form-control" placeholder="20">
                        </div>
                </div>
            </div>
            <div class="page_title_block" style="margin-top: 10px;">
                <div class="col-md-6 col-xs-12">
                        <div class="form-group">
                            <label>اعتبار (روز باقی مانده) :</label>
                            <input type="text" name="search_day" class="form-control" placeholder="0 به معنی نامحدود">
                        </div>
                </div>
                <div class="col-md-6 col-xs-12">
                        <div class="form-group">
                            <label>حجم (گیگ باقی مانده) :</label>
                            <input type="number" name="search_gb" class="form-control" placeholder="0 به معنی نامحدود">
                        </div>
                </div>
            </div>
            <div class="page_title_block" style="margin-top: 10px;margin-bottom: 3px;">
                <div class="col-xs-12 text-left">
                        <button type="submit" name="search" class="btn btn-primary"><i class="fa fa-search"></i> جستجو</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.form-inline {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
}

.form-inline input[type="number"] {
    width: 80px;
    height: 30px;
    padding: 5px;
    font-size: 14px;
    text-align: center;
}

.form-inline button {
    height: 30px;
    font-size: 14px;
    padding: 0 10px;
}

/* واکنش‌گرایی برای نمایشگرهای کوچک */
@media (max-width: 768px) {
    .form-inline {
        flex-direction: column;
        align-items: stretch;
        gap: 5px;
    }

    .form-inline input[type="number"], 
    .form-inline button {
        width: 100%;
        text-align: center;
    }
}

.blinking-circle {
    display: inline-block;
    width: 13px;
    height: 13px;
    border-radius: 50%;
    margin-left: 8px;
}

.online {
    background-color: #4CAF50;
    animation: blink 1s infinite;
}

.offline {
    background-color: #F44336;
    animation: blink 0s infinite;
}

@keyframes blink {
    0% {
        opacity: 1;
    }
    50% {
        opacity: 0;
    }
    100% {
        opacity: 1;
    }
}
</style>

<div class="row">
    <div class="col-xs-12">
        <div class="card mrg_bottom">
            <div class="page_title_block">
                <div class="col-md-5 col-xs-12">
                        <?php
                        if ($manager_row['isusers'] == '0'){
                            $_SESSION['msg2']="27";
                        	header( "Location:home.php");
                        	exit;
                        }
                        ?>
                <div class="page_title">مدیریت کل کاربران</div>
                </div>
                <div class="col-md-7 col-xs-12"> 
                    <div class="search_list">
                        <div class="add_btn_primary"> <a onclick="copySelectedUsers()">کپی کاربران</a></div> 
                        &nbsp;
                        <!--<?php if ($manager_row['creUsers'] != "0"){ ?>-->
                        <!--    <div class="add_btn_primary"> <a href="add_usersPro.php?add">ساخت انبوه اکانت</a> -->
                        <!--<?php } else {  ?>-->
                        <!--    <div class="add_btn_primary" data-toggle="tooltip" title="دسترسی ندارد">-->
                        <!--        <a style="pointer-events: not-allowed; opacity: 0.6; cursor: not-allowed;">ساخت انبوه اکانت</a>-->
                        <!--<?php }?></div>-->
                        &nbsp;
                        <?php if ($manager_row['creUsers'] != "0"){ ?>
                            <div class="add_btn_primary"> <a href="add_userPro.php?add">اضافه کردن کاربر</a> </div>
                        <?php } else {  ?>
                            <div class="add_btn_primary" data-toggle="tooltip" title="دسترسی ندارد">
                                <a style="pointer-events: not-allowed; opacity: 0.6; cursor: not-allowed;">اضافه کردن کاربر</a></div>
                        <?php }?>
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
            <div class="row mrg-top">
                <div class="col-md-12">
                    <div class="col-md-12 col-sm-12">
                        <?php if (isset($_SESSION['msg2']) && isset($client_lang[$_SESSION['msg2']])) { ?>
                            <div class="alert alert-success alert-dismissible" role="alert"> <button type="button" class="close" data-dismiss="alert" aria-label="Close" id="messageBox"><span aria-hidden="true">×</span></button>
                            <?php echo $client_lang[$_SESSION['msg2']] ;?></a> </div>
                        <?php unset($_SESSION['msg2']);}?>    
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="col-md-12 col-sm-12">
                        <div class="alert alert-dismissible" id="messageBox" role="alert" style="display: none;">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                            <span id="messageText"></span>
                        </div>
                    </div>
                </div>
            </div>
            <form method="post" action="">
                <div class="col-md-12 mrg-top table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                                <tr>
                                <th><input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)"></th>
                                <th class="text-center">شماره</th>                               
                            <?php if ($manager_row['size'] == '10'&& $manager_row['type'] == '2'){ ?>
                                <th class="text-center">مبلغ سرویس</th>
                            <?php } ?>
                                <th class="text-center">مولتی یوزر</th>
                                <th class="text-center">بازدید وضعیت</th>                         
                                <th class="text-center">نام کاربری</th>
                                <th class="text-center">پسورد</th>
                                <th class="text-center">حجم کلی</th>     
                                <th class="text-center">حجم مصرفی</th>
                                <th class="text-center">حجم مانده</th>     
                                <th class="text-center">روزهای کلی</th>     
                                <th class="text-center">روزهای مانده</th>     
                                <th class="text-center">تاریخ ساخت</th>     
                                <th class="text-center">تاریخ ورود</th>     
                                <th class="text-center">تاریخ انقضاء</th>
                                <th class="text-center">لاگین</th>     
                                <th class="text-center">مولتی اکانت</th>     
                                <th class="text-center">وضعیت</th>     
                                <!--<th class="cat_action_list text-center">گزینه ها</th>-->
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            function days_left($expiry_date)
                            {
                                $today = time();
                                $diff = strtotime($expiry_date) - $today;
                                if ($diff < 0) {
                                    return 0;
                                } else {
                                    return ceil($diff / (60 * 60 * 24));
                                }
                            }
                            
                            $i=1;
                            while($users_row=mysqli_fetch_array($users_result)){    
                                $date_day = days_left($users_row['date']);
                                if ($date_day > '365 '){
                                    $dayultra = 'نامحدود';
                                } else {
                                    $dayultra = $date_day.' روز';
                                }
                            ?>
                            <tr>
                                <td><input type="checkbox" name="selected_users[]" value="<?php echo $users_row['id']; ?>" class="user-checkbox"></td>
                                <td class="text-center"><?php echo toPersianNumbers($i++); ?></td>
                                <?php if ($manager_row['size'] == '10'&& $manager_row['type'] == '2'){ ?>
                                <td class="text-center">
                                    <?php echo toPersianNumbers(number_format($users_row['price'])); ?> ت
                                </td>
                                <?php } ?>
                                <td class="text-center"><?php echo toPersianNumbers($users_row['multi']);?> کاربر</td>                                

                                <td class="text-center">
                                    <?php 
                                    if ($users_row['online'] == 'online') {
                                        echo '<span class="blinking-circle online"></span> آنلاین';
                                    } else {
                                        echo '<span class="blinking-circle offline"></span> آفلاین';
                                    }
                                    ?>
                                </td>
                                <td class="text-center">
                                    <span onclick="copyToClipboard(this)" style="cursor: pointer;">
                                        <code><span><?php echo $users_row['username']; ?></span></code>
                                    </span>
                                </td>
                                
                                <td class="text-center">
                                    <span onclick="copyToClipboard(this)" style="cursor: pointer;">
                                        <code><span><?php echo $users_row['password']; ?></span></code>
                                    </span>
                                </td>
                                <td class="text-center"><?php echo toPersianNumbers($users_row['total']);?> گ</td>                                
                                <td class="text-center">
                                <?php 
                                if ($users_row['date'] == '0000-00-00'){echo '➖➖➖'; } else {
                                    if ($users_row['size'] < 1000) {
                                        echo toPersianNumbers($users_row['size']) . " م";
                                    } else {
                                        echo toPersianNumbers(number_format($users_row['size'] / 1000, 2)) . " گ";
                                    }
                                }
                                ?>
                                </td>
                                <td class="text-center" style="
                                    text-align: center; /* وسط چین */
                                    white-space: nowrap; /* جلوگیری از رفتن به خط بعد */
                                    <?php 
                                    $full = (float) $users_row['full'];
                                    
                                    if ($full < 0) {
                                        $full = 0;
                                    }
                                    if ($full == 0) {
                                        echo 'background-color: #f44336; color: white;';
                                        $text = "اتمام حجم";
                                    } elseif ($full <= 3) {
                                        echo 'background-color: #FFA000; color: white;';
                                        $gigs = floor($full);
                                        $megs = intval(($full - $gigs) * 1024);
                                        if ($gigs > 0) {
                                            $text = toPersianNumbers($gigs) . " گیگ" ." ". toPersianNumbers($megs) . " مگ" . '</div>';
                                        } else {
                                            $text = toPersianNumbers($megs) . " مگ" . '</div>';
                                        }
                                    } else {
                                        $gigs = floor($full);
                                        $megs = intval(($full - $gigs) * 1024);
                                        if ($megs > 0) {
                                            $text = toPersianNumbers($gigs) . " گیگ" ." ". toPersianNumbers($megs) . " مگ" . '</div>';
                                        } else {
                                            $text = toPersianNumbers($gigs) . " گیگ" . '</div>';
                                        }
                                    }
                                    ?>">
                                    <?php echo $text; ?>
                                </td>

                                <td class="text-center"><?php echo toPersianNumbers($users_row['adate']).' روز';?></td>
                                
                                <td class="text-center" style="
                                    text-align: center;
                                    white-space: nowrap;
                                    <?php 
                                        $date_day = days_left($users_row['date']);
                                        
                                        if ($date_day > 365) {
                                            $dayultra = 'نامحدود';
                                        } else {
                                            $dayultra = $date_day . ' روز';
                                        }
                                
                                        if ($users_row['date'] === '0000-00-00') {
                                            $text = '➖➖➖';
                                        } elseif ($date_day <= 0) {
                                            echo 'background-color: #f44336; color: white;'; // قرمز برای انقضاء
                                            $text = 'منقضی';
                                        } elseif ($date_day <= 3) {
                                            echo 'background-color: #FFA000; color: white;'; // نارنجی پررنگ برای زیر ۳ روز
                                            $text = $dayultra;
                                        } else {
                                            $text = $dayultra;
                                        }
                                    ?>">
                                    <?php echo toPersianNumbers($text); ?>
                                </td>

                                <td class="text-center"><?php echo jdate("Y-m-d", strtotime($users_row['date_buy'])); ?></td>
                                <td class="text-center" style="text-align: center; white-space: nowrap;"><?php if ($users_row['date_login'] == '0000-00-00'){echo '<span style="color: ' . "#E91E63" . ';">' . "وارد نشده" . '</span>';} else { echo jdate("Y-m-d", strtotime($users_row['date_login'])); }?></td>
                                <td class="text-center"><?php if ($users_row['date'] == '0000-00-00'){echo '➖➖➖'; } else { echo jdate("Y-m-d", strtotime($users_row['date'])); }?></td>
                                
                                <td class="text-center">
                                    <?php if($users_row['login']!="false"){?>
                                        <span class="badge badge-success badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>بله</span></span>
                                    <?php }else{?>
                                        <span class="badge badge-danger badge-icon"><i class="fa fa-times" aria-hidden="true"></i><span>خیر</span></span>
                                    <?php }?>
                                </td>
                                <td class="text-center">
                                    <?php if($users_row['multi']!="0"){?>
                                        <span class="badge badge-success badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>بله</span></span>

                                    <?php }else{?>
                                        <span class="badge badge-danger badge-icon"><i class="fa fa-times" aria-hidden="true"></i><span>خیر</span></span>
                                    <?php }?>
                                </td>
                                <td class="text-center">
                                    <?php if($manager_row['staUsers']!="0"){?>
                                        <?php if($users_row['active']!="deactive"){?>
                                            <a href="usersPro.php?status_deactive_id=<?php echo $users_row['id'];?>" title="تغییر وضعیت"><span class="badge badge-success badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>فعال</span></span></a>
                                        <?php }else{?>
                                            <a href="usersPro.php?status_active_id=<?php echo $users_row['id'];?>" title="تغییر وضعیت"><span class="badge badge-danger badge-icon"><i class="fa fa-times" aria-hidden="true"></i><span>غیرفعال </span></span></a>
                                        <?php }?>
                                    <?php }else{?>
                                    <?php if($users_row['active']!="deactive"){?>
                                            <a title="دسترسی ندارید"><span class="badge badge-success badge-icon" style="pointer-events: not-allowed; opacity: 0.6; cursor: not-allowed;"><i class="fa fa-check" aria-hidden="true"></i><span>فعال</span></span></a>
                                        <?php }else{?>
                                            <a title="دسترسی ندارید"><span class="badge badge-danger badge-icon" style="pointer-events: not-allowed; opacity: 0.6; cursor: not-allowed;"><i class="fa fa-times" aria-hidden="true"></i><span>غیرفعال </span></span></a>
                                    <?php }?>
                                    <?php }?>
                                </td>
                                
                   	            		           
                            </tr>
                            <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>       
                
            <script>
                function copyToClipboard(element) {
                    var text = element.querySelector("span").innerText.trim(); // دریافت متن داخل span
                    navigator.clipboard.writeText(text).then(() => {
                        alert("کپی شد: " + text);
                    }).catch(err => {
                        console.error('خطا در کپی کردن:', err);
                    });
                }

                function showMessage(type, message) {
                    var messageBox = document.getElementById("messageBox");
                    var messageText = document.getElementById("messageText");
                
                    if (message && message.trim() !== "") {
                        messageText.innerHTML = message;
                        messageBox.className = "alert alert-" + type + " alert-dismissible";
                        messageBox.style.display = "block";
                    } else {
                        messageBox.style.display = "none";
                    }
                }
                function renewUser(element) {
                    var userId = element.getAttribute("data-id");
                    var agentId = element.getAttribute("data-agent-id");
                    var messageBox = document.getElementById("messageBox");
                
                    if (confirm('آیا مطمئن هستید که می‌خواهید کاربر را تمدید کنید؟')) {
                        var xhr = new XMLHttpRequest();
                        xhr.open("POST", "renew_user.php", true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        xhr.onreadystatechange = function () {
                            if (xhr.readyState == 4) {
                                try {
                                    var response = JSON.parse(xhr.responseText);
                                    if (xhr.status == 200 && response.success) {
                                        showMessage("success",response.message);
                                        setTimeout(() => location.reload(), 2000);
                                    } else {
                                        showMessage("danger",response.message);
                                    }
                                } catch (e) {
                                    showMessage("danger",response.message);
                                }
                            }
                        };
                        xhr.send("user_id=" + userId + "&agent_id=" + agentId);
                    }
                }
                function toggleSelectAll(selectAllCheckbox) {
                    const checkboxes = document.querySelectorAll('.user-checkbox');
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = selectAllCheckbox.checked;
                    });
                }
                document.addEventListener("DOMContentLoaded", function () {
                    document.querySelectorAll('.confirm-reset').forEach(function (btn) {
                        btn.addEventListener('click', function (e) {
                            e.preventDefault();
                            let url = this.getAttribute('href');
                            if (confirm("آیا مطمئن هستید که حجم کاربر ریست شود؟")) {
                                window.location.href = url;
                            }
                        });
                    });
                });
                function copySelectedUsers() {
                        var selectedUsers = $('[name="selected_users[]"]:checked').map(function(){
                            return this.value;
                        }).get();
                        
                        if(selectedUsers.length > 0) {
                            $.ajax({
                                type: 'POST',
                                url: 'get_data.php',
                                data: {selectedUsers: selectedUsers},
                                success: function(response) {
                                    navigator.clipboard.writeText(response).then(function() {
                                        alert('نام کاربری و رمز عبور کاربران انتخاب شده به کلیپ بورد کپی شد.');
                                    }, function() {
                                        alert('مشکلی در کپی کردن نام کاربری و رمز عبور به وجود آمده است.');
                                    });
                                },
                                error: function() {
                                    alert('خطایی در ارسال درخواست به وجود آمده است.');
                                }
                            });
                        } else {
                            alert('لطفا حداقل یک کاربر را انتخاب کنید.');
                        }
                    }
            </script>

            <div class="form-group">
                <div class="col-md-2">
                    <select name="action" id="action" class="select2">
                        <option value="">انتخاب کنید</option>
                        <option value="enable_selected">فعال کردن کاربران انتخاب شده</option>
                        <option value="disable_selected">غیرفعال کردن کاربران انتخاب شده</option>
                        <?php if ($manager_row['delUsers'] != "0"){
                            if ($manager_row['size'] != "10"){ ?>
                                <option value="delete_selected">حذف کاربران انتخاب شده</option>
                            <?php } ?>
                        <?php } ?>                    
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                    <div class="col-md-2">
                        <button type="submit" id="submitButton" class="btn btn-primary">انجام عملیات</button>
                    </div>
                </div>
    
            <script>
                    $('#action').on('change', function() {
                        var selectedAction = $('[name="action"]').val();
                        alert("مقدار ارسالی: " + selectedAction);
                    });
                
                    $('#submitButton').on('click', function() {
                        var selectedAction = $('[name="action"]').val();
                    });
                </script>

            <div class="col-md-12 col-xs-12">
                    <div class="pagination_item_block">
                        <nav>
                            <?php if(!isset($_POST["user_search"])){ include("pagination.php");}?>                 
                        </nav>
                    </div>
                </div>
                
            <div class="clearfix"></div>
                
            </form>
        </div>
    </div>
</div>

<?php include('includes/footer.php');?>
