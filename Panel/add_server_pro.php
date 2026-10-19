<?php 

    include('includes/header.php');
    include('includes/function.php');
	include('language/language.php'); 
	include('includes/jdf.php'); 

	$Time = date('Y-m-d H:i:s');
  
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
			'json'      =>  $is_json,
			'date'      =>  $Time
			);

			$qry = Insert('configs',$data);

			$_SESSION['msg']="10";
			header("location:pro_server.php");	 
			exit;
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
				
				$_SESSION['msg']="11";
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
              <div class="page_title"><?php if(isset($_GET['server_id'])){?>ویرایش<?php }else{?>اضافه کردن<?php }?> سرور اختصاصی</div>
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
            <?php
            // استخراج کد کشور از مسیر ذخیره‌شده
            $selected_src = str_replace([$file_path, '.png'], '', $user_row['src']);

            $countries = [
                "ge" => "آلمان",
                "usa" => "آمریکا",
                "em" => "امارات",
                "ir" => "ایران",
                "en" => "انگلیس",
                "ca" => "کانادا",
                "tr" => "ترکیه",
                "sw" => "سوئد",
                "fl" => "فلاند",
                "fr" => "فرانسه",
                "ne" => "هلند",
                "mk" => "مکزیک",
                "sp" => "اسپانیا",
                "aus" => "استرالیا",
                "in" => "هند",
                "sing" => "سنگاپور",
                "br" => "برزیل",
                "ru" => "روسیه",
                "er" => "ایرلند",
                "la" => "لهستان",
                "et" => "ایتالیا",
                "ok" => "اوکراین"
            ];

            foreach ($countries as $code => $name) {
                $selected = ($selected_src == $code) ? "selected" : "";
                echo "<option value='$code' $selected>$name</option>";
            }
            ?>
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