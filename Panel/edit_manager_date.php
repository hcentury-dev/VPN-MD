<?php 

    include('includes/header.php');
    include('includes/function.php');
	include('language/language.php'); 
	include('includes/jdf.php'); 

    $admin_qry="SELECT * FROM admin where id='".$_SESSION['id']."'";
	$admin_result=mysqli_query($mysqli,$admin_qry);
	$admin_row=mysqli_fetch_assoc($admin_result);
	
    if (isset($_GET['user_id']))
    {
        $manager_qry = "SELECT * FROM manager WHERE id='".$_GET['user_id']."'";
        $manager_result = mysqli_query($mysqli, $manager_qry);
        $manager_data = mysqli_fetch_assoc($manager_result);
    }

	if(isset($_POST['submit']) and isset($_GET['user_id']))
	{
	            
	    $user_id = $_GET['user_id'];
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $owner = trim($_POST['owner']);
        $expireDate = $_POST['date'];
        
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        } else {
            $result = $mysqli->query("SELECT password FROM manager WHERE id = $user_id");
            $row = $result->fetch_assoc();
            $hashed_password = $row['password'];
        }
        
        if ($_POST['total'] == "∞"){
            $total = "102400000";
        } else {
            $total = $_POST['total'];
        }
        if ($_POST['servers'] == "∞"){
            $servers = "102400000";
        } else {
            $servers = $_POST['servers'];
        }
        
        if (!isset($expireDate) || empty($expireDate)) {
            $_SESSION['msg'] = "57";
            header('Location:edit_manager_date.php?add');
            exit;
        }
    
        $stmt = $mysqli->prepare("SELECT username FROM manager WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

         $default_access = [
            'isservers' => isset($_POST['isservers']) ? "1" : "0",
            'editServers' => isset($_POST['editServers']) ? "1" : "0",
            'delServers' => isset($_POST['delServers']) ? "1" : "0",
            'creServers' => isset($_POST['creServers']) ? "1" : "0",
            'staServers' => isset($_POST['staServers']) ? "1" : "0",
        
            'isusers' => isset($_POST['isusers']) ? "1" : "0",
            'editUsers' => isset($_POST['editUsers']) ? "1" : "0",
            'delUsers' => isset($_POST['delUsers']) ? "1" : "0",
            'creUsers' => isset($_POST['creUsers']) ? "1" : "0",
            'staUsers' => isset($_POST['staUsers']) ? "1" : "0",

            'expire' => $expireDate
            ];

    
        $data = array_merge($default_access, [
            'password' => $hashed_password,
            'device' => $_POST['device'],
            'servers' => $servers,
            'total' => $total,
        ]);
        Update('manager', $data, "WHERE id = '".$_GET['user_id']."'");
        
			$_SESSION['msg']="11";
			header("location:manager.php");	 
			exit;
    }

	if (isset($_SESSION['id'])) {
			 
		$SES_qry="SELECT * FROM manager where id='".$_SESSION['id']."'";
		$SES_result=mysqli_query($mysqli,$SES_qry);
		$SES_row=mysqli_fetch_assoc($SES_result);
		
	}
	
    $fullAccess = $manager_data['fullAccess'];
    $isusers = $manager_data['isusers'];
    $creUsers = $manager_data['creUsers'];
    $editUsers = $manager_data['editUsers'];
    $delUsers = $manager_data['delUsers'];
    $staUsers = $manager_data['staUsers'];
    $isservers = $manager_data['isservers'];
    $creServers = $manager_data['creServers'];
    $editServers = $manager_data['editServers'];
    $delServers = $manager_data['delServers'];
    $staServers = $manager_data['staServers'];
    $allChecked = ($isusers && $creUsers && $editUsers && $delUsers && $staUsers && 
                   $isservers && $creServers && $editServers && $delServers && $staServers);
    
    $selectedFullAccess = $allChecked ? "1" : "2";

?>
 	
<style>

    .access-container {
        display: grid;
        grid-template-columns: 1fr 1fr; /* دو ستون مساوی */
        gap: 20px; /* فاصله بین دو بخش */
        align-items: start;
    }
    
    .switch-container {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .switch-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .switch-list li {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 5px 0;
        gap: 10px;
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
    }
    
    #fullAccess:checked ~ #accessControl {
        display: none;
    }
    
    #accessControl {
        display: none;
    }
    
</style>

<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="page_title_block">
        <div class="col-md-5 col-xs-12">
          <?php
          if ($admin_row['isManager'] == 'deactive'){
              $_SESSION['msg']="27";
            header( "Location:home.php");
            exit;
          }
          if ($admin_row['is_admin'] == 'false'){
              $_SESSION['msg']="27";
            header( "Location:home.php");
            exit;
          }
          ?>
          <div class="page_title">ویرایش همکار براساس تاریخ انقضاء</div>
        </div>
      </div>
      <div class="clearfix"></div>
      <div class="row mrg-top">
        <div class="col-md-12">
          <?php if(isset($_SESSION['msg'])){?> 
            <div class="alert alert-danger alert-dismissible" role="alert"> 
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">×</span>
              </button>
              <?php echo $client_lang[$_SESSION['msg']] ; ?>
            </div>
          <?php unset($_SESSION['msg']);}?>  
        </div>
      </div>
      <div class="card-body mrg_bottom"> 
        <form action="" name="addedituser" method="post" class="form form-horizontal" enctype="multipart/form-data">
          <div class="section">
            <div class="section-body">              
                    <div class="form-group">
                        <label class="col-md-3 control-label">نام کاربری :</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" value="<?php echo $manager_data['username']; ?>" disabled>
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
                        <label class="col-md-3 control-label">نام و تگ همکار :</label>
                        <div class="col-md-6">
                            <input type="text" name="owner" id="owner" class="form-control" value="<?php echo $manager_data['owner']; ?>" disabled>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">تعداد لاگین همکار :</label>
                        <div class="col-md-6">
                          <input type="number" name="device" id="device" class="form-control" value="<?php echo $manager_data['device']; ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">تعداد ساخت سرور :</label>
                        <div class="col-md-6"><small>برای نامحدود # بذارید</small>
                            <input type="text" name="servers" id="servers" minlength="0" maxlength="1000" class="form-control" value="<?php echo ($manager_data['servers'] > 10240) ? '∞' : $manager_data['servers']; ?>" required> 
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">تعداد ساخت کاربران :</label>
                        <div class="col-md-6"><small>برای نامحدود # بذارید</small>
                            <input type="text" name="total" id="total" minlength="0" maxlength="1000" class="form-control" value="<?php echo ($manager_data['total'] > 10240) ? '∞' : $manager_data['total']; ?>" required> 
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">تاریخ انقضاء :</label>
                        <div class="col-md-6">
                            <input type="text" name="date" id="date" class="form-control" value="<?php echo $manager_data['expire']; ?>" required placeholder="انتخاب تاریخ">
                        </div>
                    </div>              
                  
                    <div class="form-group">
                        <label class="col-md-3 control-label">دسترسی همکار</label>
                        <div class="col-md-6">
                          <select name="fullAccess" id="fullAccess" class="select2" onchange="toggleFields()">                    
                            <option value="1" <?php echo ($selectedFullAccess == "1") ? "selected" : ""; ?>>دسترسی کامل</option>
                            <option value="2" <?php echo ($selectedFullAccess == "2") ? "selected" : ""; ?>>دسترسی دستی</option>
                          </select>
                        </div>
</div>
                    
                    <div id="accessControl" <?php echo ($selectedFullAccess == "1") ? 'style="display:none;"' : ''; ?>>
                        <div class="access-container">
                            <!-- بخش کاربران -->
                            <div class="switch-container">
                                <h3>کاربران</h3>
                                <ul class="switch-list">
                                    <li>
                                        <span class="switch-label">دسترسی به کاربران</span>
                                        <label class="pure-material-switch">
                                            <input type="checkbox" name="isusers" id="isusers" <?php echo ($isusers ? "checked" : ""); ?>>
                                            <span></span>
                                        </label>
                                    </li>
                                    <li>
                                        <span class="switch-label">دسترسی به ساخت کاربران</span>
                                        <label class="pure-material-switch">
                                            <input type="checkbox" name="creUsers" id="creUsers" <?php echo ($creUsers ? "checked" : ""); ?>>
                                            <span></span>
                                        </label>
                                    </li>
                                    <li>
                                        <span class="switch-label">دسترسی به ویرایش کاربران</span>
                                        <label class="pure-material-switch">
                                            <input type="checkbox" name="editUsers" id="editUsers" <?php echo ($editUsers ? "checked" : ""); ?>>
                                            <span></span>
                                        </label>
                                    </li>
                                    <li>
                                        <span class="switch-label">دسترسی به حذف کاربران</span>
                                        <label class="pure-material-switch">
                                            <input type="checkbox" name="delUsers" id="delUsers" <?php echo ($delUsers ? "checked" : ""); ?>>
                                            <span></span>
                                        </label>
                                    </li>
                                    <li>
                                        <span class="switch-label">دسترسی به وضعیت کاربران</span>
                                        <label class="pure-material-switch">
                                            <input type="checkbox" name="staUsers" id="staUsers" <?php echo ($staUsers ? "checked" : ""); ?>>
                                            <span></span>
                                        </label>
                                    </li>
                                </ul>
                            </div>
                            <!-- بخش سرورها -->
                            <div class="switch-container">
                                <h3>سرور</h3>
                                <ul class="switch-list">
                                    <li>
                                        <span class="switch-label">دسترسی به سرورها</span>
                                        <label class="pure-material-switch">
                                            <input type="checkbox" name="isservers" id="isservers" <?php echo ($isservers ? "checked" : ""); ?>>
                                            <span></span>
                                        </label>
                                    </li>
                                    <li>
                                        <span class="switch-label">دسترسی به ساخت سرورها</span>
                                        <label class="pure-material-switch">
                                            <input type="checkbox" name="creServers" id="creServers" <?php echo ($creServers ? "checked" : ""); ?>>
                                            <span></span>
                                        </label>
                                    </li>
                                    <li>
                                        <span class="switch-label">دسترسی به ویرایش سرورها</span>
                                        <label class="pure-material-switch">
                                            <input type="checkbox" name="editServers" id="editServers" <?php echo ($editServers ? "checked" : ""); ?>>
                                            <span></span>
                                        </label>
                                    </li>
                                    <li>
                                        <span class="switch-label">دسترسی به حذف سرورها</span>
                                        <label class="pure-material-switch">
                                            <input type="checkbox" name="delServers" id="delServers" <?php echo ($delServers ? "checked" : ""); ?>>
                                            <span></span>
                                        </label>
                                    </li>
                                    <li>
                                        <span class="switch-label">دسترسی به وضعیت سرورها</span>
                                        <label class="pure-material-switch">
                                            <input type="checkbox" name="staServers" id="staServers" <?php echo ($staServers ? "checked" : ""); ?>>
                                            <span></span>
                                        </label>
                                    </li>
                                </ul>
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
</div>
<!--<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">-->
<!--<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>-->
<script>
function toggleFields() {
    var fullAccess = document.getElementById("fullAccess").value;
    var accessControl = document.getElementById("accessControl");
    
    if (fullAccess == "1") {
        accessControl.style.display = "none";
    } else {
        accessControl.style.display = "block";
    }
}

// هنگام تغییر هر چک‌باکس بررسی کن که آیا همه انتخاب شده‌اند یا خیر
document.querySelectorAll('#accessControl input[type="checkbox"]').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        var allChecked = Array.from(document.querySelectorAll('#accessControl input[type="checkbox"]')).every(cb => cb.checked);
        document.getElementById("fullAccess").value = allChecked ? "1" : "2";
        toggleFields();
    });
});
</script>
<script>
    function toggleFields() {
        var accessControl = document.getElementById("accessControl");
        var fullAccess = document.getElementById("fullAccess").value;

        if (fullAccess == "2") {
            accessControl.style.display = "block";
        } else {
            accessControl.style.display = "none";
        }
    }
    document.addEventListener("DOMContentLoaded", function () {
        toggleFields();
    });
</script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
            let totalInput = document.getElementById("total");
            let previousValue = "";
        
            totalInput.addEventListener("input", function () {
                let currentValue = this.value.replace(/[^0-9#]/g, '');
        
                if (currentValue === "") return;
        
                if(currentValue === "#"){
                    this.value = "∞";
                } else {
                    previousValue = currentValue;
                }
            });
        
            totalInput.addEventListener("blur", function () {
                let currentValue = this.value.replace(/[^0-9#]/g, "");
                if(currentValue === "#"){
                    this.value = previousValue;
                }
            });
        });
    document.addEventListener("DOMContentLoaded", function () {
            let totalInput = document.getElementById("servers");
            let previousValue = "";
        
            totalInput.addEventListener("input", function () {
                let currentValue = this.value.replace(/[^0-9#]/g, '');
        
                if (currentValue === "") return;
        
                if(currentValue === "#"){
                    this.value = "∞";
                } else {
                    previousValue = currentValue;
                }
            });
        
            totalInput.addEventListener("blur", function () {
                let currentValue = this.value.replace(/[^0-9#]/g, "");
                if(currentValue === "#"){
                    this.value = previousValue;
                }
            });
        });

   window.addEventListener('load', function() {
    const fullAccess = document.getElementById('fullAccess');
    const accessControl = document.getElementById('accessControl');
    
    const fullAccessState = localStorage.getItem('fullAccess') === 'true';
    fullAccess.checked = fullAccessState;

    if (fullAccessState) {
        accessControl.style.display = 'none';
    }

    fullAccess.addEventListener('change', function() {
        const isChecked = fullAccess.checked;
        localStorage.setItem('fullAccess', isChecked);
        if (isChecked) {
            accessControl.style.display = 'none';
        } else {
            accessControl.style.display = 'block';
        }
    });
});
</script>
<script>
    document.addEventListener("DOMContentLoaded", function () {

    flatpickr("#date", {
    dateFormat: "Y-m-d H:i",  // فرمت تاریخ و ساعت
    enableTime: true,          // فعال کردن انتخاب ساعت
    noCalendar: false,         // تقویم را نمایش می‌دهد
    time_24hr: true,           // نمایش ساعت به صورت 24 ساعته
    disable: [
        {
            from: "1900-01-01", // غیرفعال کردن روزهای قبل از امروز
        }
    ],
    altInput: false,           // به حالت پیش‌فرض تقویم برمی‌گردد (نمایش تاریخ)
    allowInput: false,         // جلوگیری از تایپ دستی تاریخ
    locale: {
        firstDayOfWeek: 6,     // شنبه به عنوان اولین روز هفته
    },
    timeZone: "Asia/Tehran",   // تنظیم منطقه زمانی تهران
    minDate: "today",         // جلوگیری از انتخاب روزهای قبل از امروز
    onChange: function(selectedDates, dateStr, instance) {
        // ارسال تاریخ صحیح به سرور
        document.getElementById('date').value = dateStr; // تاریخ و ساعت کامل در فیلد قرار می‌گیرد
    }
});
 
    });
</script>
<?php include('includes/footer.php');?> 