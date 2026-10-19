<?php 

    include('includes/header.php');
    include('includes/function.php');
	include('language/language.php'); 
	include('includes/jdf.php'); 

	if(isset($_SESSION['id']))
	{
			 
		$SES_qry="SELECT * FROM admin where id='".$_SESSION['id']."'";
		$SES_result=mysqli_query($mysqli,$SES_qry);
		$SES_row=mysqli_fetch_assoc($SES_result);
	
	}
	
	if(isset($_GET['user_id']))
	{
			 
		$user_qry="SELECT * FROM admin where id='".$_GET['user_id']."'";
		$user_result=mysqli_query($mysqli,$user_qry);
		$user_row=mysqli_fetch_assoc($user_result);
		$hashed_password = password_hash($user_row['password'], PASSWORD_DEFAULT);
	}
	
    if (isset($_POST['submit']) && isset($_POST['user_id'])) {	
        $user_id = $_POST['user_id'];
        $password = $_POST['password'];
    
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        } else {
            $result = $mysqli->query("SELECT password FROM admin WHERE id = $user_id");
            $row = $result->fetch_assoc();
            $hashed_password = $row['password'];
        }
        
        $data = array(
            'password' => $hashed_password,
            'email' => $_POST['email'],
            'isManager' => $_POST['isManager'],
            'is_admin' => $_POST['is_admin'],
            'status' => $_POST['status'],
            'x_add_admin' => $_POST['x_add_admin'],
            'x_edit_admin' => $_POST['x_edit_admin'],
            'x_status_admin' => $_POST['x_status_admin'],
            'x_delete_admin' => $_POST['x_delete_admin']
        );
    
        $user_edit = Update('admin', $data, "WHERE id = '$user_id'");
    
        if ($user_edit > 0) {
            if ($user_id == $_SESSION['id']) {
                session_destroy();
                header("Location: index.php");
                exit;
            }
    
            $_SESSION['msg'] = "11";
            header("location: admins.php");
            exit;
        }
    }
	
?>
 	

 <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="page_title_block">
            <div class="col-md-5 col-xs-12">
                <?php
                if ($SES_row['x_edit_admin'] == '0'){
                    $_SESSION['msg']="27";
            		header( "Location:admins.php");
            		exit;
                }
                ?>
              <div class="page_title">ویرایش مدیریت</div>
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
            	<input  type="hidden" name="user_id" value="<?php echo $_GET['user_id'];?>" />

              <div class="section">
                <div class="section-body">
                  <div class="form-group">
                    <label class="col-md-3 control-label">نام کاربری :</label>
                    <div class="col-md-6"><?php if(isset($_GET['user_id'])){?>
                      <input type="text" name="username" id="username" min="4" max="20" onkeypress="return /[a-z0-9]/i.test(event.key)" value="<?php if(isset($_GET['user_id'])){echo $user_row['username'];}?>" class="form-control" disabled required>
                      <?php }?>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="col-md-3 control-label">پسورد :</label>
                    <div class="col-md-6"><?php if(isset($_GET['user_id'])){?>
                    <small>اگر رمز عبور جدید وارد نشود، رمز قبلی باقی خواهد ماند.</small>
                      <input type="text" name="password" id="password" min="4" max="20" onkeypress="return /[a-z0-9]/i.test(event.key)" value="" class="form-control">
                      <?php }?>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="col-md-3 control-label">ایمیل :</label>
                    <div class="col-md-6">
                      <input type="text" name="email" id="email" value="<?php if(isset($_GET['user_id'])){echo $user_row['email'];}?>" class="form-control" required>
                    </div>
                  </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">مدیریت فعال باشد؟</label>
                        <div class="col-md-6">                    
                        <select name="status" id="status" class="select2">
                          <option value="active" <?php if($user_row['status']=='active'){?>selected<?php }?>>بله</option>
                          <option value="deactive" <?php if($user_row['status']=='deactive'){?>selected<?php }?>>خیر</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">دسترسی نمایش لیست همکاران</label>
                        <div class="col-md-6">
                        <select name="is_admin" id="is_admin" class="select2">
                          <option value="false" <?php if($user_row['is_admin']=='false'){?>selected<?php }?>>خیر</option>
                          <option value="true" <?php if($user_row['is_admin']=='true'){?>selected<?php }?>>بله</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">دسترسی مدیریت همکاران</label>
                        <div class="col-md-6">
                        <select name="isManager" id="isManager" class="select2">
                          <option value="deactive" <?php if($user_row['isManager']=='deactive'){?>selected<?php }?>>خیر</option>
                          <option value="active" <?php if($user_row['isManager']=='active'){?>selected<?php }?>>بله</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">دسترسی ساخت مدیریت</label>
                        <div class="col-md-6">
                        <select name="x_add_admin" id="x_add_admin" class="select2">
                          <option value="0" <?php if($user_row['x_add_admin']=='0'){?>selected<?php }?>>خیر</option>
                          <option value="1" <?php if($user_row['x_add_admin']=='1'){?>selected<?php }?>>بله</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">دسترسی ویرایش مدیریت</label>
                        <div class="col-md-6">
                        <select name="x_edit_admin" id="x_edit_admin" class="select2">
                          <option value="0" <?php if($user_row['x_edit_admin']=='0'){?>selected<?php }?>>خیر</option>
                          <option value="1" <?php if($user_row['x_edit_admin']=='1'){?>selected<?php }?>>بله</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">دسترسی حذف مدیریت</label>
                        <div class="col-md-6">
                        <select name="x_delete_admin" id="x_delete_admin" class="select2">
                          <option value="0" <?php if($user_row['x_delete_admin']=='0'){?>selected<?php }?>>خیر</option>
                          <option value="1" <?php if($user_row['x_delete_admin']=='1'){?>selected<?php }?>>بله</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">دسترسی وضعیت مدیریت</label>
                        <div class="col-md-6">
                        <select name="x_status_admin" id="x_status_admin" class="select2">
                          <option value="0" <?php if($user_row['x_status_admin']=='0'){?>selected<?php }?>>خیر</option>
                          <option value="1" <?php if($user_row['x_status_admin']=='1'){?>selected<?php }?>>بله</option>
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