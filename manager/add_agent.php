<?php 

include('includes/header.php');
include('includes/function.php');
include('language/language.php'); 

$qry = "SELECT * FROM settings WHERE id='1'";
$result = mysqli_query($mysqli, $qry);
$settings_row = mysqli_fetch_assoc($result);

if (isset($_GET['agents_id'])) {
    $user_qry = "SELECT * FROM agents WHERE id='".$_GET['agents_id']."'";
    $user_result = mysqli_query($mysqli, $user_qry);
    $user_row = mysqli_fetch_assoc($user_result);
}

if (isset($_POST['submit']) && isset($_POST['agents_id'])) {
    
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] == 0) {
        $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif');
        
        if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
            $_SESSION['msg'] = "فایل تصویر معتبر نیست!";
            header("Location: add_agents.php?agents_id=".$_POST['agents_id']);
            exit;
        }
        
        $randomNumber = rand(1000, 9999);
        $randomName = 'logo_' . $randomNumber . '.' . $fileExtension;
        $pic1 = $_FILES['image']['tmp_name'];
        
        $destinationUrl = $settings_row['admin_link'].'/upload.php'; 
        
        $cfile = new CURLFile($pic1, $_FILES['image']['type'], $randomName);
        $data = array('file' => $cfile, 'randomName' => $randomName);
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $destinationUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
    
        if ($response == "Success") {
            $_SESSION['msg'] = "فایل با موفقیت آپلود شد!";
            
            if (isset($user_row['logo']) && $user_row['logo'] != 'logo_0.png') {
            }
        } else {
            $_SESSION['msg'] = "خطا در آپلود فایل به سرور مقصد: " . $error;
            $randomName = $user_row['logo'];
        }
    } else {
        $randomName = $user_row['logo'] ?? 'logo_0.png';
    }
    
    $data = array(
        'buy'      => $_POST['buy'],
        'renew'    => $_POST['renew'],
        'support'  => $_POST['support'],
        'amozesh'  => $_POST['amozesh'],
        'app'      => $_POST['app'],
        'logo'     => $randomName,
    );

    $server_edit = Update('agents', $data, "WHERE id = '".$_POST['agents_id']."'");

    if ($server_edit > 0) {
        $_SESSION['msg'] = "11";
        header("Location: agents.php?id=".$_POST['agents_id']);
        exit;
    } else {
        $_SESSION['msg'] = "خطا در ذخیره اطلاعات!";
        header("Location: agents.php?agents_id=".$_POST['agents_id']);
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
                    <div class="page_title"><?php if(isset($_GET['agents_id'])) { ?>ویرایش<?php } else { ?>اضافه کردن<?php } ?> نماینده</div>
                </div>
            </div>
            <div class="clearfix"></div>
            <div class="row mrg-top">
                <div class="col-md-12">
                    <div class="col-md-12 col-sm-12">
                        <?php if(isset($_SESSION['msg'])) { ?> 
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                            <?php 
                            // نمایش پیام خطا یا موفقیت
                            if ($_SESSION['msg'] == "11") {
                                echo $client_lang[$_SESSION['msg']];
                            } else {
                                echo $_SESSION['msg'];
                            }
                            ?>
                        </div>
                        <?php unset($_SESSION['msg']); } ?>  
                    </div>
                </div>
            </div>
            <div class="card-body mrg_bottom"> 
                <form action="" name="addedituser" method="post" class="form form-horizontal" enctype="multipart/form-data">
                    <input type="hidden" name="agents_id" value="<?php echo $_GET['agents_id'];?>" />
                    <div class="section">
                        <div class="section-body">
                            <div class="form-group">
                                <label class="col-md-3 control-label">تصویر لوگو :</label>
                                <div class="col-md-6">
                                    <div class="fileupload_block">
                                        <input type="file" name="image" id="fileupload" accept="image/*">
                                        <div class="fileupload_img">
                                            <img type="image" src="assets/images/add-image.png" alt="add image" />
                                        </div>
                                    </div>
                                    <small>فرمت‌های مجاز: JPG, PNG, GIF - حداکثر سایز: 2MB</small>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label">تصویر فعلی :</label>
                                <div class="col-md-3">
                                    <?php if (isset($user_row['logo']) && $user_row['logo'] != "") { ?>
                                    <div class="block_wallpaper">
                                        <img src="<?php echo $settings_row['admin_link']; ?>/panel/images/logo/<?php echo $user_row['logo']; ?>" alt="logo" style="max-width: 200px; max-height: 200px;" />
                                        <p class="text-muted"><?php echo $user_row['logo']; ?></p>
                                    </div>
                                    <?php } else { ?>
                                    <div class="block_wallpaper">
                                        <img src="assets/images/no-image.png" alt="بدون تصویر" style="max-width: 200px; max-height: 200px;" />
                                        <p class="text-muted">تصویری انتخاب نشده است</p>
                                    </div>
                                    <?php } ?>
                                </div>
                            </div>
                            
                            <!-- سایر فیلدها -->
                            <div class="form-group">
                                <label class="col-md-3 control-label">لینک خرید :</label>
                                <div class="col-md-6">                                
                                <small>اگر نیاز هست داخل برنامه نمایش نده حتما # بذارید</small>
                                    <input type="text" name="buy" id="buy" value="<?php if(isset($_GET['agents_id'])) { echo $user_row['buy']; } ?>" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label">لینک تمدید :</label>
                                <div class="col-md-6">                                
                                <small>اگر نیاز هست داخل برنامه نمایش نده حتما # بذارید</small>
                                    <input type="text" name="renew" id="renew" value="<?php if(isset($_GET['agents_id'])) { echo $user_row['renew']; } ?>" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label">لینک پشتیبانی :</label>
                                <div class="col-md-6">                                
                                <small>اگر نیاز هست داخل برنامه نمایش نده حتما # بذارید</small>
                                    <input type="text" name="support" id="support" value="<?php if(isset($_GET['agents_id'])) { echo $user_row['support']; } ?>" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label">لینک آموزش :</label>
                                <div class="col-md-6">                                
                                <small>اگر نیاز هست داخل برنامه نمایش نده حتما # بذارید</small>
                                    <input type="text" name="amozesh" id="amozesh" value="<?php if(isset($_GET['agents_id'])) { echo $user_row['amozesh']; } ?>" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label">اسم اپلیکیشن :</label>
                                <div class="col-md-6">
                                    <input type="text" name="app" id="app" value="<?php if(isset($_GET['agents_id'])) { echo $user_row['app']; } ?>" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <div class="col-md-9 col-md-offset-3">
                                    <button type="submit" name="submit" class="btn btn-primary">ذخیره</button>
                                    <a href="agents.php?id=<?php echo $_GET['agents_id']; ?>" class="btn btn-default">لغو</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>





