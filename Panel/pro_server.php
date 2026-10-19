<?php 

    include('includes/header.php'); 
    include('includes/function.php');
	include('language/language.php');  
    include('includes/jdf.php'); 

	if(isset($_POST['server_search'])) {
		$user_qry="SELECT * FROM configs WHERE configs.`name` like '%".addslashes($_POST['search_value'])."%' or configs.link like '%".addslashes($_POST['search_value'])."%' ORDER BY configs.id DESC";  
							 
		$users_result=mysqli_query($mysqli,$user_qry);
	 } else {
	 
		$tableName="configs";		
		$targetpage = "pro_server.php"; 	
		$limit = 100; 
		
		$query = "SELECT COUNT(*) as num FROM $tableName WHERE $tableName.permium ='Pro' AND $tableName.agent NOT LIKE '%|%' ORDER BY `$tableName`.`id`";
		$total_pages = mysqli_fetch_array(mysqli_query($mysqli,$query));
		$total_pages = $total_pages['num'];
		
		$stages = 3;
		$page=0;
		if(isset($_GET['page'])){
		$page = mysqli_real_escape_string($mysqli,$_GET['page']);
		}
		if($page){
			$start = ($page - 1) * $limit; 
		}else{
			$start = 0;	
		}	
			
		$users_qry="SELECT * FROM $tableName WHERE $tableName.permium ='Pro' AND $tableName.agent NOT LIKE '%|%' ORDER BY `$tableName`.`id` DESC LIMIT $start, $limit";  
		$users_result=mysqli_query($mysqli,$users_qry);
							
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
        $_SESSION['msg'] = "51";
        header("Location:pro_server.php");
        exit;
    }
?>

<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت سرورهای اختصاصی</title>
    <link rel="stylesheet" href="path/to/font-awesome/css/font-awesome.min.css">
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
        .icon-size {
            width: 50px !important;
            height: 32px !important;
        }
    </style>
</head>
<body>
    <div class="row">
        <div class="col-xs-12">
            <div class="card mrg_bottom">
                <div class="page_title_block">
                    <div class="col-md-5 col-xs-12">
                        <div class="page_title">مدیریت سرورهای اختصاصی</div>
                    </div>
                    <div class="col-md-7 col-xs-12">
                        <div class="search_list">
                            <div class="search_block">
                                <form method="post" action="">
                                    <input class="form-control input-sm" placeholder="جستجو ..." aria-controls="DataTables_Table_0" type="search" name="search_value" required>
                                    <button type="submit" name="server_search" class="btn-search"><i class="fa fa-search"></i></button>
                                </form>
                            </div>
                            <div class="add_btn_primary"> <a href="add_server_pro.php?add">اضافه کردن سرور اختصاصی</a> </div>
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
                                <th class="cat_action_list text-center">گزینه ها</th>
                                <th class="text-center">پرچم</th>                         
                                <th class="text-center">نام سرور فارسی</th>                         
                                <th class="text-center">نام سرور لاتین</th>                         
                                <th class="text-center">کانفیگ</th>
                                <th class="text-center">حجم مصرفی</th>
                                <th class="text-center">لوکیشن فارسی</th>
                                <th class="text-center">لوکیشن لاتین</th>
                                <th class="text-center">تاریخ ساخت</th>                
                                <th class="text-center">فرگمنت</th>
                                <th class="text-center">وضعیت</th>    
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            
                            if (isset($_SERVER['HTTPS'] ) ) {  
                                $file_path = 'https://'.$_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']).'/images/Flag/';
                            } else {
                                $file_path = 'http://'.$_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']).'/images/Flag/';
                            }
                                $i=1;
                                
                                
                                //////////////////////////////////////////////
                                $tableName="configs";        
                                $targetpage = "pro_server.php";     
                                $limit = 100; 
                                
                                // 1. شمارش کل ردیف‌ها (برای صفحه‌بندی)
                                $query = "SELECT COUNT(*) as num FROM $tableName 
                                          WHERE $tableName.permium ='Pro' 
                                            AND $tableName.agent = 'ADMIN' 
                                            AND $tableName.agent NOT LIKE '%|%' 
                                          ORDER BY `$tableName`.`id`";
                                $total_pages = mysqli_fetch_array(mysqli_query($mysqli,$query));
                                $total_pages = $total_pages['num'];
                                
                                // 2. گرفتن ردیف‌ها برای نمایش
                                $stages = 3;
                                $page=0;
                                if(isset($_GET['page'])){
                                    $page = mysqli_real_escape_string($mysqli,$_GET['page']);
                                }
                                if($page){
                                    $start = ($page - 1) * $limit; 
                                }else{
                                    $start = 0;    
                                }    
                                
                                $users_qry="SELECT * FROM $tableName 
                                            WHERE $tableName.permium ='Pro' 
                                              AND $tableName.agent = 'ADMIN' 
                                              AND $tableName.agent NOT LIKE '%|%' 
                                            ORDER BY `$tableName`.`id` DESC 
                                            LIMIT $start, $limit";  
                                $users_result=mysqli_query($mysqli,$users_qry);
                                ////////////////////////////////////////
                                
                                
                                while($users_row=mysqli_fetch_array($users_result))
                                {     
                            ?>
                            <tr>
                                <td><input type="checkbox" name="selected_users[]" value="<?php echo $users_row['id']; ?>" class="user-checkbox"></td>                                <td class="text-center">
                                    <a href="pro_server.php?reset_size_id=<?php echo $users_row['id']; ?>" class="btn btn-success btn_edit" onclick="return confirm('آیا مطمئن هستید که حجم این سرور ریست شود؟')">ریست حجم</a>
                                    <a href="add_server_pro.php?server_id=<?php echo $users_row['id'];?>" data-toggle="tooltip" title="ویرایش" class="btn btn-primary btn_edit"><i class="fa fa-edit"></i></a>
                                    <a href="pro_server.php?server_id=<?php echo $users_row['id'];?>" data-toggle="tooltip" title="حذف" class="btn btn-danger btn_delete" onclick="return confirm('آیا میخواهید این سرور را حذف نمایید؟')"><i class="fa fa-trash"></i></a>
                                </td>
                                <td>
                                    <img src="<?php echo $file_path . $users_row['src']; ?>"
                                         class="icon-size">
                                </td>
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
                                    <?php if($users_row['status']!="false"){?>
                                        <a href="pro_server.php?status_deactive_id=<?php echo $users_row['id'];?>" title="تغییر وضعیت"><span class="badge badge-success badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>فعال</span></span></a>
                                    <?php }else{?>
                                        <a href="pro_server.php?status_active_id=<?php echo $users_row['id'];?>" title="تغییر وضعیت"><span class="badge badge-danger badge-icon"><i class="fa fa-times" aria-hidden="true"></i><span>غیرفعال</span></span></a>
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