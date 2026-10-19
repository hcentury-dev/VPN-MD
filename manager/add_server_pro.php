<?php 

    include('includes/header.php');
    include('includes/function.php');
	include('language/language.php'); 
	
	$Time = date('Y-m-d H:i:s');
	
    if(isset($_SESSION['id'])){
		$manager_qry="SELECT * FROM manager where id='".$_SESSION['id']."'";
		$manager_result=mysqli_query($mysqli,$manager_qry);
		$manager_row=mysqli_fetch_assoc($manager_result);
		$agent_servers = $manager_row['servers'];
		$agent_inventory = $manager_row['inventory'];
		$agent_type = $manager_row['type'];
		$agent = $manager_row['owner'];

        $qry_servers="SELECT COUNT(*) as num FROM configs WHERE agent = '$agent'";
            $total_servers= mysqli_fetch_array(mysqli_query($mysqli,$qry_servers));
            $total_servers = $total_servers['num'];
    }
	
    // if( isset($_SERVER['HTTPS'] ) ){  
    //     $file_path = 'https://'.$_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']).'/images/Flag/';
    // } else {
    //     $file_path = 'http://'.$_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']).'/images/Flag/';
    // }

    // $SRC = $file_path.$_POST['src'].'.png';
    
      $SRC = $_POST['src'].'.png';
      
      
    if ($_POST['src'] == 'ge'){
        $loc = 'آلمان';
        $loc2 = 'Germany';
    } else if ($_POST['src'] == 'usa'){
        $loc = 'آمریکا';
        $loc2 = 'USA';
    } else if ($_POST['src'] == 'ir'){
        $loc = 'ایران';
        $loc2 = 'Iran';
    } else if ($_POST['src'] == 'em'){
        $loc = 'امارات';
        $loc2 = 'Emirates';
    } else if ($_POST['src'] == 'en'){
        $loc = 'انگلیس';
        $loc2 = 'England';
    } else if ($_POST['src'] == 'tr'){
        $loc = 'ترکیه';
        $loc2 = 'Turkey';
    } else if ($_POST['src'] == 'sw'){
        $loc = 'سوئد';
        $loc2 = 'Sweden';
    } else if ($_POST['src'] == 'fr'){
        $loc = 'فرانسه';
        $loc2 = 'France';
    } else if ($_POST['src'] == 'fl'){
        $loc = 'فلاند';
        $loc2 = 'Finland';
    } else if ($_POST['src'] == 'ne'){
        $loc = 'هلند';
        $loc2 = 'Netherlands';
    } else if ($_POST['src'] == 'ca'){
        $loc = 'کانادا';
        $loc2 = 'Canada';
    } else if ($_POST['src'] == 'mk'){
        $loc = 'مکزیک';
        $loc2 = 'Mexico';
    } else if ($_POST['src'] == 'sp'){
        $loc = 'اسپانیا';
        $loc2 = 'Spain';
    } else if ($_POST['src'] == 'aus'){
        $loc = 'استرالیا';
        $loc2 = 'Australia';
    } else if ($_POST['src'] == 'in'){
        $loc = 'هند';
        $loc2 = 'India';
    } else if ($_POST['src'] == 'sing'){
        $loc = 'سنگاپور';
        $loc2 = 'Singapore';
    } else if ($_POST['src'] == 'br'){
        $loc = 'برزیل';
        $loc2 = 'Brazil';
    } else if ($_POST['src'] == 'ru'){
        $loc = 'روسیه';
        $loc2 = 'Russia';
    } else if ($_POST['src'] == 'er'){
        $loc = 'ایرلند';
        $loc2 = 'Ireland';
    } else if ($_POST['src'] == 'la'){
        $loc = 'لهستان';
        $loc2 = 'Poland';
    } else if ($_POST['src'] == 'et'){
        $loc = 'ایتالیا';
        $loc2 = 'Italy';
    } else if ($_POST['src'] == 'ok'){
        $loc = 'اوکراین';
        $loc2 = 'Ukraine';
    }
        
    $user_qry = "SELECT * FROM configs WHERE id = '".$_GET['server_id']."'";
    $user_result = mysqli_query($mysqli, $user_qry);
    $user_row = mysqli_fetch_assoc($user_result);
    $json_data = json_decode($user_row['json'], true);

	if (isset($_POST['submit']) and isset($_GET['add']))
	{
	    
    $is_json = ($_POST['config_type'] == 'json') ? 'true' : 'false';
    $config_value = ($is_json == 'true') ? $_POST['json_config'] : $_POST['link'];	
    
        $sql = "SELECT * FROM settings";
            $result = mysqli_query($mysqli, $sql);
            $row = mysqli_fetch_assoc($result);
            $manager_server = $row['manager_server'];
            
            if ($agent_type == "1"){
                
                $addUSERS = 1 + $total_servers;
                    if ($agent_servers < $addUSERS) {
                        $_SESSION['msg2'] = "51";
                        header("location:add_server_pro.php?add");
                        exit;
                    }
                        
    			$data = array(
     			'name_fa'   =>  $_POST['name_fa'],
     			'name_en'   =>  $_POST['name_en'],
     			'title_fa'  =>  "اختصاصی",
     			'title_en'  =>  "dedicated",
                'fragment'  =>  ($_POST['config_type'] != 'json') ? $_POST['op_fragment'] : 'false',
    			'link'      =>  $config_value,
    			'locations_fa'  =>  $loc,
    			'locations_en'  =>  $loc2,
    			'src'       =>  $SRC,
    			'permium'   =>  'Pro',
    			'oprator'   =>  'ALL',
    			'agent'      =>  $agent,
    			'json'      =>  $is_json,
    			'date'      =>  $Time
    			);
    
    			$qry = Insert('configs',$data);
    
    			$_SESSION['msg2']="10";
    			header("location:pro_server.php");	 
    			exit;
	
	        } elseif ($agent_type == "2"){
                
                $addTOTAL = 1 * $manager_server;
    		    if ($addTOTAL > $agent_inventory) {
                    $_SESSION['msg2'] = "45";
                    header("location:add_server_pro.php?add");
                    exit;
                }
                
                $Mony = array( 
				    'inventory'  =>  $agent_inventory-$addTOTAL,
			    );

				Update('manager', $Mony, "WHERE id = '".$_SESSION['id']."'"); 
				
				$data = array(
     			'name_fa'   =>  $_POST['name_fa'],
     			'name_en'   =>  $_POST['name_en'],
     			'title_fa'  =>  "اختصاصی",
     			'title_en'  =>  "dedicated",
                'fragment'  =>  ($_POST['config_type'] != 'json') ? $_POST['op_fragment'] : 'false',
    			'link'      =>  $config_value,
    			'locations_fa'  =>  $loc,
    			'locations_en'  =>  $loc2,
    			'src'       =>  $SRC,
    			'permium'   =>  'Pro',
    			'oprator'   =>  'ALL',
    			'agent'      =>  $agent,
    			'json'      =>  $is_json,
    			'date'      =>  $Time
    			);
    
    			$qry = Insert('configs',$data);
    
    			$_SESSION['msg2']="10";
    			header("location:pro_server.php");	 
    			exit;
    			
    		} else {
    		    
    		        $addUSERS = 1 + $total_servers;
                    if ($agent_servers < $addUSERS) {
                        $_SESSION['msg2'] = "51";
                        header("location:add_server_pro.php?add");
                        exit;
                    }
                    
    		    $data = array(
     			'name_fa'   =>  $_POST['name_fa'],
     			'name_en'   =>  $_POST['name_en'],
     			'title_fa'  =>  "اختصاصی",
     			'title_en'  =>  "dedicated",
                'fragment'  =>  ($_POST['config_type'] != 'json') ? $_POST['op_fragment'] : 'false',
    			'link'      =>  $config_value,
    			'locations_fa'  =>  $loc,
    			'locations_en'  =>  $loc2,
    			'src'       =>  $SRC,
    			'permium'   =>  'Pro',
    			'oprator'   =>  'ALL',
    			'agent'      =>  $agent,
    			'json'      =>  $is_json,
    			'date'      =>  $Time
    			);
    
    			$qry = Insert('configs',$data);
    
    			$_SESSION['msg2']="10";
    			header("location:pro_server.php");	 
    			exit;
    		}
	}
	
	if(isset($_GET['server_id']))
	{
			 
			$user_qry="SELECT * FROM configs where id='".$_GET['server_id']."'";
			$user_result=mysqli_query($mysqli,$user_qry);
			$user_row=mysqli_fetch_assoc($user_result);
		
	}
	
	if(isset($_POST['submit']) and isset($_POST['server_id']))
	{
	    
        $is_json = ($_POST['config_type'] == 'json') ? 'true' : 'false';
        $config_value = ($is_json == 'true') ? $_POST['json_config'] : $_POST['link'];
                
    			$data = array(
     			'name_fa'   =>  $_POST['name_fa'],
     			'name_en'   =>  $_POST['name_en'],
     			'title_fa'  =>  "اختصاصی",
     			'title_en'  =>  "dedicated",
                'fragment'  =>  ($_POST['config_type'] != 'json') ? $_POST['op_fragment'] : 'false',
    			'link'      =>  $config_value,
    			'locations_fa'  =>  $loc,
    			'locations_en'  =>  $loc2,
    			'src'       =>  $SRC,
    			'json'      =>  $is_json,
    			'permium'   =>  'Pro',
    			'oprator'   =>  'ALL',
    			);
    		
    		   $server_edit=Update('configs', $data, "WHERE id = '".$_POST['server_id']."'");
    		 
    			if ($server_edit > 0){
    				$_SESSION['msg2']="11";
    				header("Location:pro_server.php?id=".$_POST['server_id']);
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
                <?php
                if(isset($_GET['server_id'])){
                    if ($manager_row['editServers'] == '0'){
                    $_SESSION['msg2']="27";
                	header( "Location:pro_server.php");
                	exit;
                }
                } else {
                    if ($manager_row['creServers'] == '0'){
                    $_SESSION['msg2']="27";
                	header( "Location:pro_server.php");
                	exit;
                }
                }
                ?>
              <div class="page_title"><?php if(isset($_GET['server_id'])){?>ویرایش<?php }else{?>اضافه کردن<?php }?> سرور اختصاصی</div>
            </div>
          </div>
          <div class="clearfix"></div>
          <div class="row mrg-top">
            <div class="col-md-12">
              <div class="col-md-12 col-sm-12">
                <?php if (isset($_SESSION['msg2']) && isset($client_lang[$_SESSION['msg2']])) { ?>
               	 <div class="alert alert-danger alert-dismissible" role="alert"> <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                	<?php echo $client_lang[$_SESSION['msg2']] ; ?></a> </div>
                <?php unset($_SESSION['msg2']);}?>	
              </div>
            </div>
          </div>
          <div class="card-body mrg_bottom"> 
            <form action="" name="addedituser" method="post" class="form form-horizontal" enctype="multipart/form-data" >
            	<input  type="hidden" name="server_id" value="<?php echo $_GET['server_id'];?>" />
              <div class="section">
                <div class="section-body">
                  <div class="form-group">
                    <label class="col-md-3 control-label">نام سرور فارسی :</label>
                    <div class="col-md-6">
                      <input type="text" name="name_fa" id="name_fa" value="<?php if(isset($_GET['server_id'])){echo $user_row['name_fa'];}?>" class="form-control" required>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="col-md-3 control-label">نام سرور لاتین :</label>
                    <div class="col-md-6">
                      <input type="text" name="name_en" id="name_en" value="<?php if(isset($_GET['server_id'])){echo $user_row['name_en'];}?>" class="form-control" required>
                    </div>
                  </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">نوع کانفیگ :</label>
                    <div class="col-md-6">
                        <select name="config_type" id="config_type" class="select2" required onchange="toggleConfigFields()">
                            <option value="normal" <?php if(isset($user_row['json']) && $user_row['json'] == 'false'){echo 'selected';}?>>معمولی</option>
                            <option value="json" <?php if(isset($user_row['json']) && $user_row['json'] == 'true'){echo 'selected';}?>>جیسون</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" id="link_field">
                    <label class="col-md-3 control-label">کانفیگ :</label>
                    <div class="col-md-6">
                        <input type="text" name="link" id="link" value="<?php if(isset($_GET['server_id']) && $user_row['json'] == 'false'){echo $user_row['link'];}?>" class="form-control">
                    </div>
                </div>
                <div class="form-group" id="json_field" style="display:none;">
                    <label class="col-md-3 control-label">کانفیگ JSON :</label>
                    <div class="col-md-6">
                        <textarea name="json_config" id="json_config" class="form-control"><?php if(isset($_GET['server_id']) && $user_row['json'] == 'true'){echo htmlspecialchars($user_row['link'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);} ?></textarea>
                    </div>
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
                <div class="form-group" id="fragment_field">
                    <label class="col-md-3 control-label" >فرگمنت</label>
                        <div class="col-md-6" >
                        <select name="op_fragment" id="op_fragment" class="select2">
                          <option value="true" <?php if($user_row['fragment']=='true'){?>selected<?php }?>>فعال</option>
                          <option value="false" <?php if($user_row['fragment']=='false'){?>selected<?php }?>>غیرفعال</option>
                        </select>
                    </div>
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
    <script type="text/javascript">
        function toggleConfigFields() {
    var configType = document.getElementById('config_type').value;
    
    if (configType == 'json') {
        document.getElementById('link_field').style.display = 'none';
        document.getElementById('json_field').style.display = 'block';
        document.getElementById('fragment_field').style.display = 'none';  // نمایش فیلد فرگمنت
    } else {
        document.getElementById('link_field').style.display = 'block';
        document.getElementById('json_field').style.display = 'none';
        document.getElementById('fragment_field').style.display = 'block';  // مخفی کردن فیلد فرگمنت
    }
}
        window.onload = function() {
    toggleConfigFields();
}
    </script>

<?php include('includes/footer.php');?>                  