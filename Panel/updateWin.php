<?php 

    include("includes/header.php");
	require("includes/function.php");
	require("language/language.php");
	 
	
	if(isset($_SESSION['id']))
	{
			 
		$qry="select * from application where id=1";
		 
		$result=mysqli_query($mysqli,$qry);
		$row=mysqli_fetch_assoc($result);

	}
	
	if(isset($_POST['submit']))
	{
	        
	        $version = $row['version'];
	        $link = $row['link'];
	    
			$data = array( 
			    'version'  => $version,
			    'link'  =>  $link,
			    'versionWin'  =>  $_POST['version'],
			    'linkWin'  =>  $_POST['link'],
			    'appstatus'  =>  $_POST['op_status']
			);
			
			$channel_edit=Update('application', $data, "WHERE id =1"); 

		$_SESSION['msg']="11"; 
		header( "Location:updateWin.php");
		exit;
		 
	}

?>
 
	 <div class="row">
      <div class="col-md-12">
        <div class="card">
		  <div class="page_title_block">
            <div class="col-md-5 col-xs-12">
              <div class="page_title">آپدیت ویندوز</div>
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
                    <label class="col-md-3 control-label">شماره نسخه ویندوز :</label>
                    <div class="col-md-6">
                      <input type="text" name="version" id="version" value="<?php echo $row['versionWin'];?>" class="form-control" required autocomplete="off">
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="col-md-3 control-label">لینک آپدیت ویندوز :</label>
                    <div class="col-md-6">
                      <input type="text" name="link" id="link" value="<?php echo $row['linkWin'];?>" class="form-control" required autocomplete="off">
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="col-md-3 control-label">وضعیت آپدیت اجباری</label>
                        <div class="col-md-6">
                        <select name="op_status" id="op_status" class="select2">
                          <option value="deactive" <?php if($row['appstatus']=='deactive'){?>selected<?php }?>>غیرفعال</option>
                          <option value="active"   <?php if($row['appstatus']=='active'){?>selected<?php }?>>فعال</option>
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
