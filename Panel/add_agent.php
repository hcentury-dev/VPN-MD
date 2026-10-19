<?php 

include('includes/header.php');
include('includes/function.php');
include('language/language.php'); 
include('includes/jdf.php'); 

if (isset($_POST['submit']) && isset($_GET['add'])) {
    if (!empty($_FILES['image']['name'])) {
        $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $pic1 = $_FILES['image']['tmp_name'];
        $upload1 = $_POST['from'] .'.'. $fileExtension;
        $tpath1 = 'images/logo/' . $upload1;
        copy($pic1, $tpath1);
    } else {
        $upload1 = '0.png';
    }

    $data = array(
        'name'  =>  $_POST['name'],
        'buy'  =>  $_POST['buy'],
        'renew' =>  $_POST['renew'],        
        'support' =>  $_POST['support'],
        'amozesh' =>  $_POST['amozesh'],
        'from_id'   =>  $_POST['from'],
        'app'   =>  $_POST['app'],
        'logo'   =>  $upload1,
    );

    $qry = Insert('agents', $data);

    $_SESSION['msg']="10";
    header("location:agents.php");     
    exit;
}

if (isset($_GET['agents_id'])) {
    $user_qry="SELECT * FROM agents where id='".$_GET['agents_id']."'";
    $user_result=mysqli_query($mysqli, $user_qry);
    $user_row=mysqli_fetch_assoc($user_result);
}

if (isset($_POST['submit']) && isset($_POST['agents_id'])) {
    
    if (!empty($_FILES['image']['name'])) {
        $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $randomNumber = rand(1000, 9999); // عدد تصادفی بین 1000 تا 9999
        $randomName = 'logo_' . $randomNumber . '.' . $fileExtension; // نام رندوم با پسوند
        $tpath1 = 'images/logo/' . $randomName;
        $pic1 = $_FILES['image']['tmp_name'];
        if (move_uploaded_file($pic1, $tpath1)) {
            echo "فایل با موفقیت آپلود شد!";
        } else {
            echo "خطا در آپلود فایل.";
        }
    } else {
        $randomName = 'logo_0.png';
    }

    $data = array(
        'name'  =>  $_POST['name'],
        'buy'  =>  $_POST['buy'],
        'renew' =>  $_POST['renew'],
        'support' =>  $_POST['support'],
        'amozesh' =>  $_POST['amozesh'],
        'from_id' =>  $_POST['from'],
        'app'   =>  $_POST['app'],
        'logo'   =>  $randomName,
    );

    $server_edit = Update('agents', $data, "WHERE id = '".$_POST['agents_id']."'");

    if ($server_edit > 0) {
        $_SESSION['msg']="11";
        header("Location:agents.php?id=".$_POST['agents_id']);
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
                            <?php echo $client_lang[$_SESSION['msg']] ; ?>
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
                                        <input type="file" name="image" id="fileupload">
                                        <div class="fileupload_img"><img type="image" src="assets/images/add-image.png" alt="add image" /></div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-3 control-label">&nbsp; </label>
                                <div class="col-md-3">
                                    <?php if(isset($user_row['logo']) && $user_row['logo']!="") { ?>
                                    <div class="block_wallpaper"><img type="image" src="images/logo/<?php echo $user_row['logo'];?>" /></div>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-3 control-label">نام نماینده :</label>
                                <div class="col-md-6">
                                    <input type="text" name="name" id="name" value="<?php if(isset($_GET['agents_id'])) { echo $user_row['name']; } ?>" class="form-control" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-3 control-label">آیدی عددی تلگرام :</label>
                                <div class="col-md-6">                                
                                <small>حتما باید عددی وارد کنید که با بقیه نماینده ها یکسان نباشد</small>
                                    <input type="text" name="from" id="from" pattern="\d*" value="<?php if(isset($_GET['agents_id'])) { echo $user_row['from_id']; } ?>" class="form-control" required>
                                </div>
                            </div>
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
