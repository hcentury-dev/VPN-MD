<?php 

    include('includes/header.php'); 
    include('includes/function.php');
	include('language/language.php'); 
	include('includes/jdf.php'); 

    if(isset($_SESSION['id']))
	{
			 
		$admin_qry="SELECT * FROM admin where id='".$_SESSION['id']."'";
		$admin_result=mysqli_query($mysqli,$admin_qry);
		$admin_row=mysqli_fetch_assoc($admin_result);
		
	}
	
	if(isset($_GET['user_id']))
	{
			 
		$admin_qry="SELECT * FROM admin where id='".$_GET['user_id']."'";
		$admin_result=mysqli_query($mysqli,$admin_qry);
		$admin_row=mysqli_fetch_assoc($admin_result);
		
	}
	
	if(isset($_POST['user_search']))
	 {
		 
		
		$user_qry="SELECT * FROM admin WHERE admin.`username` like '%".addslashes($_POST['search_value'])."%' or admin.password like '%".addslashes($_POST['search_value'])."%' ORDER BY admin.id DESC";  
							 
		$users_result=mysqli_query($mysqli,$user_qry);
		
		 
	 }
	 else
	 {
	 
		$tableName="admin";		
		$targetpage = "admins.php"; 	
		$limit = 15; 
		
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
			
		$users_qry="SELECT * FROM admin ORDER BY admin.id DESC LIMIT $start, $limit";  
		$users_result=mysqli_query($mysqli,$users_qry);
							
	 }
	 
	if(isset($_GET['user_id']))
	{
		  
		Delete('admin','id='.$_GET['user_id'].'');
		
		$_SESSION['msg']="12";
		header( "Location:admins.php");
		exit;
	}
	
	//Active and Deactive status
	if(isset($_GET['status_deactive_id']))
	{
		$data = array('status'  =>  'deactive');
		
		$edit_status=Update('admin', $data, "WHERE id = '".$_GET['status_deactive_id']."'");
		
		 $_SESSION['msg']="14";
		 header( "Location:admins.php");
		 exit;
	}
	if(isset($_GET['status_active_id']))
	{
		$data = array('status'  =>  'active');
		
		$edit_status=Update('admin', $data, "WHERE id = '".$_GET['status_active_id']."'");
		
		$_SESSION['msg']="13";
		 header( "Location:admins.php");
		 exit;
	}
	
	//Active and Deactive login
	if(isset($_GET['login_deactive_id']))
	{
		$data = array('is_admin'  =>  'false');
		
		$edit_status=Update('admin', $data, "WHERE id = '".$_GET['login_deactive_id']."'");
		
		 $_SESSION['msg']="26";
		 header( "Location:admins.php");
		 exit;
	}
	if(isset($_GET['login_active_id']))
	{
		$data = array('is_admin'  =>  'true');
		
		$edit_status=Update('admin', $data, "WHERE id = '".$_GET['login_active_id']."'");
		
		$_SESSION['msg']="26";
		 header( "Location:admins.php");
		 exit;
	}
	
?>


 <div class="row">
      <div class="col-xs-12">
        <div class="card mrg_bottom">
          <div class="page_title_block">
            <div class="col-md-5 col-xs-12">
              <div class="page_title">لیست مدیران</div>
            </div>
            <div class="col-md-7 col-xs-12">              
                  <div class="search_list">
                    <div class="search_block">
                      <form  method="post" action="">
                        <input class="form-control input-sm" placeholder="جستجو ..." aria-controls="DataTables_Table_0" type="search" name="search_value" required>
                        <button type="submit" name="user_search" class="btn-search"><i class="fa fa-search"></i></button>
                      </form>  
                    </div>
                    <div class="add_btn_primary"> <a href="add_admin.php?add">اضافه کردن مدیر</a> </div>
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
          <div class="col-md-12 mrg-top table-responsive">
            <table class="table table-striped table-bordered table-hover">
              <thead>
                <tr>
	                <th class="text-center">نام کاربری</th>						 
					<th class="text-center">پسورد</th>
					<th class="text-center">ایمیل</th>
					<th class="text-center">مدیریت همکاران</th>	 	                					
					<th class="text-center">ساخت مدیر</th>	 	                					
					<th class="text-center">ویرایش مدیر</th>	 	                					
					<th class="text-center">حذف مدیر</th>	 	                					
					<th class="text-center">وضعیت مدیر</th>	 	                					
					<th class="text-center">وضعیت</th>	 	                					
	                <th class="cat_action_list text-center">گزینه ها</th>						
	                <th class="text-center">نام مدیریتی</th>	 				
	                <th class="text-center">سازنده</th>	 
	                <th class="text-center">آخرین ورود</th>	 
	                <th class="text-center">تاریخ ساخت</th>	 
                </tr>
              </thead>
              <tbody>
              	<?php
					$i=1;
					while($users_row=mysqli_fetch_array($users_result))
					{	 
				?>
                <tr>
                   	<td class="text-center"><?php echo $users_row['username'];?></td>
		           	<td class="text-center"><?php echo "****"?></td>   
		           	<td class="text-center"><?php echo $users_row['email'];?></td>
		           	<td class="text-center">
		          	<?php if($users_row['isManager']!="deactive"){?>
		              		<a><span class="badge badge-success badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>دارد</span></span></a>
		            <?php }else{?>
		              		<a><span class="badge badge-danger badge-icon"><i class="fa fa-times" aria-hidden="true"></i><span>ندارد</span></span></a>
		            <?php }?>
              		</td>
		           	<td class="text-center">

		          	<?php if($users_row['x_add_admin']!="0"){?>
		              		<a><span class="badge badge-success badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>دارد</span></span></a>

		            <?php }else{?>
		              		<a><span class="badge badge-danger badge-icon"><i class="fa fa-times" aria-hidden="true"></i><span>ندارد</span></span></a>
		            <?php }?>
		            
              		</td>
		           	<td class="text-center">

		          	<?php if($users_row['x_edit_admin']!="0"){?>
		              		<a><span class="badge badge-success badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>دارد</span></span></a>

		            <?php }else{?>
		              		<a><span class="badge badge-danger badge-icon"><i class="fa fa-times" aria-hidden="true"></i><span>ندارد</span></span></a>
		            <?php }?>
              		</td>
		           	<td class="text-center">

		          	<?php if($users_row['x_delete_admin']!="0"){?>
		              		<a><span class="badge badge-success badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>دارد</span></span></a>
		            <?php }else{?>
		              		<a><span class="badge badge-danger badge-icon"><i class="fa fa-times" aria-hidden="true"></i><span>ندارد</span></span></a>
		            <?php }?>
              		</td>
		           	<td class="text-center">

		          	<?php if($users_row['x_status_admin']!="0"){?>
		              		<a><span class="badge badge-success badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>دارد</span></span></a>

		            <?php }else{?>
		              		<a><span class="badge badge-danger badge-icon"><i class="fa fa-times" aria-hidden="true"></i><span>ندارد</span></span></a>
		            <?php }?>
              		</td>
		           	<td class="text-center">
		          		<?php if($users_row['status']!="deactive"){?>
		              		<a><span class="badge badge-success badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>فعال</span></span></a>
		              <?php }else{?>
		              		<a><span class="badge badge-danger badge-icon"><i class="fa fa-times" aria-hidden="true"></i><span>غیرفعال </span></span></a>
		              <?php }?>
              		</td>
                   	<td class="text-center">
                   	<?php if ($admin_row['x_edit_admin'] != "0"){ ?>
                   		<a href="edit_admin.php?user_id=<?php echo $users_row['id'];?>" data-toggle="tooltip" title="ویرایش" class="btn btn-primary btn_edit"><i class="fa fa-edit"></i></a
                   		>
                    <?php } else {  ?>
                   		<a data-toggle="tooltip" title="دسترسی ندارید" class="btn btn-primary btn_edit" disabled><i class="fa fa-edit"></i></a>
                   	<?php }?>
                   	
                   	<?php if ($admin_row['x_delete_admin'] != "0"){ ?>
                        <a href="admins.php?user_id=<?php echo $users_row['id'];?>" data-toggle="tooltip" title="حذف" class="btn btn-danger btn_delete" onclick="return confirm('آیا میخواهید این حساب را حذف نمایید؟')"><i class="fa fa-trash"></i></a>
                    <?php } else {  ?>
                        <a data-toggle="tooltip" title="دسترسی ندارید" class="btn btn-danger btn_delete" disabled><i class="fa fa-trash"></i></a>
                    <?php }?>
                   	</td>		           
                   	<td class="text-center"><?php echo $users_row['owner'];?></td>
		           	<td class="text-center"><code><?php echo $users_row['author'];?></code></td>
                   	<td class="text-center"><?php if ($users_row['login'] == '0000-00-00'){echo '➖➖➖'; } else { echo jdate("Y-m-d H:i:s", strtotime($users_row['login'])); }?></td>
                   	<td class="text-center"><?php echo jdate("Y-m-d H:i:s", strtotime($users_row['date'])); ?></td>
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
              	<?php if(!isset($_POST["user_search"])){ include("pagination.php");}?>                 
              </nav>
            </div>
          </div>
          <div class="clearfix"></div>
        </div>
      </div>
    </div>     

<?php include('includes/footer.php');?>    
