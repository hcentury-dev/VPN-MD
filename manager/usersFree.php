<?php 

include('includes/header.php'); 
include('includes/function.php');
include('language/language.php');  

if(isset($_POST['user_search'])) {
    $user_qry="SELECT * FROM users WHERE users.`username` like '%".addslashes($_POST['search_value'])."%' or users.password like '%".addslashes($_POST['search_value'])."%' ORDER BY users.id DESC";  
    $users_result=mysqli_query($mysqli,$user_qry);
} else {
    $tableName="users";        
    $targetpage = "usersFree.php";  
    $limit = 80; 
    
	$query = "SELECT COUNT(*) as num FROM $tableName WHERE $tableName.permium ='Free' ORDER BY `$tableName`.`id`";
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
    
	$users_qry="SELECT * FROM $tableName WHERE $tableName.permium ='Free' ORDER BY `$tableName`.`id` DESC LIMIT $start, $limit";  
    $users_result=mysqli_query($mysqli,$users_qry);
}

if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    $query = "SELECT username FROM users WHERE id = $user_id";
    $result = mysqli_query($mysqli, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $username = $row['username'];

        $deleteQuery = "DELETE FROM devices WHERE username = '$username'";
        mysqli_query($mysqli, $deleteQuery);

        $deleteUserQuery = "DELETE FROM users WHERE id = $user_id";
        mysqli_query($mysqli, $deleteUserQuery);

        $_SESSION['msg'] = "12";
    } else {
        $_SESSION['msg'] = "51";
    }
    header( "Location:usersFree.php");
    exit;
}

//Active and Deactive status
if(isset($_GET['status_deactive_id'])) {
    $data = array('active'  =>  'deactive');
    $edit_status=Update('users', $data, "WHERE id = '".$_GET['status_deactive_id']."'");
    $_SESSION['msg']="14";
    header( "Location:usersFree.php");
    exit;
}
if(isset($_GET['status_active_id'])) {
    $data = array('active'  =>  'active');
    $edit_status=Update('users', $data, "WHERE id = '".$_GET['status_active_id']."'");
    $_SESSION['msg']="13";
    header( "Location:usersFree.php");
    exit;
}

//Active and Deactive login
if(isset($_GET['login_deactive_id'])) {
    $data = array('login'  =>  'false');
    $edit_status=Update('users', $data, "WHERE id = '".$_GET['login_deactive_id']."'");
    $_SESSION['msg']="26";
    header( "Location:usersFree.php");
    exit;
}
if(isset($_GET['login_active_id'])) {
    $data = array('login'  =>  'true');
    $edit_status=Update('users', $data, "WHERE id = '".$_GET['login_active_id']."'");
    $_SESSION['msg']="26";
    header( "Location:usersFree.php");
    exit;
}

if(isset($_POST['action'])) {
    if($_POST['action'] == 'delete_selected') {
        if(isset($_POST['selected_users']) && !empty($_POST['selected_users'])) {
            foreach($_POST['selected_users'] as $user_id) {
                Delete('users', 'id='.$user_id);
            }
            $_SESSION['msg'] = "29";
            header("Location: usersFree.php");
            exit;
        } else {
            $_SESSION['msg'] = "30";
            header("Location: usersFree.php");
            exit;
        }
        
    } elseif($_POST['action'] == 'enable_selected') {
        if(isset($_POST['selected_users']) && !empty($_POST['selected_users'])) {
            foreach($_POST['selected_users'] as $user_id) {
                $data = array('active'  =>  'active');
                Update('users', $data, "WHERE id = '".$user_id."'");
            }
            $_SESSION['msg'] = "31";
            header("Location: usersFree.php");
            exit;
        } else {
            $_SESSION['msg'] = "32";
            header("Location: usersFree.php");
            exit;
        }
        
    } elseif($_POST['action'] == 'disable_selected') {
        if(isset($_POST['selected_users']) && !empty($_POST['selected_users'])) {
            foreach($_POST['selected_users'] as $user_id) {
                $data = array('active'  =>  'deactive');
                Update('users', $data, "WHERE id = '".$user_id."'");
            }
            $_SESSION['msg'] = "33";
            header("Location: usersFree.php");
            exit;
        } else {
            $_SESSION['msg'] = "34";
            header("Location: usersFree.php");
            exit;
        }
        
    }
}
?>

<div class="row">
    <div class="col-xs-12">
        <div class="card mrg_bottom">
            <div class="page_title_block">
                <div class="col-md-5 col-xs-12">
                    <div class="page_title">مدیریت کل کاربران</div>
                </div>
                <div class="col-md-7 col-xs-12"> 
                    <div class="search_list">
                        <div class="search_block">
                            <form  method="post" action="">
                                <input class="form-control input-sm" placeholder="جستجو ..." aria-controls="DataTables_Table_0" type="search" name="search_value" required>
                                <button type="submit" name="user_search" class="btn-search"><i class="fa fa-search"></i></button>
                            </form>  
                        </div>             
                        <div class="add_btn_primary"> <a onclick="copySelectedUsers()">کپی کاربران</a> </div>&nbsp;
                        <div class="add_btn_primary"> <a href="add_usersFree.php?add">ساخت انبوه اکانت</a> </div>&nbsp;
                        <div class="add_btn_primary"> <a href="add_userFree.php?add">اضافه کردن کاربر</a> </div>
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
            <div class="row mrg-top">
                <div class="col-md-12">
                    <div class="col-md-12 col-sm-12">
                        <?php if(isset($_SESSION['msg'])){?> 
                            <div class="alert alert-success alert-dismissible" role="alert"> <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                            <?php echo $client_lang[$_SESSION['msg']] ; ?></a> </div>
                        <?php unset($_SESSION['msg']);}?>    
                    </div>
                </div>
            </div>
            <form method="post" action="">
                <div class="col-md-12 mrg-top table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th class="text-center">انتخاب</th>
                                <th class="text-center">شماره</th>
                                <th class="text-center">بازدید وضعیت</th>                         
                                <th class="text-center">نام</th>
                                <th class="text-center">حجم مصرفی</th>
                                <th class="text-center">حجم کلی</th>     
                                <th class="text-center">روز های مانده</th>     
                                <th class="text-center">تاریخ ساخت</th>     
                                <th class="text-center">تاریخ انقضاء</th>
                                <th class="text-center">لاگین</th>     
                                <th class="text-center">مولتی اکانت</th>     
                                <th class="text-center">وضعیت</th>     
                                <th class="cat_action_list text-center">گزینه ها</th>
                                <th class="text-center">سازنده</th>     
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
                                
                                <td class="text-center">
                                <input type="checkbox" name="selected_users[]" value="<?php echo $users_row['id']; ?>">
                                </td><td class="text-center"><?php echo $i++; ?></td>
                                <td class="text-center"><?php if ($users_row['online'] == 'online') {echo '🟢آنلاین';} else { echo '🔴آفلاین'; }?></td>
                                <td class="text-center"><?php echo $users_row['username'];?></td>
                                <td class="text-center">
                                <?php 
                                if ($users_row['size'] < 1000) {
                                    echo $users_row['size'] . "M";
                                } else {
                                    echo number_format($users_row['size'] / 1000, 2) . "G";
                                }
                                ?>
                                </td>
                                <td class="text-center"><?php echo $users_row['total'];?>G</td>
                                <td class="text-center"><?php echo $dayultra;?></td>
                                <td class="text-center"><?php echo $users_row['date_buy'];?></td>
                                <td class="text-center"><?php if ( $users_row['date'] == '0000-00-00'){echo 'وارد نشده'; } else { echo $users_row['date']; }?></td>
                                <td class="text-center">

                                    <?php if($users_row['login']!="false"){?>
                                        <a href="usersFree.php?login_deactive_id=<?php echo $users_row['id'];?>" title="تغییر وضعیت"><span class="badge badge-success badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>بله</span></span></a>

                                    <?php }else{?>
                                        <a href="usersFree.php?login_active_id=<?php echo $users_row['id'];?>" title="تغییر وضعیت"><span class="badge badge-danger badge-icon"><i class="fa fa-times" aria-hidden="true"></i><span>خیر</span></span></a>
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

                                    <?php if($users_row['active']!="deactive"){?>
                                        <a href="usersFree.php?status_deactive_id=<?php echo $users_row['id'];?>" title="تغییر وضعیت"><span class="badge badge-success badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>فعال</span></span></a>

                                    <?php }else{?>
                                        <a href="usersFree.php?status_active_id=<?php echo $users_row['id'];?>" title="تغییر وضعیت"><span class="badge badge-danger badge-icon"><i class="fa fa-times" aria-hidden="true"></i><span>غیرفعال </span></span></a>
                                    <?php }?>
                                </td>
                                <td class="text-center">
                                    <a href="add_userFree.php?user_id=<?php echo $users_row['id'];?>" data-toggle="tooltip" title="ویرایش" class="btn btn-primary btn_edit"><i class="fa fa-edit"></i></a>

                                    <a href="usersFree.php?user_id=<?php echo $users_row['id'];?>" data-toggle="tooltip" title="حذف" class="btn btn-danger btn_delete" onclick="return confirm('آیا میخواهید این حساب را حذف نمایید؟')"><i class="fa fa-trash"></i></a>
                                </td>
                                <td class="text-center"><?php echo $users_row['owner'];?></td>
                            </tr>
                            <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>       
                
                <script>
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
