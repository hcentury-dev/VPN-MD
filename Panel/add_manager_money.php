<?php 
    include('includes/header.php');
    include('includes/function.php');
	include('language/language.php'); 
	include('includes/jdf.php'); 
	
	ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    
    $admin_qry="SELECT * FROM admin where id='".$_SESSION['id']."'";
		$admin_result=mysqli_query($mysqli,$admin_qry);
		$admin_row=mysqli_fetch_assoc($admin_result);
    
    function generateRandomNumber() {
        global $mysqli;
        do {
            $randomNumber = str_pad(mt_rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
            $stmt = $mysqli->prepare("SELECT COUNT(*) FROM agents WHERE from_id = ?");
            $stmt->bind_param("s", $randomNumber);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();
        } while ($count > 0);
        
        return $randomNumber;
    }
    
    if (isset($_POST['submit']) && isset($_GET['add'])) {
        
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $owner = trim($_POST['owner']);

        if ($_POST['inventory'] == "∞") {
            $inventory = "99999999999";
        } else {
            $inventory = $_POST['inventory'];
        }
    
        // بررسی وجود نام کاربری تکراری
        $stmt = $mysqli->prepare("SELECT username FROM manager WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $_SESSION['msg'] = "22";
            header('Location: add_manager_access.php?add');
            exit;
        }
    
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        // پردازش شناسه نماینده
        $agent_id = trim($_POST['agent_id'] ?? '');
        $skip_agent_insert = false;
        
        if (!empty($agent_id)) {
            // بررسی تکراری نبودن شناسه در جدول agents
            $stmt = $mysqli->prepare("SELECT COUNT(*) FROM agents WHERE from_id = ?");
            $stmt->bind_param("s", $agent_id);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();
            
            if ($count > 0) {
                $skip_agent_insert = true; // اگر شناسه تکراری بود، از ایجاد رکورد جدید در agents صرف نظر می‌کنیم
            }
        } else {
            $agent_id = generateRandomNumber(); // اگر شناسه وارد نشده بود، یک شناسه تصادفی ایجاد می‌کنیم
        }
        
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
        
            'inventory' => $inventory, 'size' => $_POST['size'], 'day' => "0", 'multi' => "0", 'servers' => "0",'total' => "0",
            'image' => 'profile.png', 'dlogin' => '0000-00-00', 'edate' => '0000-00-00', 'expire' => '0000-00-00',
            'date' => date('Y-m-d H:i:s')
        ];

    
        $data = array_merge($default_access, [
            'username' => $username,
            'password' => $hashedPassword,
            'device' => $_POST['device'],
            'status' => "active",
            'type' => "2",
            'owner' => $owner,
        ]);
        Insert('manager', $data);
        
	        $sql = "SELECT * FROM `agents` WHERE `agents`.`id` = 1";
            $result = mysqli_query($mysqli, $sql);
            $row = mysqli_fetch_assoc($result);
            $app_name = $row['app'];
            $app_logo = $row['logo'];

            $data2 = array(
                    'name'  =>  $_POST['username'],
                    'from_id'   =>  generateRandomNumber(),
                    'app'   =>  $app_name,
                    'logo'   =>  $app_logo,
                    'agent'   =>  $_POST['owner'],
                );
            $qry = Insert('agents', $data2);
			$_SESSION['msg']="10";
			header("location:manager.php");	 
			exit;
    }

	if (isset($_SESSION['id'])) {
			 
		$SES_qry="SELECT * FROM manager where id='".$_SESSION['id']."'";
		$SES_result=mysqli_query($mysqli,$SES_qry);
		$SES_row=mysqli_fetch_assoc($SES_result);
		
	}
	
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
          <div class="page_title">ساخت همکار براساس کیف پول</div>
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
                          <input type="text" name="username" id="username" minlength="4" maxlength="30" onkeypress="return /[a-z0-9]/i.test(event.key)" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">پسورد :</label>
                        <div class="col-md-6">
                          <input type="text" name="password" id="password" minlength="4" maxlength="30" onkeypress="return /[a-z0-9]/i.test(event.key)" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">نام و تگ همکار :</label>
                        <div class="col-md-6">
                          <input type="text" name="owner" id="owner" minlength="2" maxlength="20" onkeypress="return /[a-z0-9]/i.test(event.key)" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                    <label class="col-md-3 control-label">شناسه عددی نماینده (اختیاری):</label>
                    <div class="col-md-6">
                        <small>اگر خالی بگذارید، یک شناسه تصادفی ایجاد می‌شود</small>
                      <input type="text" name="agent_id" id="agent_id" minlength="4" maxlength="30" onkeypress="return /[0-9]/i.test(event.key)" class="form-control">
                      
                    </div>
                  </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">تعداد لاگین همکار :</label>
                        <div class="col-md-6">
                          <input type="number" name="device" id="device" minlength="1" maxlength="100" onkeypress="return /[0-9]/i.test(event.key)" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">موجودی کیف پول :</label>
                        <div class="col-md-6">
                            <small>برای نامحدود # بذارید</small>
                            <input type="text" name="inventory" id="inventory" minlength="0" maxlength="99999999999" class="form-control" required>
                        </div>
                    </div>

                <div class="form-group">
                    <label class="col-md-3 control-label">نوع خرید کاربر</label>
                    <div class="col-md-6">
                      <select name="size" id="size" class="select2" onchange="toggleFields()">                    
                        <option value="10">پلن تعریف شده مدیریت</option>
                        <option value="20">پلن دستی همکار</option>
                      </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-md-3 control-label">دسترسی همکار</label>
                    <div class="col-md-6">
                      <select name="fullAccess" id="fullAccess" class="select2" onchange="toggleFields()">                    
                        <option value="1">دسترسی کامل</option>
                        <option value="2">دسترسی دستی</option>
                      </select>
                    </div>
                </div>
            
                <div id="accessControl">
                    <div class="access-container">
                        <!-- بخش کاربران -->
                        <div class="switch-container">
                            <h3>کاربران</h3>
                            <ul class="switch-list">
                                <li>
                                    <span class="switch-label">دسترسی به کاربران</span>
                                    <label class="pure-material-switch">
                                        <input type="checkbox" name="isusers" id="isusers" checked>
                                        <span></span>
                                    </label>
                                </li>
                                <li>
                                    <span class="switch-label">دسترسی به ساخت کاربران</span>
                                    <label class="pure-material-switch">
                                        <input type="checkbox" name="creUsers" id="creUsers" checked>
                                        <span></span>
                                    </label>
                                </li>
                                <li>
                                    <span class="switch-label">دسترسی به ویرایش کاربران</span>
                                    <label class="pure-material-switch">
                                        <input type="checkbox" name="editUsers" id="editUsers" checked>
                                        <span></span>
                                    </label>
                                </li>
                                <li>
                                    <span class="switch-label">دسترسی به حذف کاربران</span>
                                    <label class="pure-material-switch">
                                        <input type="checkbox" name="delUsers" id="delUsers" checked>
                                        <span></span>
                                    </label>
                                </li>
                                <li>
                                    <span class="switch-label">دسترسی به وضعیت کاربران</span>
                                    <label class="pure-material-switch">
                                        <input type="checkbox" name="staUsers" id="staUsers" checked>
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
                                        <input type="checkbox" name="isservers" id="isservers" checked>
                                        <span></span>
                                    </label>
                                </li>
                                <li>
                                    <span class="switch-label">دسترسی به ساخت سرورها</span>
                                    <label class="pure-material-switch">
                                        <input type="checkbox" name="creServers" id="creServers" checked>
                                        <span></span>
                                    </label>
                                </li>
                                <li>
                                    <span class="switch-label">دسترسی به ویرایش سرورها</span>
                                    <label class="pure-material-switch">
                                        <input type="checkbox" name="editServers" id="editServers" checked>
                                        <span></span>
                                    </label>
                                </li>
                                <li>
                                    <span class="switch-label">دسترسی به حذف سرورها</span>
                                    <label class="pure-material-switch">
                                        <input type="checkbox" name="delServers" id="delServers" checked>
                                        <span></span>
                                    </label>
                                </li>
                                <li>
                                    <span class="switch-label">دسترسی به وضعیت سرورها</span>
                                    <label class="pure-material-switch">
                                        <input type="checkbox" name="staServers" id="staServers" checked>
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
            let totalInput = document.getElementById("inventory");
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
</script>
<script>
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