<?php 

    include('includes/header.php');
    include('includes/function.php');
	include('language/language.php'); 
	include('includes/jdf.php'); 
	
// 	ini_set('display_errors', 1);
//     ini_set('display_startup_errors', 1);
//     error_reporting(E_ALL);
    
    $date_buy = date('Y-m-d');

    if (isset($_POST['submit']) and isset($_GET['add'])){
        
        global $mysqli;
        $sql = "SELECT username FROM users where username= '".$_POST['username']."' ";       
        $result = mysqli_query($mysqli,$sql);
        $num_rows = mysqli_num_rows($result);
        $sub = 'بسته '.$_POST['date'].' روزه';

        if ($num_rows > 0){
                
                $_SESSION['msg']="22";
                header('Location:add_userPro.php?add');              
                exit('<meta http-equiv="Refresh" content="0; url="add_userPro.php?add">');          
                
    	} else {
                
                $data = array(
         		'username'  =>  $_POST['username'],
        		'password'  =>  $_POST['password'],
                'total'         => $_POST['total'],
                'adate'         => $_POST['date'],
                'owner'         => $_SESSION['admin_name'],
                'permium'       => 'Free',	 
                'oprator'       => 'ALL',
                'multi'         => '1',
                'full'          => $_POST['total'],
                'id_from'       => $_POST['op_agents'],		
                'from_id'       => $_POST['op_agents'],
                'subscription'  => $sub,
                'date_buy'      => $date_buy,
                );
        
                	$qry = Insert('users',$data);
    
        			$_SESSION['msg']="10";
        			header("location:usersFree.php");	 
        			exit;
    		}
    }
	
	if (isset($_GET['user_id'])){
			 
			$user_qry="SELECT * FROM users where id='".$_GET['user_id']."'";
			$user_result=mysqli_query($mysqli,$user_qry);
			$user_row=mysqli_fetch_assoc($user_result);
			$username = $user_row['username'];
	}
	
	if(isset($_POST['submit']) and isset($_POST['user_id'])){
		
		$addDate = futureDate($_POST['date']);
		
		if ($user_row['owner'] != "APP"){
    		if ($user_row['login'] == true && $user_row['date'] != '0000-00-00'){
    		    
    			$data = array(
    			'password'  =>  $_POST['password'],
    			'total'     =>  $_POST['total'],
    			'date'      =>  $addDate,			 			
        		'adate'     =>  $_POST['date'],
    			'size'      =>  $_POST['size'],		 
    			'permium'   =>  'Free',	 
    			'oprator'   =>  'ALL',		 
    			'multi'     =>  '1',
    			'id_from'   =>  $_POST['op_agents'],		
    			'from_id'   =>  $_POST['op_agents'],
    			'subscription'  =>  $sub
    			);
    			
     	    } else if ($user_row['date'] == '0000-00-00'){
     	    
    			$data = array(
    			'password'  =>  $_POST['password'],
    			'total'     =>  $_POST['total'],
    			'permium'   =>  'Free',	 
    			'oprator'   =>  'ALL',		 
    			'multi'     =>  '1',
    			'id_from'   =>  $_POST['op_agents'],		
    			'from_id'   =>  $_POST['op_agents'],
    			);
     	    } 
     	    
		} else {
		    
    		if ($user_row['login'] == true && $user_row['date'] != '0000-00-00'){
    		    
    			$data = array(
    			'password'  =>  $_POST['password'],
    			'total'     =>  $_POST['total'],
    			'date'      =>  $addDate,			 			
        		'adate'     =>  $_POST['date'],			 			
    			'size'      =>  $_POST['size'],		 
    			'multi'     =>  '1',
    			'id_from'   =>  $_POST['op_agents'],		
    			'from_id'   =>  $_POST['op_agents'],
    			'subscription'  =>  $sub
    			);
    			
     	    } else if ($user_row['date'] == '0000-00-00'){
     	    
    			$data = array(
    			'password'  =>  $_POST['password'],
    			'total'     =>  $_POST['total'],
    			'multi'     =>  '1',
    			'id_from'   =>  $_POST['op_agents'],		
    			'from_id'   =>  $_POST['op_agents'],
    			);
     	    } 
		}
			
		$user_edit=Update('users', $data, "WHERE id = '".$_POST['user_id']."'");
		 
			if ($user_edit > 0){
				
    			$_SESSION['msg']="11";
    			header("location:usersFree.php");	 
    			exit;
			} 	
	}
	
    $Aquery = "SELECT * FROM devices WHERE username = '$username'";
    $Aresult = mysqli_query($mysqli, $Aquery);
    $deviceModels = [];
    if ($Aresult && mysqli_num_rows($Aresult) > 0) {
        while ($Arow = mysqli_fetch_assoc($Aresult)) {
            $deviceModels[] = $Arow['model'];            
            $deviceIds[] = $Arow['deviceId'];
            $deviceStatus[] = $Arow['status'];
            $deviceSizes[] = $Arow['size'];
        }
    }?>

<style>
    .device-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 10px;
        margin-bottom: 10px;
    }

    .device-name {
        font-weight: bold;
        color: #333;
        flex: 2;
    }

    .device-size {
        background: #eef;
        color: #007BFF;
        padding: 5px 10px;
        border-radius: 5px;
        font-size: 0.9em;
        text-align: center;
        flex: 1;
    }

    .button-container {
        display: flex;
        gap: 10px;
        flex: 1;
    }

    .eye-button,
    .delete-button {
        padding: 5px 10px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 0.9em;
    }


</style>

 <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="page_title_block">
            <div class="col-md-5 col-xs-12">
              <div class="page_title"><?php if(isset($_GET['user_id'])){?>ویرایش<?php }else{?>اضافه کردن<?php }?> کاربر رایگان</div>
            </div>
          </div>
          <div class="clearfix"></div>
          <div class="row mrg-top">
            <div class="col-md-12">
               
              <div class="col-md-12 col-sm-12">
                <?php if(isset($_SESSION['msg'])){?> 
               	 <div class="alert alert-danger alert-dismissible" role="alert"> <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                	<?php echo $client_lang[$_SESSION['msg']]; ?></a> </div>
                <?php unset($_SESSION['msg']);}?>	
              </div>
            </div>
          </div>
          <div class="card-body mrg_bottom"> 
            <form action="" name="addedituser" method="post" class="form form-horizontal" enctype="multipart/form-data" >
             <input  type="hidden" name="user_id" value="<?php echo $_GET['user_id'];?>" />
              <div class="section">
                <div class="section-body">
                    
                    <div class="form-group">
                    <label class="col-md-3 control-label">نام کاربری :</label>
                    <button type="button" id="generateUsername" class="btn btn-primary">تولید رندوم</button>
                    <div class="col-md-6"><?php if(isset($_GET['user_id'])){?>
                      <input type="text" name="username" id="username" min="4" max="20" onkeypress="return /[a-z0-9]/i.test(event.key)" value="<?php if(isset($_GET['user_id'])){echo $user_row['username'];}?>" class="form-control" disabled required>
                      <?php } else {?>
                      <input type="text" name="username" id="username" min="4" max="20" class="form-control" required>
                      <?php }?>
                    </div>
                  </div>
                  
                    <div class="form-group">
                    <label class="col-md-3 control-label">پسورد :</label>
                    <button type="button" id="generatePassword" class="btn btn-primary">تولید رندوم</button>
                    <div class="col-md-6"><?php if(isset($_GET['user_id'])){?>
                      <input type="text" name="password" id="password" min="4" max="20" onkeypress="return /[a-z0-9]/i.test(event.key)" value="<?php if(isset($_GET['user_id'])){echo $user_row['password'];}?>" class="form-control" required>
                      <?php } else {?>
                      <input type="text" name="password" id="password" min="4" max="20" onkeypress="return /[a-z0-9]/i.test(event.key)" value="<?php if(isset($_GET['user_id'])){echo $user_row['password'];}?>" class="form-control" required>
                      <?php }?>
                    </div>
                  </div>
                  
                    <div class="form-group">
                    <label class="col-md-3 control-label">حجم مصرفی :</label>
                    <div class="col-md-6"><?php if(isset($_GET['user_id'])){?>
                      <input type="number" name="size" id="size" min="0" max="1000000" value="<?php if(isset($_GET['user_id'])){echo $user_row['size'];}?>" class="form-control"  required>
                      <?php } else {?>
                      <input type="number" name="size" id="size" min="0" max="1000000" value="<?php if(isset($_GET['user_id'])){echo $user_row['size'];}?>" class="form-control" disabled required>
                      <?php }?>
                    </div>
                  </div>
                    
                    <div class="form-group">
                        <label class="col-md-3 control-label">حجم کل :</label>
                        <div class="col-md-6">
                          <input type="text" name="total" id="total" min="0" max="1024" value="<?php if(isset($_GET['user_id'])){echo $user_row['total'];}?>" class="form-control" required>
                        </div>
                  </div>
                      
                    <div class="form-group">
                        <?php
                  	

                        
    				$date_day = days_left($user_row['date']);
    					
                        if (isset($_GET['user_id']) && $user_row['date'] != '0000-00-00') {?>
                            <label class="col-md-3 control-label">روزهای مانده :</label>
                            <div class="col-md-6">
                              <input type="number" name="date" id="date" min="0" max="1825" value="<?php if(isset($_GET['user_id'])){echo $date_day;}?>" class="form-control" required>
                        </div><?php } elseif (isset($_GET['user_id']) && $user_row['date'] == '0000-00-00') {?>
                            <label class="col-md-3 control-label">روزهای مانده :</label>
                            <div class="col-md-6">
                              <input type="number" name="date" id="date" min="0" max="1825" value="<?php if(isset($_GET['user_id'])){echo $date_day;}?>" class="form-control" required>
                        </div><?php } else {?>
                            <label class="col-md-3 control-label">تاریخ اکانت :</label>
                            <div class="col-md-6">
                            <select name="date" id="date" class="select2">
                              <option value="1" <?php if($user_row['date']=='1'){?>selected<?php }?>>1 روز</option>
                              <option value="3" <?php if($user_row['date']=='3'){?>selected<?php }?>>3 روز</option>
                              <option value="7" <?php if($user_row['date']=='7'){?>selected<?php }?>>1 هفته</option>
                              <option value="30" <?php if($user_row['date']=='30'){?>selected<?php }?>>1 ماه</option>
                              <option value="60" <?php if($user_row['date']=='60'){?>selected<?php }?>>2 ماه</option>
                              <option value="90" <?php if($user_row['date']=='90'){?>selected<?php }?>>3 ماه</option>
                              <option value="180" <?php if($user_row['date']=='180'){?>selected<?php }?>>6 ماه</option>
                              <option value="365" <?php if($user_row['date']=='365'){?>selected<?php }?>>12 ماه</option>
                              <option value="1825" <?php if($user_row['date']=='1825'){?>selected<?php }?>>نامحدود</option>
                            </select>
                        </div>
                        <?php }?>
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
                        <label class="col-md-3 control-label">حساب مولتی یوزر :</label>
                        <div class="col-md-3">
                            <select name="op_multi" id="op_multi" class="select2">
                                <option value="1" <?php if($user_row['multi']=='1'){?>selected<?php }?>>1 نفره</option>
                                <option value="2" <?php if($user_row['multi']=='2'){?>selected<?php }?>>2 نفره</option>
                                <option value="3" <?php if($user_row['multi']=='3'){?>selected<?php }?>>3 نفره</option>
                                <option value="4" <?php if($user_row['multi']=='4'){?>selected<?php }?>>4 نفره</option>
                                <option value="5" <?php if($user_row['multi']=='5'){?>selected<?php }?>>5 نفره</option>
                                <option value="6" <?php if($user_row['multi']=='6'){?>selected<?php }?>>6 نفره</option>
                                <option value="7" <?php if($user_row['multi']=='7'){?>selected<?php }?>>7 نفره</option>
                                <option value="8" <?php if($user_row['multi']=='8'){?>selected<?php }?>>8 نفره</option>
                                <option value="9" <?php if($user_row['multi']=='9'){?>selected<?php }?>>9 نفره</option>
                                <option value="10" <?php if($user_row['multi']=='10'){?>selected<?php }?>>10 نفره</option>
                                <option value="999999" <?php if($user_row['multi']=='999999'){?>selected<?php }?>>نامحدود</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="number" value="<?php if(isset($_GET['user_id'])){echo $user_row['is_login'];}?>" class="form-control" disabled="" required>
                        </div>                    
                        <button type="button" id="resetUser" class="btn btn-primary">ریست کاربر</button>
    
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">دستگاه‌ها:</label>
                        <div class="col-md-6">
                            <div id="deviceList" class="device-list"></div>
                        </div>
                    </div>
                        
                    <div class="form-group">
                        <p class="col-md-6">
                        </p>
                    </div>

                    <div class="form-group">
                        <div class="col-md-9 col-md-offset-3">
                          <button type="submit" name="submit" class="btn btn-primary">ذخیره</button>
                        </div>
                  </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    
    <script>
        document.addEventListener("DOMContentLoaded", function(){
            var connectedDevicesField = document.getElementById('connectedDevices');
            if (connectedDevicesField.value === '|') {
                connectedDevicesField.value = 'کاربری وارد نشده';
            }
        });
        document.getElementById('generateUsername').addEventListener('click', function() {
            var randomUsername = generateRandomUsername();
            document.getElementById('username').value = randomUsername;
        });
        document.getElementById('generatePassword').addEventListener('click', function() {
            var randomUsername = generateRandomPassword();
            document.getElementById('password').value = randomUsername;
        });
        function generateRandomUsername() {
            return "user" + Math.floor(Math.random() * 1000);
        }
        function generateRandomPassword() {
            return Math.floor(Math.random() * 100000);
        }
        document.getElementById('resetUser').addEventListener('click', function() {
        var userId = document.querySelector('input[name="user_id"]').value;
        
        console.log("User ID: ", userId);
    
        if (confirm('آیا مطمئن هستید که می‌خواهید کاربر را ریست کنید؟')) {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "reset_user.php", true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    alert('کاربر با موفقیت ریست شد.');
                    location.reload();
                } else if (xhr.readyState == 4) {
                    alert('خطایی رخ داده است: ' + xhr.responseText);
                }
            };
            xhr.send("user_id=" + userId);
        }
    });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
        const devices = <?php echo json_encode($deviceModels); ?>;
        const deviceIds = <?php echo json_encode($deviceIds); ?>;
        const deviceStatus = <?php echo json_encode($deviceStatus); ?>;
        const deviceSizes = <?php echo json_encode($deviceSizes); ?>; // حجم دستگاه‌ها
        const userId = <?php echo $user_row['id']; ?>;
    
        const deviceList = document.getElementById('deviceList');
        deviceList.innerHTML = '';
    
        if (!devices || devices.length === 0) {
            deviceList.innerHTML = '<p>هیچ دستگاهی وارد نشده</p>';
            return;
        }
    
        devices.forEach((device, index) => {
            const item = document.createElement('div');
            item.classList.add('device-item');
            item.innerText = device;
    
            const currentStatus = deviceStatus[index];
    
            // کادر نمایش حجم
            const sizeBox = document.createElement('span');
            sizeBox.classList.add('size-box');
            const size = deviceSizes[index]; // مقدار حجم از آرایه
            sizeBox.innerText = size >= 1000 
                ? `${(size / 1000).toFixed(2)} گیگابایت`
                : `${size} مگابایت`;
    
            item.appendChild(sizeBox);
    
            const eyeButton = document.createElement('button');
            eyeButton.classList.add('eye-button');
            item.dataset.status = currentStatus;
    
            if (currentStatus === 'inactive') {
                eyeButton.innerHTML = '🚫'; // غیرفعال
            } else {
                eyeButton.innerHTML = '👁️'; // فعال
            }
    
            item.prepend(eyeButton);
    
            const deleteButton = document.createElement('button');
            deleteButton.classList.add('delete-button');
            deleteButton.innerHTML = '❌';
    
            // دکمه حذف
            deleteButton.addEventListener('click', function () {
                if (confirm('آیا از حذف این دستگاه مطمئن هستید؟')) {
                    const deviceId = deviceIds[index];
                    const data = `userId=${encodeURIComponent(userId)}&deviceId=${encodeURIComponent(deviceId)}`;
    
                    var xhr = new XMLHttpRequest();
                    xhr.open("POST", "delete_device.php", true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    
                    xhr.onload = function () {
                        if (xhr.status === 200) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                if (response.success) {
                                    alert('دستگاه با موفقیت حذف شد');
                                    item.remove();
                                    if (deviceList.children.length === 0) {
                                        deviceList.innerHTML = '<p>هیچ دستگاهی برای نمایش وجود ندارد</p>';
                                    }
                                } else {
                                    alert(response.message || 'خطا در حذف دستگاه');
                                }
                            } catch (error) {
                                alert('خطا در پردازش پاسخ سرور');
                                console.error(error);
                            }
                        } else {
                            alert('خطا در برقراری ارتباط با سرور');
                        }
                    };
    
                    xhr.onerror = function () {
                        alert('خطا در ارسال درخواست به سرور');
                    };
    
                    xhr.send(data);
                }
            });
    
            eyeButton.addEventListener('click', function() {
                const currentStatus = item.dataset.status; // وضعیت فعلی
                const newStatus = (currentStatus === 'active') ? 'inactive' : 'active';
    
                item.dataset.status = newStatus; // تغییر وضعیت در DOM
                eyeButton.innerHTML = (newStatus === 'active') ? '👁️' : '🚫'; // تغییر آیکن
    
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "update_status.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    
                const deviceId = deviceIds[index];
                const data = `deviceId=${encodeURIComponent(deviceId)}&status=${encodeURIComponent(newStatus)}`;
    
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                alert(response.message);
                            } else {
                                alert(response.message || 'خطا در تغییر وضعیت');
                                // بازگرداندن وضعیت در صورت خطا
                                const revertStatus = (newStatus === 'active') ? 'inactive' : 'active';
                                item.dataset.status = revertStatus;
                                eyeButton.innerHTML = (revertStatus === 'active') ? '👁️' : '🚫';
                            }
                        } catch (error) {
                            alert('خطا در پردازش پاسخ سرور');
                            console.error(error);
                        }
                    } else {
                        alert('خطا در ارتباط با سرور');
                    }
                };
    
                xhr.onerror = function() {
                    alert('خطا در ارسال درخواست به سرور');
                };
    
                xhr.send(data);
            });
    
            item.appendChild(deleteButton);
            deviceList.appendChild(item);
        });
    });
    </script>

<?php include('includes/footer.php');?>                  