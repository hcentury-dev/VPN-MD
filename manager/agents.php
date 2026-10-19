<?php 

    include('includes/header.php'); 
    include('includes/function.php');
	include('language/language.php');  
	
	if (isset($_SESSION['id']))
	{
		$manager_qry="SELECT * FROM manager where id='".$_SESSION['id']."'";
		$manager_result=mysqli_query($mysqli,$manager_qry);
		$manager_row=mysqli_fetch_assoc($manager_result);
		$agent = $manager_row['owner'];
	}
	
	if (isset($_POST['agents_search']))
	 {
		 
		$user_qry="SELECT * FROM agents WHERE agents.`name` like '%".addslashes($_POST['search_value'])."%' or agents.link like '%".addslashes($_POST['search_value'])."%' ORDER BY agents.id DESC";  
							 
		$users_result=mysqli_query($mysqli,$user_qry);

	 }else{
	 
		$tableName="agents";		
		$targetpage = "agents.php"; 	
		$limit = 100; 
		
		$query = "SELECT COUNT(*) as num FROM $tableName WHERE agent = '$agent'";
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
			
		$users_qry="SELECT * FROM agents WHERE agent = '$agent' ORDER BY agents.id DESC LIMIT $start, $limit";  
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
              <div class="page_title">مدیریت نماینده</div>
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
	                <th class="cat_action_list text-center">ویرایش</th>
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
                   	<td class="text-center">
                   		<a href="add_agent.php?agents_id=<?php echo $users_row['id'];?>" data-toggle="tooltip" title="ویرایش" class="btn btn-primary btn_edit"><i class="fa fa-edit"></i></a>
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