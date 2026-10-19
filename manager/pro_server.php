<?php 

    include('includes/header.php'); 
    include('includes/function.php');
	include('language/language.php'); 


	ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    
    if (isset($_SESSION['id'])){
        $manager_qry="SELECT * FROM manager where id='".$_SESSION['id']."'";
        $manager_result=mysqli_query($mysqli,$manager_qry);
        $manager_row=mysqli_fetch_assoc($manager_result);
        $agent_inventory = $manager_row['inventory'];
        $agent_type = $manager_row['type'];
        $agent = $manager_row['owner'];
            
        $qry_servers = "SELECT COUNT(*) as num FROM configs WHERE LOWER(agent) LIKE CONCAT('%', LOWER(?), '%') AND agent NOT LIKE '%|%'";
        $stmt = mysqli_prepare($mysqli, $qry_servers);
        mysqli_stmt_bind_param($stmt, 's', $agent);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $total_servers = mysqli_fetch_array($result);
        $total_servers = $total_servers['num'];
    }
    
    if(isset($_POST['server_search'])) {
        $search_value = addslashes($_POST['search_value']);
        $user_qry = "SELECT * FROM configs WHERE (configs.`name` LIKE '%$search_value%' OR configs.link LIKE '%$search_value%') AND agent NOT LIKE '%|%' ORDER BY configs.id DESC";  
        $users_result = mysqli_query($mysqli, $user_qry);
    } else {
        $tableName = "configs";        
        $targetpage = "pro_server.php";     
        $limit = 100; 
    
        $query = "SELECT COUNT(*) as num FROM $tableName WHERE $tableName.permium = 'Pro' AND LOWER(agent) LIKE CONCAT('%', LOWER(?), '%') AND agent NOT LIKE '%|%'";
        $stmt = mysqli_prepare($mysqli, $query);
        mysqli_stmt_bind_param($stmt, 's', $agent);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $total_pages = mysqli_fetch_array($result);
        $total_pages = $total_pages['num'];
    
        $stages = 3;
        $page = 0;
        if (isset($_GET['page'])) {
            $page = mysqli_real_escape_string($mysqli, $_GET['page']);
        }
        $start = $page ? ($page - 1) * $limit : 0;
    
        $users_qry = "SELECT * FROM $tableName WHERE $tableName.permium = 'Pro' AND LOWER(agent) LIKE CONCAT('%', LOWER(?), '%') AND agent NOT LIKE '%|%' ORDER BY `$tableName`.`id` DESC LIMIT ?, ?";
        $stmt = mysqli_prepare($mysqli, $users_qry);
        mysqli_stmt_bind_param($stmt, 'sii', $agent, $start, $limit);
        mysqli_stmt_execute($stmt);
        $users_result = mysqli_stmt_get_result($stmt);
}

	if(isset($_GET['server_id'])){
		  
		Delete('configs','id='.$_GET['server_id'].'');
		
		$_SESSION['msg']="12";
		header( "Location:pro_server.php");
		exit;
	}
	if(isset($_GET['status_deactive_id'])){
		$data = array('status'  =>  'false');
		
		Update('configs', $data, "WHERE id = '".$_GET['status_deactive_id']."'");
		
		 $_SESSION['msg']="14";
		 header( "Location:pro_server.php");
		 exit;
	}
	if(isset($_GET['status_active_id'])){
		$data = array('status'  =>  'true');
		
		Update('configs', $data, "WHERE id = '".$_GET['status_active_id']."'");
		
		$_SESSION['msg']="13";
		 header( "Location:pro_server.php");
		 exit;
	}
    if(isset($_POST['action'])) {
        if($_POST['action'] == 'delete_selected') {
            if(isset($_POST['selected_users']) && !empty($_POST['selected_users'])) {
                foreach($_POST['selected_users'] as $user_id) {
                    Delete('configs', 'id='.$user_id);
                }
                $_SESSION['msg'] = "60";
                header("Location: pro_server.php");
                exit;
            } else {
                $_SESSION['msg'] = "61";
                header("Location: pro_server.php");
                exit;
            }
            
        } elseif($_POST['action'] == 'enable_selected') {
            if(isset($_POST['selected_users']) && !empty($_POST['selected_users'])) {
                foreach($_POST['selected_users'] as $user_id) {
                    $data = array('status'  =>  'true');
                    Update('configs', $data, "WHERE id = '".$user_id."'");
                }
                $_SESSION['msg'] = "62";
                header("Location: pro_server.php");
                exit;
            } else {
                $_SESSION['msg'] = "63";
                header("Location: pro_server.php");
                exit;
            }
            
        } elseif($_POST['action'] == 'disable_selected') {
            if(isset($_POST['selected_users']) && !empty($_POST['selected_users'])) {
                foreach($_POST['selected_users'] as $user_id) {
                    $data = array('status'  =>  'false');
                    Update('configs', $data, "WHERE id = '".$user_id."'");
                }
                $_SESSION['msg'] = "64";
                header("Location: pro_server.php");
                exit;
            } else {
                $_SESSION['msg'] = "65";
                header("Location: pro_server.php");
                exit;
            }
    }
    }
	if(isset($_GET['reset_size_id'])) {
        $data = array('size' => 0);
        Update('configs', $data, "WHERE id = '".$_GET['reset_size_id']."'");
        $_SESSION['msg'] = "52";
        header("Location:pro_server.php");
        exit;
    }
    

?>

<style>
        .table-responsive {
            overflow-x: auto;
            white-space: nowrap;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            white-space: nowrap;
        }
        .text-center {
            text-align: center;
        }
        .config-cell {
            max-width: 200px;
            overflow-x: auto;
            white-space: nowrap;
        }
    </style>

<div class="row">
    <div class="col-xs-12">
        <div class="card mrg_bottom">
            <div class="page_title_block">
                <div class="col-md-5 col-xs-12">
                    <?php
                        if ($manager_row['isservers'] == '0'){
                            $_SESSION['msg2']="27";
                        	header( "Location:home.php");
                        	exit;
                        }
                    ?>
                    <div class="page_title">مدیریت سرورها</div>
                        </div>
                        <div class="col-md-7 col-xs-12">
                            <div class="search_list">
                                <div class="search_block">
                                    <form method="post" action="">
                                        <input class="form-control input-sm" placeholder="جستجو ..." aria-controls="DataTables_Table_0" type="search" name="search_value" required>
                                        <button type="submit" name="server_search" class="btn-search"><i class="fa fa-search"></i></button>
                                    </form>
                                </div>
                                <?php if ($manager_row['creServers'] != "0"){ ?>
                               		<div class="add_btn_primary"> <a href="add_server_pro.php?add">اضافه کردن سرور</a></div>
                                <?php } else {  ?>
                                <div class="add_btn_primary" data-toggle="tooltip" title="دسترسی ندارید">
                                    <a style="pointer-events: not-allowed; opacity: 0.6; cursor: not-allowed;">اضافه کردن سرور</a>
                                </div>  
                                <?php }?>
                            </div>
                        </div>
                    </div>
                <div class="clearfix"></div>
                <div class="row mrg-top">
                    <div class="col-md-12">
                        <div class="col-md-12 col-sm-12">
                            <?php if(isset($_SESSION['msg'])){?> 
                            <div class="alert alert-success alert-dismissible" role="alert">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                                <?php echo $client_lang[$_SESSION['msg']] ; ?>
                            </div>
                            <?php unset($_SESSION['msg']);}?>    
                        </div>
                    </div>
                </div>
            <form method="post" action="">
                    <div class="col-md-12 mrg-top table-responsive">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                <th><input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)"></th>
                                <th class="text-center">نام سرور فارسی</th>                         
                                <th class="text-center">نام سرور لاتین</th>                         
                                <th class="text-center">کانفیگ</th>
                                <th class="text-center">حجم مصرفی</th>
                                <th class="text-center">لوکیشن فارسی</th>
                                <th class="text-center">لوکیشن لاتین</th>
                                <th class="text-center">تاریخ ساخت</th>                
                                <th class="text-center">فرگمنت</th>
                                <th class="text-center">وضعیت</th>    
                                <th class="cat_action_list text-center">گزینه ها</th>  
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $i=1;
                                    while($users_row=mysqli_fetch_array($users_result))
                                    {
                                ?>
                                <tr>
                                    <td><input type="checkbox" name="selected_users[]" value="<?php echo $users_row['id']; ?>" class="user-checkbox"></td>
                                    <td class="text-center"><?php echo $users_row['name_fa'];?></td>
                                    <td class="text-center"><?php echo $users_row['name_en'];?></td>
                                    <td class="text-center config-cell"><sup><code><?php echo $users_row['link'];?></code></sup></td>
                                    <td class="text-center">
                                    <?php 
                                    if ($users_row['size'] < 1000) {
                                        echo $users_row['size'] . "M";
                                    } else {
                                        echo number_format($users_row['size'] / 1000, 2) . "G";
                                    }
                                    ?>
                                    </td>
                                    <td class="text-center"><?php echo $users_row['locations_fa'];?></td>                        
                                    <td class="text-center"><?php echo $users_row['locations_en'];?></td>                        
                                    <td class="text-center"><?php echo jdate("Y-m-d", strtotime($users_row['date'])); ?></td>
                                    <td class="text-center">
                                        <?php if($users_row['fragment']!="false"){?>
                                            <span class="badge badge-success badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>فعال</span></span>
                                        <?php }else{?>
                                            <span class="badge badge-danger badge-icon"><i class="fa fa-times" aria-hidden="true"></i><span>غیرفعال</span></span>
                                        <?php }?>
                                    </td>
                                    <td class="text-center">
                                        <?php if($manager_row['staServers']!="0"){?>
                                            <?php if($users_row['status']!="false"){?>
                                                <a href="pro_server.php?status_deactive_id=<?php echo $users_row['id'];?>" title="تغییر وضعیت"><span class="badge badge-success badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>فعال</span></span></a>
        
                                            <?php }else{?>
                                                <a href="pro_server.php?status_active_id=<?php echo $users_row['id'];?>" title="تغییر وضعیت"><span class="badge badge-danger badge-icon"><i class="fa fa-times" aria-hidden="true"></i><span>غیرفعال</span></span></a>
                                            <?php }?>
                                        <?php }else{?>
                                        <?php if($users_row['status']!="false"){?>
                                                <a title="دسترسی ندارید"><span class="badge badge-success badge-icon" style="pointer-events: not-allowed; opacity: 0.6; cursor: not-allowed;"><i class="fa fa-check" aria-hidden="true"></i><span>فعال</span></span></a>
        
                                                <?php }else{?>
                                                <a title="دسترسی ندارید"><span class="badge badge-danger badge-icon" style="pointer-events: not-allowed; opacity: 0.6; cursor: not-allowed;"><i class="fa fa-times" aria-hidden="true"></i><span>غیرفعال</span></span></a>
                                            <?php }?>
                                            <?php }?>
                                    </td>
                       	            <td class="text-center">
                       	                <?php if ($manager_row['type'] === "0"){ ?>
                       	                    <a href="pro_server.php?reset_size_id=<?php echo $users_row['id']; ?>" class="btn btn-success btn_edit" onclick="return confirm('آیا مطمئن هستید که حجم این سرور ریست شود؟')">ریست حجم</a>
                                       	<?php }?>
                                       	<?php if ($manager_row['editServers'] != "0"){ ?>
                                       		<a href="add_server_pro.php?server_id=<?php echo $users_row['id'];?>" data-toggle="tooltip" title="ویرایش" class="btn btn-primary btn_edit"><i class="fa fa-edit"></i></a
                                       		>
                                        <?php } else {  ?>
                                       		<a data-toggle="tooltip" title="دسترسی ندارید" class="btn btn-primary btn_edit" disabled><i class="fa fa-edit"></i></a>
                                       	<?php }?>
                                       	
                                       	<?php if ($manager_row['delServers'] != "0"){ ?>
                                            <a href="pro_server.php?server_id=<?php echo $users_row['id'];?>" data-toggle="tooltip" title="حذف" class="btn btn-danger btn_delete" onclick="return confirm('آیا میخواهید این حساب را حذف نمایید؟')"><i class="fa fa-trash"></i></a>
                                        <?php } else {  ?>
                                            <a data-toggle="tooltip" title="دسترسی ندارید" class="btn btn-danger btn_delete" disabled><i class="fa fa-trash"></i></a>
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
                function toggleSelectAll(selectAllCheckbox) {
                    const checkboxes = document.querySelectorAll('.user-checkbox');
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = selectAllCheckbox.checked;
                    });
                }
            </script>

            <div class="form-group">
                <div class="col-md-2">
                    <select name="action" id="action" class="select2">
                        <option value="">انتخاب کنید</option>
                        <option value="enable_selected">فعال کردن سرور</option>
                        <option value="disable_selected">غیرفعال کردن سرور</option>
                        <option value="delete_selected">حذف سرور</option>
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