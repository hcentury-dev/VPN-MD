<?php 

    include('includes/header.php'); 
    include('includes/function.php');
	include('language/language.php'); 
	include('includes/jdf.php'); 
	
	if(isset($_POST['user_search']))
	 {
		 
		$user_qry="SELECT * FROM plans WHERE plans.`username` like '%".addslashes($_POST['search_value'])."%' or plans.password like '%".addslashes($_POST['search_value'])."%' ORDER BY plans.id DESC";  
							 
		$users_result=mysqli_query($mysqli,$user_qry);
		
	 } else {
	 
		$tableName="plans";		
		$targetpage = "plans.php"; 	
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
			
		$users_qry="SELECT * FROM plans ORDER BY plans.id DESC LIMIT $start, $limit";  
		$users_result=mysqli_query($mysqli,$users_qry);
							
	 }
	 
    if (isset($_GET['delete_id'])) {
        
        $delete_id = $_GET['delete_id'];
        mysqli_query($mysqli,"DELETE FROM plans WHERE id='".$delete_id."'");
        $_SESSION['msg'] = "12";
        header("Location: plans.php");
        exit;
    
    }
	if (isset($_GET['status_deactive_id'])) {
		$data = array('status'  =>  'deactive');
		Update('plans', $data, "WHERE id = '".$_GET['status_deactive_id']."'");
		$_SESSION['msg']="14";
		header( "Location:plans.php");
		exit;
	}
	if (isset($_GET['status_active_id'])) {
		$data = array('status'  =>  'active');
		
		$edit_status=Update('plans', $data, "WHERE id = '".$_GET['status_active_id']."'");
		
		$_SESSION['msg']="13";
		 header( "Location:plans.php");
		 exit;
	}

?>


 <div class="row">
      <div class="col-xs-12">
        <div class="card mrg_bottom">
          <div class="page_title_block">
            <div class="col-md-5 col-xs-12">
              <div class="page_title">لیست مدیریت پلن ها</div>
            </div>
            <div class="col-md-7 col-xs-12">              
                  <div class="search_list">
                    <div class="add_btn_primary"> <a href="add_plans.php?add">اضافه کردن پلن </a> </div>&nbsp;
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
                    <th class="text-center">شماره</th>
					<th class="text-center">حجم</th>	
					<th class="text-center">روز</th>	
					<th class="text-center">مولتی</th>	 			
					<th class="text-center">قیمت</th>	 			
					<th class="text-center">وضعیت</th>
					<th class="text-center">تاریخ ساخت</th>	            					
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
                    <td class="text-center"><?php echo toPersianNumbers($i++); ?></td>
                   	<td class="text-center"><?php echo $users_row['size'];?></td>
                   	<td class="text-center"><?php echo $users_row['day'];?></td>
                   	<td class="text-center"><?php echo $users_row['multi'];?></td>
                   	<td class="text-center"><?php echo number_format($users_row['price']); ?> تومان</td>
                    <td class="text-center">
                        <?php if($users_row['status']!="deactive"){?>
                            <a href="plans.php?status_deactive_id=<?php echo $users_row['id'];?>" title="تغییر وضعیت"><span class="badge badge-success badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>فعال</span></span></a>
                        <?php }else{?>
                            <a href="plans.php?status_active_id=<?php echo $users_row['id'];?>" title="تغییر وضعیت"><span class="badge badge-danger badge-icon"><i class="fa fa-times" aria-hidden="true"></i><span>غیرفعال</span></span></a>
                        <?php }?>
                    </td>		           	
                    <td class="text-center"><?php echo $users_row['date'];?></td>		           	
                    <td class="text-center">
                        <div style="display: flex; gap: 5px; justify-content: center;">
                            <a href="add_plans.php?plans_id=<?php echo $users_row['id'];?>" data-toggle="tooltip" title="ویرایش" class="btn btn-primary btn-sm btn_edit"><i class="fa fa-edit"></i></a>
                            <a href="plans.php?delete_id=<?php echo $users_row['id'];?>" data-toggle="tooltip" title="حذف" class="btn btn-danger btn-sm btn_delete" onclick="return confirm('آیا میخواهید این پلن را حذف نمایید؟')">
                                <i class="fa fa-trash"></i>
                            </a>
                        </div>
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
