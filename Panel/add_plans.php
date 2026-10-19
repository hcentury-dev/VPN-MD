<?php 

    include('includes/header.php');
    include('includes/function.php');
	include('language/language.php'); 
	include('includes/jdf.php'); 

    $today = date('Y-m-d H:i:s');

	if (isset($_POST['submit']) and isset($_GET['add']))
	{		
	  
		$data = array(
 		'size'  =>  $_POST['size'],
		'day'   =>  $_POST['day'],
		'multi'   =>  $_POST['multi'],
		'price'   =>  $_POST['price'],
		'status' =>  $_POST['status'],
		'date' =>  $today,
		);

		$qry = Insert('plans',$data);

		$_SESSION['msg']="10";
		header("location:plans.php");	 
		exit;
	}
	
	if(isset($_GET['plans_id']))
	{
			 
		$user_qry="SELECT * FROM plans where id='".$_GET['plans_id']."'";
		$user_result=mysqli_query($mysqli,$user_qry);
		$user_row=mysqli_fetch_assoc($user_result);
		
	}
	
	if(isset($_POST['submit']) and isset($_POST['plans_id']))
	{
		  
		$data = array(
 		'size'  =>  $_POST['size'],
		'day'   =>  $_POST['day'],
		'multi'   =>  $_POST['multi'],
		'price'   =>  $_POST['price'],
		'status' =>  $_POST['status']
		);
		
	   $plans_edit=Update('plans', $data, "WHERE id = '".$_POST['plans_id']."'");
	 
		if ($plans_edit > 0){
			
			$_SESSION['msg']="11";
			header("Location:plans.php?id=".$_POST['plans_id']);
			exit;
		} 	
	}
?>
 	
<meta charset="utf8mb4">
 <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="page_title_block">
            <div class="col-md-5 col-xs-12">
              <div class="page_title"><?php if(isset($_GET['plans_id'])){?>ویرایش<?php }else{?>اضافه کردن<?php }?> پلن</div>
            </div>
          </div>
          <div class="clearfix"></div>
          <div class="row mrg-top">
            <div class="col-md-12">
              <div class="col-md-12 col-sm-12">
                <?php if(isset($_SESSION['msg'])){?> 
               	 <div class="alert alert-danger alert-dismissible" role="alert"> <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                	<?php echo $client_lang[$_SESSION['msg']] ; ?></a> </div>
                <?php unset($_SESSION['msg']);}?>	
              </div>
            </div>
          </div>
          <div class="card-body mrg_bottom"> 
            <form action="" name="addedituser" method="post" class="form form-horizontal" enctype="multipart/form-data" >
            	<input  type="hidden" name="plans_id" value="<?php echo $_GET['plans_id'];?>" />
              <div class="section">
                <div class="section-body">
                  <div class="form-group">
                    <label class="col-md-3 control-label">حجم پلن :</label>
                    <div class="col-md-6">
                      <input type="text" name="size" id="size" value="<?php if(isset($_GET['plans_id'])){echo $user_row['size'];}?>" class="form-control" required>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="col-md-3 control-label">روز پلن :</label>
                    <div class="col-md-6">
                      <input type="text" name="day" id="day" value="<?php if(isset($_GET['plans_id'])){echo $user_row['day'];}?>" class="form-control" required>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="col-md-3 control-label">مولتی پلن :</label>
                    <div class="col-md-6">
                      <input type="text" name="multi" id="multi" value="<?php if(isset($_GET['plans_id'])){echo $user_row['multi'];}?>" class="form-control" required>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="col-md-3 control-label">قیمت پلن :</label>
                    <div class="col-md-6">
                      <input type="text" name="price" id="price" value="<?php if(isset($_GET['plans_id'])){echo $user_row['price'];}?>" class="form-control" required>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="col-md-3 control-label">پلن فعال باشد؟</label>
                        <div class="col-md-6">                    
                        <select name="status" id="status" class="select2">
                          <option value="active" <?php if($user_row['status']=='active'){?>selected<?php }?>>بله</option>
                          <option value="deactive" <?php if($user_row['status']=='deactive'){?>selected<?php }?>>خیر</option>
                        </select>
                    </div>
                </div>
                  <div class="form-group">
                    <div class="col-md-9 col-md-offset-3">
                      <button type="submit" name="submit" class="btn btn-primary">ذخیره</button>
                    </div>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

<?php include('includes/footer.php');?>