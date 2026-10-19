<?php 

    include('includes/header.php');
    include('includes/function.php');
	include('language/language.php'); 
	include('includes/jdf.php'); 

    $date_buy = date('Y-m-d H:i:s');
    
	if(isset($_POST['submit']) and isset($_GET['add']))
	{
	
	global $mysqli;
    $sql = "SELECT username FROM admin where username= '".$_POST['username']."' ";       
    $result = mysqli_query($mysqli,$sql);
    $num_rows = mysqli_num_rows($result);
    $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        if ($num_rows > 0){
            
            $_SESSION['msg']="22";
            header('Location:add_admin.php?add');              

		} else {
			    
			$data = array(
     			'username'  =>  $_POST['username'],
    			'password'  =>  $hashed_password,
    			'email'  =>  $_POST['email'],
    			'owner'  =>  $_POST['username'],			 
    			'isManager'  =>  $_POST['isManager'],			 
    			'is_admin'  =>  $_POST['is_admin'],			 
    			'author'  =>  $_SESSION['admin_name'],		 
    			'image'  =>  "profile.png",		 
    			'status'  =>  $_POST['status'],		 
    			'x_add_admin'  =>  $_POST['x_add_admin'],		 
    			'x_edit_admin'  =>  $_POST['x_edit_admin'],		 
    			'x_status_admin'  =>  $_POST['x_status_admin'],		 
    			'x_delete_admin'  =>  $_POST['x_delete_admin'],
    			'login'  =>  "0000-00-00",
    			'date'  =>  $date_buy
			);

			$qry = Insert('admin',$data);
			$_SESSION['msg']="10";
			header("location:admins.php");	 
			exit;
		}
	}
	
	if(isset($_SESSION['id'])){
    	$SES_qry="SELECT * FROM admin where id='".$_SESSION['id']."'";
    	$SES_result=mysqli_query($mysqli,$SES_qry);
    	$SES_row=mysqli_fetch_assoc($SES_result);
	}
	
?>
 	

 <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="page_title_block">
            <div class="col-md-5 col-xs-12">
                <?php
                if ($SES_row['x_add_admin'] == '0'){
                    $_SESSION['msg']="27";
            		header( "Location:admins.php");
            		exit;
                }
                ?>
              <div class="page_title">ساخت مدیر جدید</div>
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
              <div class="section">
                <div class="section-body">
                  <div class="form-group">
                    <label class="col-md-3 control-label">نام کاربری :</label>
                    <div class="col-md-6">
                      <input type="text" name="username" id="username" min="4" max="20" class="form-control" required>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="col-md-3 control-label">پسورد :</label>
                    <div class="col-md-6">
                      <input type="text" name="password" id="password" min="4" max="20" onkeypress="return /[a-z0-9]/i.test(event.key)" class="form-control" required>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="col-md-3 control-label">ایمیل :</label>
                    <div class="col-md-6">
                      <input type="text" name="email" id="email" class="form-control" required>
                    </div>
                  </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">مدیریت فعال باشد؟</label>
                        <div class="col-md-6">
                        <select name="status" id="status" class="select2">
                          <option value="active">بله</option>
                          <option value="deactive">خیر</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">دسترسی نمایش لیست همکاران</label>
                        <div class="col-md-6">
                        <select name="is_admin" id="is_admin" class="select2">
                          <option value="false">خیر</option>
                          <option value="true">بله</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">دسترسی مدیریت همکاران</label>
                        <div class="col-md-6">
                        <select name="isManager" id="isManager" class="select2">
                          <option value="deactive">خیر</option>
                          <option value="active">بله</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">دسترسی ساخت مدیریت</label>
                        <div class="col-md-6">
                        <select name="x_add_admin" id="x_add_admin" class="select2">
                          <option value="0">خیر</option>
                          <option value="1">بله</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">دسترسی ویرایش مدیریت</label>
                        <div class="col-md-6">
                        <select name="x_edit_admin" id="x_edit_admin" class="select2">
                          <option value="0">خیر</option>
                          <option value="1">بله</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">دسترسی حذف مدیریت</label>
                        <div class="col-md-6">
                        <select name="x_delete_admin" id="x_delete_admin" class="select2">
                          <option value="0">خیر</option>
                          <option value="1">بله</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">دسترسی وضعیت مدیریت</label>
                        <div class="col-md-6">
                        <select name="x_status_admin" id="x_status_admin" class="select2">
                          <option value="0">خیر</option>
                          <option value="1">بله</option>
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