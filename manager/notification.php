<?php 

    include("includes/header.php");
	require("includes/function.php");
	require("language/language.php");
	 
	$date = $date = date('Y-m-d');

	if(isset($_SESSION['id']))
	{
			 
		$qry="select * from notification where id=1";
		 
		$result=mysqli_query($mysqli,$qry);
		$row=mysqli_fetch_assoc($result);

	}
	
	if(isset($_POST['submit']))
	{
			$data = array( 
				    'title' =>  $_POST['title'],
				    'text'  =>  $_POST['text'],
				    'link'  =>  $_POST['link'],
				    'users' =>  $_POST['users'],
				    'status' =>  $_POST['op_status'],
				    'date'  =>  $date
			);
			
			$channel_edit=Update('notification', $data, "WHERE id =1"); 

		$_SESSION['msg']="11"; 
		header( "Location:notification.php");
		exit;
		 
	}

?>
 
	 <div class="row">
      <div class="col-md-12">
        <div class="card">
		  <div class="page_title_block">
            <div class="col-md-5 col-xs-12">
              <div class="page_title">اعلان برنامه</div>
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
          <div class="card-body mrg_bottom">
            <form action="" name="editprofile" method="post" class="form form-horizontal" enctype="multipart/form-data">
              <div class="section">
                <div class="section-body">
                  <div class="form-group">
                    <label class="col-md-3 control-label">عنوان اعلان :</label>
                    <div class="col-md-6">
                      <input type="text" name="title" id="title" value="<?php echo $row['title'];?>" class="form-control" required autocomplete="off">
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="col-md-3 control-label">متن اعلان :</label>
                    <div class="col-md-6">
                      <input type="text" name="text" id="text" value="<?php echo $row['text'];?>" class="form-control" required autocomplete="off">
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="col-md-3 control-label">لینک اعلان :</label>
                    <div class="col-md-6">
                      <input type="text" name="link" id="link" value="<?php echo $row['link'];?>" class="form-control" required autocomplete="off">
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="col-md-3 control-label">کاربران دیده :</label>
                    <div class="col-md-6">
                      <input type="text" name="users" id="users" value="<?php echo $row['users'];?>" class="form-control" required autocomplete="off">
                    </div>
                  </div>
                   <div class="form-group">
                    <label class="col-md-3 control-label">وضعیت اعلان</label>
                        <div class="col-md-6">
                        <select name="op_status" id="op_status" class="select2">
                          <option value="deactive" <?php if($row['status']=='deactive'){?>selected<?php }?>>غیرفعال</option>
                          <option value="active" <?php if($row['status']=='active'){?>selected<?php }?>>فعال</option>
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

        
<?php include("includes/footer.php");?>       
