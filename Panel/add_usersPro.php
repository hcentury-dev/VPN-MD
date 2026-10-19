<?php 

    include('includes/header.php');
    include('includes/function.php');
	include('language/language.php'); 
	include('includes/jdf.php'); 
	
    $date_buy = date('Y-m-d H:i:s');
    if ($_POST['total'] == "∞"){
        $total = "1024000";
    } else {
        $total = $_POST['total'];
    }
    
    if (isset($_POST['submit'])){
        
        $ag_qry="SELECT * FROM agents where from_id='".$_POST['op_agents']."'";
		$ag_result=mysqli_query($mysqli,$ag_qry);
		$ag_row=mysqli_fetch_assoc($ag_result);
        $num_accounts = $_POST['num_accounts'];
        $sub = 'بسته ' . $_POST['date'] . ' روزه';
    
        for ($i = 0; $i < $num_accounts; $i++) {
    
            $random_username = generateUniqueUsername(
    $_POST['name_accounts'],
    $_POST['na_accounts'],
    $mysqli
);

if ($random_username === false) {
    die('خطا: نتوانستم نام کاربری یکتا بسازم.');
}
            $random_password = generateRandomString(4);
                
            $data = array(
                'username'      => $random_username,
                'password'      => $random_password,
                'total'         => $total,
                'adate'         => $_POST['date'],
                'owner'         => $_SESSION['admin_name'],
                'permium'       => 'Pro',	 
                'oprator'       => 'ALL',
                'multi'         => $_POST['op_multi'],
                'full'          => $_POST['total'],
                'id_from'       => $_POST['op_agents'],		
    			'from_id'       => $_POST['op_agents'],
    			'agent'         => $ag_row['agent'],
                'subscription'  => $sub,
                'date_buy'      => $date_buy,
            );
    
            $qry = Insert('users', $data);
        }
    
        $_SESSION['msg'] = "10";
        header("location:usersPro.php");
        exit;
    }

?>

    <style>
    .switch-container {
        padding: 15px;
        border-radius: 8px;
    }

    .switch-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .switch-label {
        flex: 1;
        font-weight: bold;
    }

    .pure-material-switch input {
        display: none;
    }

    .pure-material-switch span {
        width: 34px;
        height: 20px;
        background: #ddd;
        display: inline-block;
        border-radius: 50px;
        position: relative;
        transition: background 0.3s;
    }

    .pure-material-switch span::after {
        content: "";
        width: 16px;
        height: 16px;
        background: white;
        border-radius: 50%;
        position: absolute;
        top: 2px;
        left: 2px;
        transition: left 0.3s;
    }

    .pure-material-switch input:checked + span {
        background: #4caf50;
    }

    .pure-material-switch input:checked + span::after {
        left: 16px;
    }

    #accessControl {
        display: block;
        text-align: center;
    }

    #fullAccess:checked ~ #accessControl {
        display: none;
    }

    #accessControl {
        display: none;
        text-align: center;
    }
</style>

    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="page_title_block">
            <div class="col-md-5 col-xs-12">
              <div class="page_title">اضافه کردن کاربر انبوه</div>
            </div>
          </div>
          <div class="clearfix"></div>
          <div class="row mrg-top">
            <div class="col-md-12">
              <div class="col-md-12 col-sm-12">
                <?php if(isset($_SESSION['msg'])){?> 
               	 <div class="alert alert-danger alert-dismissible" role="alert">
               	     <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                	<?php echo $client_lang[$_SESSION['msg']]; ?></a> </div>
                <?php unset($_SESSION['msg']);}?>	
              </div>
            </div>
          </div>
          <div class="card-body mrg_bottom"> 
            <form action="" name="add" method="post" class="form form-horizontal" enctype="multipart/form-data" >
              <div class="section">
                <div class="section-body">
                <div class="form-group">
                    <label class="col-md-3 control-label">پیشوند اولیه اسم ها :</label>
                    <div class="col-md-3">
                      <input type="text" name="name_accounts" id="name_accounts" min="3" max="1000" class="form-control" required>
                    </div>
                    <label class="col-md-1 control-label">تعداد عدد اسم ها :</label>
                    <div class="col-md-2">
                      <select name="na_accounts" id="na_accounts" class="select2">
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                      </select>
                    </div>
                  </div>                  
                <div class="form-group">
                    <label class="col-md-3 control-label">تعداد اکانت ها :</label>
                    <div class="col-md-6">
                      <input type="number" name="num_accounts" id="num_accounts" min="2" max="1000" class="form-control" required>
                    </div>
                  </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">حجم کل :</label>
                    <div class="col-md-6"><small>برای حجم نامحدود # بذارید</small>
                        <input type="text" name="total" id="total" class="form-control" required placeholder="هر گیگ برابر با 1">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">تاریخ اکانت ها :</label>
                        <div class="col-md-6">
                        <select name="date" id="date" class="select2">
                          <option value="1">1 روزه</option>
                          <option value="3">3 روزه</option>
                          <option value="7">1 هفته</option>
                          <option value="30">1 ماه</option>
                          <option value="60">2 ماه</option>
                          <option value="90">3 ماه</option>
                          <option value="180">6 ماه</option>
                          <option value="365">یکسال</option>
                          <option value="1825">نامحدود</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">انتخاب نماینده :</label>
                    <div class="col-md-6">
                        <select name="op_agents" id="op_agents" class="select2">
                            <?php
                            $sql = "SELECT * FROM agents";
                            $agents_result = mysqli_query($mysqli, $sql);
                            if ($agents_result) {
                                while ($row = mysqli_fetch_assoc($agents_result)) {
                                    $selected = ($row['from_id'] == $user_row['id_from']) ? "selected" : "";
                                    echo "<option value='" . $row['from_id'] . "' " . $selected . ">" . $row['name'] . "</option>";
                                }
                                mysqli_free_result($agents_result);
                            } else {
                                echo "Error: " . mysqli_error($mysqli);
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">تعداد دستگاه های قابل اتصال :</label>
                        <div class="col-md-6">
                      <input type="number" name="op_multi" id="op_multi" min="1" max="99999" class="form-control" required>
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
<!--    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">-->
<!--    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>-->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let totalInput = document.getElementById("total");
            let previousValue = "";
        
            totalInput.addEventListener("input", function () {
                let currentValue = this.value.replace(/[^0-9#]/g, ''); // حذف تمام کاراکترهای غیر از اعداد 0-9 و #
        
                if (currentValue === "") return;
        
                if(currentValue === "#"){
                    this.value = "∞";
                } else {
                    previousValue = currentValue;
                }
            });
        
            totalInput.addEventListener("blur", function () {
                let currentValue = this.value.replace(/[^0-9#]/g, ""); // حذف تمام کاراکترهای غیر از اعداد 0-9 و #
                if(currentValue === "#"){
                    this.value = previousValue;
                }
            });
        });
    </script>

<?php include('includes/footer.php');?>                 