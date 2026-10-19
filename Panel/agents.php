<?php 

    include('includes/header.php'); 
    include('includes/function.php');
	include('language/language.php');  


	if(isset($_POST['agents_search']))
	 {
		 
		$user_qry="SELECT * FROM agents WHERE agents.`name` like '%".addslashes($_POST['search_value'])."%' or agents.link like '%".addslashes($_POST['search_value'])."%' ORDER BY agents.id DESC";  
							 
		$users_result=mysqli_query($mysqli,$user_qry);

	 }
	 else
	 {
	 
		$tableName="agents";		
		$targetpage = "agents.php"; 	
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
			
		$users_qry="SELECT * FROM agents ORDER BY agents.id DESC LIMIT $start, $limit";  
		$users_result=mysqli_query($mysqli,$users_qry);
							
	 }
	 
	if(isset($_GET['agents_id']))
	{
		  
		Delete('agents','id='.$_GET['agents_id'].'');
		
		$_SESSION['msg']="12";
		header( "Location:agents.php");
		exit;
	}

?>

<meta charset="utf8mb4">
 <div class="row">
      <div class="col-xs-12">
        <div class="card mrg_bottom">
          <div class="page_title_block">
            <div class="col-md-5 col-xs-12">
              <div class="page_title">مدیریت نماینده ها</div>
            </div>
            <div class="col-md-7 col-xs-12">              
                <div class="search_list">
                    <div class="search_block">
                      <form  method="post" action="">
                        <input class="form-control input-sm" placeholder="جستجو ..." aria-controls="DataTables_Table_0" type="search" name="search_value" required>
                        <button type="submit" name="agents_search" class="btn-search"><i class="fa fa-search"></i></button>
                      </form>  
                    </div>
                    <div class="add_btn_primary"> <a href="add_agent.php?add">اضافه کردن نماینده</a> </div>
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
	                <th class="text-center">نام نماینده</th>						 
					<th class="text-center">لینک خرید</th>
					<th class="text-center">لینک تمدید</th>
					<th class="text-center">لینک پشتیبانی</th>
					<th class="text-center">لینک آموزش</th>
					<th class="text-center">نام برنامه</th>				
					<th class="text-center">آیدی عددی</th>				
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
		           	<td class="text-center"><code><?php echo $users_row['buy'];?></code></td>
		           	<td class="text-center"><code><?php echo $users_row['renew'];?></code></td>
		           	<td class="text-center"><code><?php echo $users_row['support'];?></code></td>
		           	<td class="text-center"><code><?php echo $users_row['amozesh'];?></code></td>
		           	<td class="text-center"><?php echo $users_row['app'];?></td>
		           	<td class="text-center"><?php echo $users_row['from_id'];?></td>
                    <td class="text-center">
                        <a href="add_agent.php?agents_id=<?php echo $users_row['id'];?>" data-toggle="tooltip" title="ویرایش" class="btn btn-primary btn_edit">
                            <i class="fa fa-edit"></i>
                        </a>
                    
                        <?php if ($users_row['id'] != 1 && $users_row['name'] != "مدیریت اصلی") { ?>
                            <a href="agents.php?agents_id=<?php echo $users_row['id'];?>" data-toggle="tooltip" title="حذف" class="btn btn-danger btn_delete" onclick="return confirm('آیا میخواهید این نماینده را حذف نمایید؟')">
                                <i class="fa fa-trash"></i>
                            </a>
                        <?php } else { ?>
                            <button class="btn btn-secondary btn_delete" disabled>
                                <i class="fa fa-trash"></i>
                            </button>
                        <?php } ?>
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
              	<?php if(!isset($_POST["agents_search"])){ include("pagination.php");}?>                 
              </nav>
            </div>
          </div>
          <div class="clearfix"></div>
        </div>
      </div>
    </div>     


<?php include('includes/footer.php');?>                  