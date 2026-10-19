<?php 

include("includes/header.php");
include("includes/function.php");
include("language/language.php");

if(isset($_SESSION['id'])) {
    $qry = "SELECT * FROM admin WHERE id='".$_SESSION['id']."'";
    $result = mysqli_query($mysqli, $qry);
    $row = mysqli_fetch_assoc($result);
}

if(isset($_POST['submit'])) {

    // آماده سازی رمز عبور
    if(!empty($_POST['password'])) {
        // اگر رمز جدید وارد شده باشد، هش بساز
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    } else {
        // اگر خالی بود، رمز قبلی را از دیتابیس بخوان
        $result_pass = mysqli_query($mysqli, "SELECT password FROM admin WHERE id='".$_SESSION['id']."'");
        $row_pass = mysqli_fetch_assoc($result_pass);
        $hashed_password = $row_pass['password'];
    }

    // آماده سازی داده برای آپدیت
    $data = array(
        'username' => $_POST['username'],
        'password' => $hashed_password,
        'email'    => $_POST['email']
    );

    // اگر تصویر آپلود شده باشد
    if(!empty($_FILES['image']['name'])) {

        $img_res = mysqli_query($mysqli, "SELECT * FROM admin WHERE id='".$_SESSION['id']."'");
        $img_row = mysqli_fetch_assoc($img_res);

        if(!empty($img_row['image'])) {
            unlink('images/'.$img_row['image']);
        }

        $image = "profile.jpg";
        $pic1 = $_FILES['image']['tmp_name'];
        $tpath1 = 'images/'.$image;
        copy($pic1, $tpath1);

        $data['image'] = $image;
    }

    // اجرای آپدیت
    $channel_edit = Update('admin', $data, "WHERE id='".$_SESSION['id']."'");

    $_SESSION['msg'] = "11"; 
    header("Location: profile.php");
    exit;
}
?>

<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="page_title_block">
        <div class="col-md-5 col-xs-12">
          <div class="page_title">پروفایل مدیریت</div>
        </div>
      </div>
      <div class="clearfix"></div>
      <div class="row mrg-top">
        <div class="col-md-12">
          <div class="col-md-12 col-sm-12">
            <?php if(isset($_SESSION['msg'])) { ?> 
              <div class="alert alert-success alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                <?php echo $client_lang[$_SESSION['msg']]; ?>
              </div>
            <?php unset($_SESSION['msg']); } ?> 
          </div>
        </div>
      </div>
      <div class="card-body mrg_bottom">
        <form action="" name="editprofile" method="post" class="form form-horizontal" enctype="multipart/form-data">
          <div class="section">
            <div class="section-body">
              <div class="form-group">
                <label class="col-md-3 control-label">تصویر پروفایل :</label>
                <div class="col-md-6">
                  <div class="fileupload_block">
                    <input type="file" name="image" id="fileupload">
                    <div class="fileupload_img">
                      <?php if(!empty($row['image'])) { ?>
                        <img src="images/<?php echo $row['image']; ?>" alt="profile image" />
                      <?php } else { ?>
                        <img src="assets/images/add-image.png" alt="add image" />
                      <?php } ?>
                    </div>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label class="col-md-3 control-label">نام کاربری :</label>
                <div class="col-md-6">
                  <input type="text" name="username" value="<?php echo $row['username']; ?>" class="form-control" required autocomplete="off">
                </div>
              </div>

              <div class="form-group">
                <label class="col-md-3 control-label">رمز عبور :</label>
                <div class="col-md-6">
                  <input type="password" name="password" value="" class="form-control" autocomplete="off">
                  <small>اگر رمز جدید وارد نشود، رمز قبلی باقی خواهد ماند.</small>
                </div>
              </div>

              <div class="form-group">
                <label class="col-md-3 control-label">ایمیل :</label>
                <div class="col-md-6">
                  <input type="text" name="email" value="<?php echo $row['email']; ?>" class="form-control" required autocomplete="off">
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

<?php include("includes/footer.php"); ?>