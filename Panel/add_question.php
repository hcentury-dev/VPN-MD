<?php 

    include('includes/header.php');
    include('includes/function.php');
	include('language/language.php'); 
	include('includes/jdf.php'); 

    $today = date('Y-m-d H:i:s');

	if (isset($_POST['submit']) and isset($_GET['add']))
	{		
	  
			$data = array(
 			'title'  =>  $_POST['title'],
			'text'   =>  $_POST['text'],
			'status' =>  $_POST['status'],
			);

			$qry = Insert('questions', $data);

			$_SESSION['msg']="10";
			header("location:question.php");	 
			exit;
		}
	
	if(isset($_GET['question_id']))
	{
			 
			$user_qry="SELECT * FROM questions where id='".$_GET['question_id']."'";
			$user_result=mysqli_query($mysqli,$user_qry);
			$user_row=mysqli_fetch_assoc($user_result);
		
	}
	
	if(isset($_POST['submit']) and isset($_POST['question_id']))
	{
		  
			$data = array(
 			'title'  =>  $_POST['title'],
			'text'   =>  $_POST['text'],
			'status' =>  $_POST['status'],
			);
		
 
		   $questions_edit=Update('questions', $data, "WHERE id = '".$_POST['question_id']."'");
		 
			if ($questions_edit > 0){
				
				$_SESSION['msg']="11";
				header("Location:question.php?id=".$_POST['question_id']);
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
              <div class="page_title"><?php if(isset($_GET['question_id'])){?>ویرایش<?php }else{?>اضافه کردن<?php }?> سوال</div>
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
            	<input  type="hidden" name="question_id" value="<?php echo $_GET['question_id'];?>" />

              <div class="section">
                <div class="section-body">
                  <div class="form-group">
                    <label class="col-md-3 control-label">عنوان سوال :</label>
                    <div class="col-md-6">
                      <input type="text" name="title" id="title" value="<?php if(isset($_GET['question_id'])){echo $user_row['title'];}?>" class="form-control" required>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="col-md-3 control-label">متن سوال :</label>
                    <div class="col-md-6">
                      <input type="text" name="text" id="text" value="<?php if(isset($_GET['question_id'])){echo $user_row['text'];}?>" class="form-control" required>
                    </div>
                  </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">سوال فعال باشد؟</label>
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