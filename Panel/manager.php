<?php 

    include('includes/header.php'); 
    include('includes/function.php');
	include('language/language.php'); 
	include('includes/jdf.php'); 

	$admin_qry="SELECT * FROM admin where id='".$_SESSION['id']."'";
	$admin_result=mysqli_query($mysqli,$admin_qry);
	$admin_row=mysqli_fetch_assoc($admin_result);
    
    if(isset($_SESSION['id']))
	{
		$manager_qry="SELECT * FROM manager where id='".$_SESSION['id']."'";
		$manager_result=mysqli_query($mysqli,$manager_qry);
		$manager_row=mysqli_fetch_assoc($manager_result);
	}
	
	if(isset($_GET['user_id']))
	{
			 
		$manager_qry="SELECT * FROM manager where id='".$_GET['user_id']."'";
		$manager_result=mysqli_query($mysqli,$manager_qry);
		$manager_row=mysqli_fetch_assoc($manager_result);
		
	}
	
	if(isset($_POST['user_search']))
	 {
		 
		$user_qry="SELECT * FROM manager WHERE manager.`username` like '%".addslashes($_POST['search_value'])."%' or manager.password like '%".addslashes($_POST['search_value'])."%' ORDER BY manager.id DESC";  
							 
		$users_result=mysqli_query($mysqli,$user_qry);
		
	 } else {
	 
		$tableName="manager";		
		$targetpage = "manager.php"; 	
		$limit = 50; 
		
		$query = "SELECT COUNT(*) as num FROM $tableName";
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
			
		$users_qry="SELECT * FROM manager ORDER BY manager.id DESC LIMIT $start, $limit";  
		$users_result=mysqli_query($mysqli,$users_qry);
							
	 }
	 
    if (isset($_GET['delete_id'])) {
        
        $delete_id = $_GET['delete_id'];
        
        mysqli_query($mysqli,"DELETE FROM manager WHERE owner='".$delete_id."'");
        mysqli_query($mysqli,"DELETE FROM users WHERE agent='".$delete_id."'");
        mysqli_query($mysqli,"DELETE FROM agents WHERE agent='".$delete_id."'");
        mysqli_query($mysqli,"DELETE FROM configs WHERE agent='".$delete_id."'");
    
        $_SESSION['msg'] = "12";
        header("Location: manager.php");
        exit;
        
    }

	//Active and Deactive status
	if (isset($_GET['status_deactive_id']))
	{
		$data = array('status'  =>  'deactive');
		Update('manager', $data, "WHERE id = '".$_GET['status_deactive_id']."'");
		$_SESSION['msg']="14";
		header( "Location:manager.php");
		exit;
	}
	if (isset($_GET['status_active_id']))
	{
		$data = array('status'  =>  'active');
		
		$edit_status=Update('manager', $data, "WHERE id = '".$_GET['status_active_id']."'");
		
		$_SESSION['msg']="13";
		 header( "Location:manager.php");
		 exit;
	}
	
	//Active and Deactive login
	if (isset($_GET['login_deactive_id']))
	{
		$data = array('is_manager'  =>  'false');
		
		$edit_status=Update('manager', $data, "WHERE id = '".$_GET['login_deactive_id']."'");
		
		 $_SESSION['msg']="26";
		 header( "Location:manager.php");
		 exit;
	}
	if (isset($_GET['login_active_id']))
	{
		$data = array('is_manager'  =>  'true');
		
		$edit_status=Update('manager', $data, "WHERE id = '".$_GET['login_active_id']."'");
		
		$_SESSION['msg']="26";
		 header( "Location:manager.php");
		 exit;
	}
	
	if (isset($_POST['action'])) {
        if ($_POST['action'] == 'delete_selected') {
                if (isset($_POST['selected_users']) && !empty($_POST['selected_users'])) {
                    foreach ($_POST['selected_users'] as $user_id) {
                        // دریافت مقدار agent از جدول manager برای حذف تمام داده‌های مرتبط
                        $query = mysqli_query($mysqli, "SELECT agent FROM manager WHERE id = '".$user_id."'");
                        $row = mysqli_fetch_assoc($query);
                        $agent_id = $row['agent'];
        
                        Delete('manager', 'id='.$user_id);

                        mysqli_query($mysqli, "DELETE FROM users WHERE agent='".$agent_id."'");
                        mysqli_query($mysqli, "DELETE FROM agents WHERE agent='".$agent_id."'");
                        mysqli_query($mysqli, "DELETE FROM configs WHERE agent='".$agent_id."'");
                    }
        
                    $_SESSION['msg'] = "70"; // پیام موفقیت
                    header("Location: manager.php");
                    exit;
                } else {
                    $_SESSION['msg'] = "71"; // پیام خطا در صورتی که کاربری انتخاب نشده باشد
                    header("Location: manager.php");
                    exit;
                }
        } elseif($_POST['action'] == 'enable_selected') {
            if(isset($_POST['selected_users']) && !empty($_POST['selected_users'])) {
                foreach($_POST['selected_users'] as $user_id) {
                    $data = array('status'  =>  'active');
                    Update('manager', $data, "WHERE id = '".$user_id."'");
                }
                $_SESSION['msg'] = "72";
                header("Location: manager.php");
                exit;
            } else {
                $_SESSION['msg'] = "73";
                header("Location: manager.php");
                exit;
            }
            
        } elseif($_POST['action'] == 'disable_selected') {
            if(isset($_POST['selected_users']) && !empty($_POST['selected_users'])) {
                foreach($_POST['selected_users'] as $user_id) {
                    $data = array('status'  =>  'deactive');
                    Update('manager', $data, "WHERE id = '".$user_id."'");
                }
                $_SESSION['msg'] = "74";
                header("Location: manager.php");
                exit;
            } else {
                $_SESSION['msg'] = "75";
                header("Location: manager.php");
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
                <?php
                if ($admin_row['isManager'] == 'deactive'){
                    $_SESSION['msg']="27";
            		header( "Location:home.php");
            		exit;
                }
                if ($admin_row['is_admin'] == 'false'){
                    $_SESSION['msg']="27";
            		header( "Location:home.php");
            		exit;
                }
                ?>
              <div class="page_title">لیست مدیریت همکاران</div>
            </div>
            <div class="col-md-7 col-xs-12">              
                  <div class="search_list">
                    <div class="add_btn_primary"> <a href="add_manager_date.php?add">اضافه کردن براساس تاریخ</a> </div>&nbsp;
                    <div class="add_btn_primary"> <a href="add_manager_access.php?add">اضافه کردن براساس محدودیت</a> </div>&nbsp;
                    <div class="add_btn_primary"> <a href="add_manager_money.php?add">اضافه کردن براساس کیف پول</a> </div>
                  </div>
            </div>
          </div>
          <div class="clearfix"></div>
          <div class="row mrg-top">
            <div class="col-md-12">
               
              <div class="col-md-12 col-sm-12">
                <?php if(isset($_SESSION['msg'])){?> 
               	 <div class="alert alert-info alert-dismissible" role="alert"> <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
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
                    <th><input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)"></th>
                    <th class="text-center">شماره</th>	               
                    <th class="cat_action_list text-center">گزینه ها</th>
	                <th class="text-center">نام کاربری</th>
					<th class="text-center">پسورد</th>
					<th class="text-center">نام همکار</th>				
					<th class="text-center">موجودی</th>	
					<th class="text-center">مشاهده سرور</th>
					<th class="text-center">ساخت سرور</th>
					<th class="text-center">ویرایش سرور</th>
					<th class="text-center">حذف سرور</th>
					<th class="text-center">وضعیت سرور</th>	 
					<th class="text-center">تعداد سرور</th>	
					<th class="text-center">مشاهده کاربر</th>
					<th class="text-center">ساخت کاربر</th>
					<th class="text-center">ویرایش کاربر</th>
					<th class="text-center">حذف کاربر</th>
					<th class="text-center">وضعیت کاربر</th>
					<th class="text-center">نوع اکانت</th>	
					<th class="text-center">حجم مجاز</th>	
					<th class="text-center">تعداد مجاز</th>	
					<th class="text-center">روز مجاز</th>	
					<th class="text-center">محدودیت مجاز</th>	
					<th class="text-center">لاگین مجاز</th>	
					<th class="text-center">تعداد ورود</th>	 			
					<th class="text-center">وضعیت</th>
					<th class="text-center">آخرین ورود</th>	 
					<th class="text-center">تاریخ ساخت</th>	            					
					<th class="text-center">تاریخ ویرایش</th>	 
					<th class="text-center">تاریخ انقضاء</th>	 
                </tr>
              </thead>
              <tbody>
              	<?php
					$i=1;
					while($users_row=mysqli_fetch_array($users_result))
					{
					    $date_now = new DateTime();
					    $date_expire = new DateTime($users_row['expire']);
				?>
                <tr>
                    <td><input type="checkbox" name="selected_users[]" value="<?php echo $users_row['id']; ?>" class="user-checkbox"></td>
                    <td class="text-center"><?php echo toPersianNumbers($i++); ?></td>                    
                    <td class="text-center">
                        <div style="display: flex; gap: 5px; justify-content: center;">
                            <?php if ($users_row['type'] == "0"){?>
                                <a href="edit_manager_date.php?user_id=<?php echo $users_row['id'];?>" data-toggle="tooltip" title="ویرایش" class="btn btn-primary btn-sm btn_edit"><i class="fa fa-edit"></i></a>
                            <?php } elseif ($users_row['type'] == "2"){?>
                                <a href="edit_manager_money.php?user_id=<?php echo $users_row['id'];?>" data-toggle="tooltip" title="ویرایش" class="btn btn-primary btn-sm btn_edit"><i class="fa fa-edit"></i></a>
                            <?php } else {?>
                                <a href="edit_manager_access.php?user_id=<?php echo $users_row['id'];?>" data-toggle="tooltip" title="ویرایش" class="btn btn-primary btn-sm btn_edit"><i class="fa fa-edit"></i></a>
                            <?php }?>
                            <a href="manager.php?delete_id=<?php echo $users_row['owner'];?>" data-toggle="tooltip" title="حذف" class="btn btn-danger btn-sm btn_delete" onclick="return confirm('آیا میخواهید این حساب را حذف نمایید؟')">
                                <i class="fa fa-trash"></i>
                            </a>
                        </div>
                    </td>
                   	<td class="text-center"><?php echo $users_row['username'];?></td>
                   	<td class="text-center"><?php echo '***';?></td>
		           	<td class="text-center"><code><?php echo $users_row['owner'];?></code></td>
		           	<td class="text-center"><code><?php if ($users_row['type']=="0" || $users_row['type']=="1"){ echo "➖";} elseif ($users_row['inventory'] > "100000000"){ echo "نامحدود"; } else { echo number_format($users_row['inventory']).' تومان';}?></code></td>

		           	<td class="text-center">
		          		<?php if($users_row['isservers']!="0"){?>
		              		<a><span class="badge badge-success badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>فعال</span></span></a>
		              <?php }else{?>
		              		<a><span class="badge badge-danger badge-icon"><i class="fa fa-times" aria-hidden="true"></i><span>غیرفعال</span></span></a>
		              <?php }?>
              		</td>		           	
		           	<td class="text-center">
		          		<?php if($users_row['creServers']!="0"){?>
		              		<a><span class="badge badge-success badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>فعال</span></span></a>
		              <?php }else{?>
		              		<a><span class="badge badge-danger badge-icon"><i class="fa fa-times" aria-hidden="true"></i><span>غیرفعال</span></span></a>
		              <?php }?>
              		</td>		           	
		           	<td class="text-center">
		          		<?php if($users_row['editServers']!="0"){?>
		              		<a><span class="badge badge-success badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>فعال</span></span></a>
		              <?php }else{?>
		              		<a><span class="badge badge-danger badge-icon"><i class="fa fa-times" aria-hidden="true"></i><span>غیرفعال</span></span></a>
		              <?php }?>
              		</td>		           	
		           	<td class="text-center">
		          		<?php if($users_row['delServers']!="0"){?>
		              		<a><span class="badge badge-success badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>فعال</span></span></a>
		              <?php }else{?>
		              		<a><span class="badge badge-danger badge-icon"><i class="fa fa-times" aria-hidden="true"></i><span>غیرفعال</span></span></a>
		              <?php }?>
              		</td>		           	
		           	<td class="text-center">
		          		<?php if($users_row['staServers']!="0"){?>
		              		<a><span class="badge badge-success badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>فعال</span></span></a>
		              <?php }else{?>
		              		<a><span class="badge badge-danger badge-icon"><i class="fa fa-times" aria-hidden="true"></i><span>غیرفعال</span></span></a>
		              <?php }?>
              		</td>		           	
              		<td class="text-center"><code><?php if ($users_row['type']=="2"){ echo "➖";} elseif ($users_row['servers'] > "10240"){ echo "نامحدود"; } else { echo $users_row['servers'].' عدد';}?></code></td>
		           	<td class="text-center">
		          		<?php if($users_row['isusers']!="0"){?>
		              		<a><span class="badge badge-success badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>فعال</span></span></a>
		              <?php }else{?>
		              		<a><span class="badge badge-danger badge-icon"><i class="fa fa-times" aria-hidden="true"></i><span>غیرفعال</span></span></a>
		              <?php }?>
              		</td>		           	
		           	<td class="text-center">
		          		<?php if($users_row['creUsers']!="0"){?>
		              		<a><span class="badge badge-success badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>فعال</span></span></a>
		              <?php }else{?>
		              		<a><span class="badge badge-danger badge-icon"><i class="fa fa-times" aria-hidden="true"></i><span>غیرفعال</span></span></a>
		              <?php }?>
              		</td>		           	
		           	<td class="text-center">
		          		<?php if($users_row['editUsers']!="0"){?>
		              		<a><span class="badge badge-success badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>فعال</span></span></a>
		              <?php }else{?>
		              		<a><span class="badge badge-danger badge-icon"><i class="fa fa-times" aria-hidden="true"></i><span>غیرفعال</span></span></a>
		              <?php }?>
              		</td>		           	
		           	<td class="text-center">
		          		<?php if($users_row['delUsers']!="0"){?>
		              		<a><span class="badge badge-success badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>فعال</span></span></a>
		              <?php }else{?>
		              		<a><span class="badge badge-danger badge-icon"><i class="fa fa-times" aria-hidden="true"></i><span>غیرفعال</span></span></a>
		              <?php }?>
              		</td>		           	
		           	<td class="text-center">
		          		<?php if($users_row['staUsers']!="0"){?>
		              		<a><span class="badge badge-success badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>فعال</span></span></a>
		              <?php }else{?>
		              		<a><span class="badge badge-danger badge-icon"><i class="fa fa-times" aria-hidden="true"></i><span>غیرفعال</span></span></a>
		              <?php }?>
              		</td>		           	
		           	<td class="text-center">
		          		<?php if($users_row['type']==="2"){?>
		              		<a><span class="badge badge-success badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>کیف پول</span></span></a>
		              	<?php }elseif($users_row['type']==="1"){?>
		              		<a><span class="badge badge-warning badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>محدودیت</span></span></a>
		              <?php }else{?>
		              		<a><span class="badge badge-info badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>تاریخ انقضاء</span></span></a>
		              <?php }?>
              		</td>		           	
		           	<td class="text-center"><code><?php if ($users_row['type']=="0" || $users_row['type']=="2"){ echo "➖";} elseif ($users_row['size'] > "10240"){ echo "نامحدود"; } else { echo $users_row['size'].' GB';}?></code></td>
		           	<td class="text-center"><code><?php if ($users_row['type']=="2"){ echo "➖";} elseif ($users_row['total'] > "10240"){ echo "نامحدود"; } else { echo $users_row['total'].' عدد';}?></code></td>
		           	<td class="text-center"><code><?php if ($users_row['type']=="0" || $users_row['type']=="2"){ echo "➖";} elseif ($users_row['day'] > "10240"){ echo "نامحدود"; } else { echo $users_row['day'].' روز';}?></code></td>
		           	<td class="text-center"><code><?php if ($users_row['type']=="0" || $users_row['type']=="2"){ echo "➖";} elseif ($users_row['multi'] > "10240"){ echo "نامحدود"; } else { echo $users_row['multi'].' دستگاه';}?></code></td>
		           	<td class="text-center"><code><?php echo $users_row['device'];?> ورود</code></td>
		           	<td class="text-center"><code><?php echo $users_row['login'];?> کاربر</code></td>		           	
                    <td class="text-center">
                        <?php if($users_row['status']!="deactive"){?>
                            <a href="manager.php?status_deactive_id=<?php echo $users_row['id'];?>" title="تغییر وضعیت"><span class="badge badge-success badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>فعال</span></span></a>
                        <?php }else{?>
                            <a href="manager.php?status_active_id=<?php echo $users_row['id'];?>" title="تغییر وضعیت"><span class="badge badge-danger badge-icon"><i class="fa fa-times" aria-hidden="true"></i><span>غیرفعال</span></span></a>
                        <?php }?>
                    </td>
		           	<td class="text-center"><?php if($users_row['dlogin'] == '0000-00-00'){echo '➖➖➖'; } else { echo jdate("Y-m-d H:i:s", strtotime($users_row['dlogin']));}?></td>
		           	<td class="text-center"><?php echo jdate("Y-m-d H:i:s", strtotime($users_row['date'])); ?></td>
		           	<td class="text-center"><?php if($users_row['edate'] == '0000-00-00'){echo '➖➖➖'; } else { echo jdate("Y-m-d H:i:s", strtotime($users_row['edate']));}?></td>
                    <td class="text-center" style="
                        <?php 
                            if ($users_row['expire'] === '0000-00-00') {
                                $text = '➖➖➖';
                            } elseif ($date_now > $date_expire) {
                                echo 'background-color: #f44336; color: white;';
                                $text = 'منقضی';
                            } else {
                                $text = jdate("Y-m-d H:i:s", strtotime($users_row['expire']));
                            }
                        ?>">
                        <?php echo $text; ?>
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
                        <option value="enable_selected">فعال کردن نمایندگان انتخاب شده</option>
                        <option value="disable_selected">غیرفعال کردن نمایندگان انتخاب شده</option>
                        <option value="delete_selected">حذف نمایندگان انتخاب شده</option>
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
