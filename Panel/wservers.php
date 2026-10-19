<?php 

    include('includes/header.php'); 
    include('includes/function.php');
	include('language/language.php');  


	if(isset($_POST['server_search']))
	 {
		 
		$user_qry="SELECT * FROM wconfigs WHERE wconfigs.`name` like '%".addslashes($_POST['search_value'])."%' or wconfigs.link like '%".addslashes($_POST['search_value'])."%' ORDER BY wconfigs.id DESC";  
							 
		$users_result=mysqli_query($mysqli,$user_qry);

	 }
	 else
	 {
	 
		$tableName="wconfigs";		
		$targetpage = "wservers.php"; 	
		$limit = 100; 
		
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
			
		$users_qry="SELECT * FROM wconfigs ORDER BY wconfigs.id DESC LIMIT $start, $limit";  
		$users_result=mysqli_query($mysqli,$users_qry);
							
	 }
	 
	if(isset($_GET['server_id']))
	{
		  
		Delete('wconfigs','id='.$_GET['server_id'].'');
		
		$_SESSION['msg']="12";
		header( "Location:wservers.php");
		exit;
	}
	
	//Active and Deactive status
	if(isset($_GET['status_deactive_id']))
	{
		$data = array('status'  =>  'false');
		
		Update('wconfigs', $data, "WHERE id = '".$_GET['status_deactive_id']."'");
		
		 $_SESSION['msg']="14";
		 header( "Location:wservers.php");
		 exit;
	}
	if(isset($_GET['status_active_id']))
	{
		$data = array('status'  =>  'true');
		
		Update('wconfigs', $data, "WHERE id = '".$_GET['status_active_id']."'");
		
		$_SESSION['msg']="13";
		 header( "Location:wservers.php");
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
<meta charset="utf8mb4">
 <div class="row">
      <div class="col-xs-12">
        <div class="card mrg_bottom">
          <div class="page_title_block">
            <div class="col-md-5 col-xs-12">
              <div class="page_title">مدیریت سرورهای ویندوز</div>
            </div>
            <div class="col-md-7 col-xs-12">              
                  <div class="search_list">
                    <div class="search_block">
                      <form  method="post" action="">
                        <input class="form-control input-sm" placeholder="جستجو ..." aria-controls="DataTables_Table_0" type="search" name="search_value" required>
                        <button type="submit" name="server_search" class="btn-search"><i class="fa fa-search"></i></button>
                      </form>  
                    </div>
                    <div class="add_btn_primary"> <a href="add_wserver.php?add">اضافه کردن سرور ویندوز</a> </div>
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
          <div class="col-md-12 mrg-top table-responsive">
            <table class="table table-striped table-bordered table-hover">
              <thead>
                <tr>
	                <th class="text-center">نام سرور</th>						 
					<th class="text-center">کانفیگ</th>
					<th class="text-center">لوکیشن</th>
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
                   	<td class="text-center"><?php echo $users_row['name'];?></td>
		           	<td class="text-center config-cell"><sup><code><?php echo $users_row['link'];?></code></sup></td>
		           	<td class="text-center"><?php echo $users_row['locations'];?></td>   		           	
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
		              		<a href="wservers.php?status_deactive_id=<?php echo $users_row['id'];?>" title="تغییر وضعیت"><span class="badge badge-success badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>فعال</span></span></a>

		              <?php }else{?>
		              		<a href="wservers.php?status_active_id=<?php echo $users_row['id'];?>" title="تغییر وضعیت"><span class="badge badge-danger badge-icon"><i class="fa fa-times" aria-hidden="true"></i><span>غیرفعال</span></span></a>
		              <?php }?>
              		</td>
                   	<td class="text-center">
                   		<a href="add_wserver.php?server_id=<?php echo $users_row['id'];?>" data-toggle="tooltip" title="ویرایش" class="btn btn-primary btn_edit"><i class="fa fa-edit"></i></a>

                   		<a href="wservers.php?server_id=<?php echo $users_row['id'];?>" data-toggle="tooltip" title="حذف" class="btn btn-danger btn_delete" onclick="return confirm('آیا میخواهید این سرور را حذف نمایید؟')"><i class="fa fa-trash"></i></a>
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



<?php include('includes/footer.php');?>                  