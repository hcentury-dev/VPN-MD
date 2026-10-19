<?php 

    include('includes/header.php'); 
    include('includes/function.php');
	include('language/language.php');  


	if(isset($_POST['question_search']))
	 {
		 
		$user_qry="SELECT * FROM questions WHERE questions.`title` like '%".addslashes($_POST['search_value'])."%' or questions.text like '%".addslashes($_POST['search_value'])."%' ORDER BY questions.id DESC";  
							 
		$users_result=mysqli_query($mysqli,$user_qry);

	 }
	 else
	 {
	 
		$tableName="questions";		
		$targetpage = "question.php"; 	
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
			
		$users_qry="SELECT * FROM questions ORDER BY questions.id DESC LIMIT $start, $limit";  
		$users_result=mysqli_query($mysqli,$users_qry);
							
	 }
	 
	if(isset($_GET['question_id']))
	{
		  
		Delete('questions','id='.$_GET['question_id'].'');
		
		$_SESSION['msg']="12";
		header( "Location:question.php");
		exit;
	}

?>

<meta charset="utf8mb4">
 <div class="row">
      <div class="col-xs-12">
        <div class="card mrg_bottom">
          <div class="page_title_block">
            <div class="col-md-5 col-xs-12">
              <div class="page_title">مدیریت سوالات</div>
            </div>
            <div class="col-md-7 col-xs-12">              
                  <div class="search_list">
                    <div class="search_block">
                      <form  method="post" action="">
                        <input class="form-control input-sm" placeholder="جستجو ..." aria-controls="DataTables_Table_0" type="search" name="search_value" required>
                        <button type="submit" name="question_search" class="btn-search"><i class="fa fa-search"></i></button>
                      </form>  
                    </div>
                    <div class="add_btn_primary"> <a href="add_question.php?add">اضافه کردن سوال</a> </div>
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
	                <th class="text-center">عنوان سوال</th>						 
					<th class="text-center">متن سوال</th>
					<th class="text-center">وضعیت </th>				
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
                   	<td class="text-center"><?php echo $users_row['title'];?></td>
		           	<td class="text-center"><?php echo $users_row['text'];?></td>
		           	<td class="text-center">
		          		<?php if($users_row['status']!="deactive"){?>
		              		<span class="badge badge-success badge-icon"><i class="fa fa-check" aria-hidden="true"></i><span>فعال</span></span>

		              <?php }else{?>
		              		<span class="badge badge-danger badge-icon"><i class="fa fa-times" aria-hidden="true"></i><span>غیرفعال</span></span>
		              <?php }?>
              		</td>
                   	<td class="text-center">
                   		<a href="add_question.php?question_id=<?php echo $users_row['id'];?>" data-toggle="tooltip" title="ویرایش" class="btn btn-primary btn_edit"><i class="fa fa-edit"></i></a>

                   		<a href="question.php?question_id=<?php echo $users_row['id'];?>" data-toggle="tooltip" title="حذف" class="btn btn-danger btn_delete" onclick="return confirm('آیا میخواهید این سرور را حذف نمایید؟')"><i class="fa fa-trash"></i></a>
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
              	<?php if(!isset($_POST["question_search"])){ include("pagination.php");}?>                 
              </nav>
            </div>
          </div>
          <div class="clearfix"></div>
        </div>
      </div>
    </div>     


<?php include('includes/footer.php');?>                  