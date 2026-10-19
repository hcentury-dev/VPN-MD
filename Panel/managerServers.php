<?php 
    include('includes/header.php'); 
    include('includes/function.php');
    include('language/language.php');  

    if(isset($_POST['server_search'])) {
        $user_qry = "SELECT configs.*, 
                    GROUP_CONCAT(agents.name SEPARATOR ', ') AS agent_names
                    FROM configs 
                    LEFT JOIN agents ON FIND_IN_SET(agents.agent, REPLACE(configs.agent, '|', ',')) > 0
                    WHERE configs.`name` LIKE '%".addslashes($_POST['search_value'])."%' 
                    OR configs.link LIKE '%".addslashes($_POST['search_value'])."%'
                    GROUP BY configs.id
                    ORDER BY configs.id DESC";
                               
        $users_result = mysqli_query($mysqli, $user_qry);
    } else {
        $tableName = "configs";        
        $targetpage = "managerServers.php";     
        $limit = 100; 
        
        $query = "SELECT COUNT(*) as num FROM $tableName WHERE $tableName.permium = 'Pro' AND $tableName.agent != 'ADMIN'";
        $total_pages = mysqli_fetch_array(mysqli_query($mysqli, $query));
        $total_pages = $total_pages['num'];
        
        $stages = 3;
        $page = 0;
        if(isset($_GET['page'])){
            $page = mysqli_real_escape_string($mysqli, $_GET['page']);
        }
        if($page){
            $start = ($page - 1) * $limit; 
        } else {
            $start = 0;    
        }    
            
        $users_qry = "SELECT configs.*, 
                     GROUP_CONCAT(agents.name SEPARATOR ', ') AS agent_name
                     FROM $tableName 
                     LEFT JOIN agents ON FIND_IN_SET(agents.agent, REPLACE(configs.agent, '|', ',')) > 0
                     WHERE $tableName.permium = 'Pro' AND $tableName.agent != 'ADMIN'
                     GROUP BY configs.id
                     ORDER BY configs.id DESC 
                     LIMIT $start, $limit";
        $users_result = mysqli_query($mysqli, $users_qry);
    }
	 
	if(isset($_GET['server_id'])){
		  
		Delete('configs','id='.$_GET['server_id'].'');
		
		$_SESSION['msg']="12";
		header( "Location:managerServers.php");
		exit;
	}
	if(isset($_GET['status_deactive_id'])){
		$data = array('status'  =>  'false');
		
		Update('configs', $data, "WHERE id = '".$_GET['status_deactive_id']."'");
		
		 $_SESSION['msg']="14";
		 header( "Location:managerServers.php");
		 exit;
	}
	if(isset($_GET['status_active_id'])){
		$data = array('status'  =>  'true');
		
		Update('configs', $data, "WHERE id = '".$_GET['status_active_id']."'");
		
		$_SESSION['msg']="13";
		 header( "Location:managerServers.php");
		 exit;
	}
	if(isset($_GET['permium_d'])){
	        
	    $title = "رایگان";
		$data = array('title'  =>  $title,'permium'  =>  'Free');
		
		Update('configs', $data, "WHERE id = '".$_GET['permium_d']."'");
		
		 $_SESSION['msg']="24";
		 header( "Location:managerServers.php");
		 exit;
	}
	if(isset($_GET['permium_a'])){
	    $title = "اختصاصی";
		$data = array('title'  =>  $title,'permium'  =>  'Pro');
		
		Update('configs', $data, "WHERE id = '".$_GET['permium_a']."'");
		
		$_SESSION['msg']="25";
		 header( "Location:managerServers.php");
		 exit;
	}
	
    $admin_qry="SELECT * FROM admin where id='".$_SESSION['id']."'";
		$admin_result=mysqli_query($mysqli,$admin_qry);
		$admin_row=mysqli_fetch_assoc($admin_result);
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
    </style>
</head>
<body>
    <div class="row">
        <div class="col-xs-12">
            <div class="card mrg_bottom">
                <div class="page_title_block">
                    <div class="col-md-5 col-xs-12">
                        <?php if ($admin_row['is_admin'] == 'false'){
                            $_SESSION['msg']="27";
                    		header( "Location:home.php");
                    		exit;
                        }
                        ?>
                        <div class="page_title">مدیریت سرورهای همکاران</div>
                    </div>
                    <div class="col-md-7 col-xs-12">
                        <div class="search_list">
                            <div class="search_block">
                                <form method="post" action="">
                                    <input class="form-control input-sm" placeholder="جستجو ..." aria-controls="DataTables_Table_0" type="search" name="search_value" required>
                                    <button type="submit" name="server_search" class="btn-search"><i class="fa fa-search"></i></button>
                                </form>
                            </div>
                            <div class="add_btn_primary"> <a href="add_managerServers.php?add">اضافه کردن سرور همکار</a> </div>
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
                <div class="col-md-12 mrg-top table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>                                
                            <th class="cat_action_list text-center">گزینه ها</th>                              
                                <th class="text-center">نام سرور فارسی</th>                         
                                <th class="text-center">نام سرور لاتین</th>                         
                                <th class="text-center">کانفیگ</th>
                                <th class="text-center">حجم مصرفی</th>
                                <th class="text-center">لوکیشن فارسی</th>
                                <th class="text-center">لوکیشن لاتین</th>
                                <th class="text-center">تاریخ ساخت</th>                
                                <th class="text-center">فرگمنت</th>
                                <th class="text-center">وضعیت</th>    
                                <th class="text-center">همکار</th>    
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $i=1;
                                while($users_row=mysqli_fetch_array($users_result))
                                {     
                            ?>
                            <tr>                                
                                <td class="text-center">
                                    <a href="add_managerServers.php?server_id=<?php echo $users_row['id'];?>" data-toggle="tooltip" title="ویرایش" class="btn btn-primary btn_edit"><i class="fa fa-edit"></i></a>
                                    <a href="managerServers.php?server_id=<?php echo $users_row['id'];?>" data-toggle="tooltip" title="حذف" class="btn btn-danger btn_delete" onclick="return confirm('آیا میخواهید این سرور را حذف نمایید؟')"><i class="fa fa-trash"></i></a>
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
                                <td class="text-center"><?php echo $users_row['date'];?></td>
                                <td class="text-center">
                                    <?php if($users_row['fragment']!="false"){?>
                                        <span class="badge badge-success badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>فعال</span></span>
                                    <?php }else{?>
                                        <span class="badge badge-danger badge-icon"><i class="fa fa-times" aria-hidden="true"></i><span>غیرفعال</span></span>
                                    <?php }?>
                                </td>
                                <td class="text-center">
                                    <?php if($users_row['status']!="false"){?>
                                        <a href="managerServers.php?status_deactive_id=<?php echo $users_row['id'];?>" title="تغییر وضعیت"><span class="badge badge-success badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>فعال</span></span></a>
                                    <?php }else{?>
                                        <a href="managerServers.php?status_active_id=<?php echo $users_row['id'];?>" title="تغییر وضعیت"><span class="badge badge-danger badge-icon"><i class="fa fa-times" aria-hidden="true"></i><span>غیرفعال</span></span></a>
                                    <?php }?>
                                </td>
                                
                                <?php
                                /*
                                // <td class="text-center">
                                //     <td class="text-center config-cell"><sup><code><?php echo (!empty($users_row['agent_name'])) ? $users_row['agent_name'] : '<span class="badge badge-warning">نامشخص</span>';  ?></code></sup></td>
                                // </td>
                                */
                                ?>
                                
                                
                                <td class="text-center config-cell">
                                <sup><code>
                                <?php 
                                if (!empty($users_row['agent_name'])) {
                            
                                    if ($users_row['agent_name'] === 'all') {
                                        // گرفتن فقط نماینده‌هایی که در جدول manager وجود دارند
                                        $qry_valid_agents = "
                                            SELECT a.name 
                                            FROM agents a
                                            INNER JOIN manager m ON a.name = m.username
                                        ";
                                        $result_valid_agents = mysqli_query($mysqli, $qry_valid_agents);
                            
                                        $valid_agents = [];
                                        while ($row = mysqli_fetch_assoc($result_valid_agents)) {
                                            $valid_agents[] = $row['name'];
                                        }
                            
                                        if (!empty($valid_agents)) {
                                            echo htmlspecialchars(implode(', ', $valid_agents));
                                        } else {
                                            echo '<span class="badge badge-warning">نامشخص</span>';
                                        }
                            
                                    } else {
                                        // اگر نماینده‌های مشخص انتخاب شده‌اند
                                        $selected_agents = explode(',', $users_row['agent_name']);
                                        $valid_agents = [];
                            
                                        // بررسی اینکه فقط نماینده‌هایی نمایش داده شوند که در manager هستند
                                        foreach ($selected_agents as $agent) {
                                            $agent = trim($agent);
                                            $chk_qry = "SELECT 1 FROM manager WHERE username = '".mysqli_real_escape_string($mysqli, $agent)."' LIMIT 1";
                                            $chk_res = mysqli_query($mysqli, $chk_qry);
                                            if (mysqli_num_rows($chk_res) > 0) {
                                                $valid_agents[] = $agent;
                                            }
                                        }
                            
                                        if (!empty($valid_agents)) {
                                            echo htmlspecialchars(implode(', ', $valid_agents));
                                        } else {
                                            echo '<span class="badge badge-warning">نامشخص</span>';
                                        }
                                    }
                            
                                } else {
                                    echo '<span class="badge badge-warning">نامشخص</span>';
                                }
                                ?>
                                </code></sup>
                            </td>



                            </tr>
                            <?php
                                }
                            ?>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-12 col-xs-12">
                    <div class="pagination_item_block">
                        <nav>
                            <?php if(!isset($_POST["server_search"])){ include("pagination.php");}?>                 
                        </nav>
                    </div>
                </div>
                <div class="clearfix"></div>
            </div>
        </div>
    </div>
</body>
</html>


<?php include('includes/footer.php');?>                  