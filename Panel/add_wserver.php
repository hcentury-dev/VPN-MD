<?php 

    include('includes/header.php');
    include('includes/function.php');
	include('language/language.php'); 
	include('includes/jdf.php'); 

	$Time = date('Y-m-d H:i:s');

   if( isset($_SERVER['HTTPS'] ) ) {  
    $file_path = 'https://'.$_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']).'/images/Flag/';
  }
  else
  {
    $file_path = 'http://'.$_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']).'/images/Flag/';
  }
  
  $SRC = $file_path.$_POST['src'].'.png';
  
    if ($_POST['src'] == 'ge'){
        $loc = 'آلمان';
    } else if ($_POST['src'] == 'usa'){
        $loc = 'آمریکا';
    } else if ($_POST['src'] == 'ir'){
        $loc = 'ایران';
    } else if ($_POST['src'] == 'em'){
        $loc = 'امارات';
    } else if ($_POST['src'] == 'en'){
        $loc = 'انگلیس';
    } else if ($_POST['src'] == 'tr'){
        $loc = 'ترکیه';
    } else if ($_POST['src'] == 'sw'){
        $loc = 'سوئد';
    } else if ($_POST['src'] == 'fr'){
        $loc = 'فرانسه';
    } else if ($_POST['src'] == 'fl'){
        $loc = 'فلاند';
    } else if ($_POST['src'] == 'ne'){
        $loc = 'هلند';
    } else if ($_POST['src'] == 'ca'){
        $loc = 'کانادا';
    } else if ($_POST['src'] == 'mk'){
        $loc = 'مکزیک';
    } else if ($_POST['src'] == 'sp'){
        $loc = 'اسپانیا';
    } else if ($_POST['src'] == 'aus'){
        $loc = 'استرالیا';
    } else if ($_POST['src'] == 'in'){
        $loc = 'هند';
    } else if ($_POST['src'] == 'sing'){
        $loc = 'سنگاپور';
    } else if ($_POST['src'] == 'br'){
        $loc = 'برزیل';
    } else if ($_POST['src'] == 'ru'){
        $loc = 'روسیه';
    } else if ($_POST['src'] == 'er'){
        $loc = 'ایرلند';
    } else if ($_POST['src'] == 'la'){
        $loc = 'لهستان';
    } else if ($_POST['src'] == 'et'){
        $loc = 'ایتالیا';
    } else if ($_POST['src'] == 'ok'){
        $loc = 'اوکراین';
    }
    
	if (isset($_POST['submit']) and isset($_GET['add']))
	{		
	    if ($_POST['op_permium'] == 'Free'){
	        $title = "رایگان";
	    } else {
	        $title = "اختصاصی";
	    }
			  
			$data = array(
 			'name'  =>  $_POST['name'],
 			'title'  =>  "اختصاصی",
 			'fragment'  =>  $_POST['op_fragment'],
			'link'  =>  $_POST['link'],
			'locations'  =>  $loc,
			'src'  =>  $SRC,
			'permium'  =>  'Pro',
			'oprator'  =>  'ALL',
			'date'  =>  $Time
			);

			$qry = Insert('wconfigs',$data);

			$_SESSION['msg']="10";
			header("location:wservers.php");	 
			exit;
		}
	
	if(isset($_GET['server_id']))
	{
			 
			$user_qry="SELECT * FROM wconfigs where id='".$_GET['server_id']."'";
			$user_result=mysqli_query($mysqli,$user_qry);
			$user_row=mysqli_fetch_assoc($user_result);
		
	}
	
	if(isset($_POST['submit']) and isset($_POST['server_id']))
	{
	    
	    if ($_POST['op_permium'] == 'Free'){
	        $title = "رایگان";
	    } else {
	        $title = "اختصاصی";
	    }
		  
			$data = array(
 			'name'  =>  $_POST['name'],
 			'title'  =>  "اختصاصی",
 			'fragment'  =>  $_POST['op_fragment'],
			'link'  =>  $_POST['link'],
			'locations'  => $loc,
			'src'  =>  $SRC,
			'permium'  =>  'Pro',
			'oprator'  =>  'ALL',
			);
		
 
		   $server_edit=Update('wconfigs', $data, "WHERE id = '".$_POST['server_id']."'");
		 
			if ($server_edit > 0){
				
				$_SESSION['msg']="11";
				header("Location:wservers.php?id=".$_POST['server_id']);
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
              <div class="page_title"><?php if(isset($_GET['server_id'])){?>ویرایش<?php }else{?>اضافه کردن<?php }?> سرور ویندوز</div>
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
            	<input  type="hidden" name="server_id" value="<?php echo $_GET['server_id'];?>" />

              <div class="section">
                <div class="section-body">
                  <div class="form-group">
                    <label class="col-md-3 control-label">نام سرور :</label>
                    <div class="col-md-6">
                      <input type="text" name="name" id="name" value="<?php if(isset($_GET['server_id'])){echo $user_row['name'];}?>" class="form-control" required>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="col-md-3 control-label">کانفیگ :</label>
                    <div class="col-md-6">
                      <input type="text" name="link" id="link" value="<?php if(isset($_GET['server_id'])){echo $user_row['link'];}?>" class="form-control" required>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="col-md-3 control-label">پرچم کشور</label>
                        <div class="col-md-6">
                        <select name="src" id="src" class="select2">
                          <option value="ge" <?php if($user_row['src']== $file_path.'ge'.'.png'){?>selected<?php }?>>آلمان</option>
                          <option value="usa"<?php if($user_row['src']== $file_path.'usa'.'.png'){?>selected<?php }?>>آمریکا</option>
                          <option value="em" <?php if($user_row['src']== $file_path.'em'.'.png'){?>selected<?php }?>>امارات</option>
                          <option value="ir" <?php if($user_row['src']== $file_path.'ir'.'.png'){?>selected<?php }?>>ایران</option>
                          <option value="en" <?php if($user_row['src']== $file_path.'en'.'.png'){?>selected<?php }?>>انگلیس</option>
                          <option value="ca" <?php if($user_row['src']== $file_path.'ca'.'.png'){?>selected<?php }?>>کانادا</option>
                          <option value="tr" <?php if($user_row['src']== $file_path.'tr'.'.png'){?>selected<?php }?>>ترکیه</option>
                          <option value="sw" <?php if($user_row['src']== $file_path.'sw'.'.png'){?>selected<?php }?>>سوئد</option>
                          <option value="fl" <?php if($user_row['src']== $file_path.'fl'.'.png'){?>selected<?php }?>>فلاند</option>
                          <option value="fr" <?php if($user_row['src']== $file_path.'fr'.'.png'){?>selected<?php }?>>فرانسه</option>
                          <option value="ne" <?php if($user_row['src']== $file_path.'ne'.'.png'){?>selected<?php }?>>هلند</option>
                          <option value="mk" <?php if($user_row['src']== $file_path.'mk'.'.png'){?>selected<?php }?>>مکزیک</option>
                          <option value="sp" <?php if($user_row['src']== $file_path.'sp'.'.png'){?>selected<?php }?>>اسپانیا</option>
                          <option value="aus" <?php if($user_row['src']== $file_path.'aus'.'.png'){?>selected<?php }?>>استرالیا</option>
                          <option value="in" <?php if($user_row['src']== $file_path.'in'.'.png'){?>selected<?php }?>>هند</option>
                          <option value="sing" <?php if($user_row['src']== $file_path.'sing'.'.png'){?>selected<?php }?>>سنگاپور</option>
                          <option value="br" <?php if($user_row['src']== $file_path.'br'.'.png'){?>selected<?php }?>>برزیل</option>
                          <option value="ru" <?php if($user_row['src']== $file_path.'ru'.'.png'){?>selected<?php }?>>روسیه</option>
                          <option value="er" <?php if($user_row['src']== $file_path.'er'.'.png'){?>selected<?php }?>>ایرلند</option>
                          <option value="la" <?php if($user_row['src']== $file_path.'la'.'.png'){?>selected<?php }?>>لهستان</option>
                          <option value="et" <?php if($user_row['src']== $file_path.'et'.'.png'){?>selected<?php }?>>ایتالیا</option>
                          <option value="ok" <?php if($user_row['src']== $file_path.'ok'.'.png'){?>selected<?php }?>>اوکراین</option>

                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-md-3 control-label">فرگمنت</label>
                        <div class="col-md-6">
                        <select name="op_fragment" id="op_fragment" class="select2">
                          <option value="true" <?php if($user_row['fragment']=='true'){?>selected<?php }?>>فعال</option>
                          <option value="false" <?php if($user_row['fragment']=='false'){?>selected<?php }?>>غیرفعال</option>
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