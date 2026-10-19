<?php 

    include('includes/header.php');
    include('includes/function.php');
    include('language/language.php'); 

    $date_buy = date('Y-m-d');
    
    // ini_set('display_errors', 1);
    // ini_set('display_startup_errors', 1);
    // error_reporting(E_ALL);
    
    if(isset($_SESSION['id'])){
		$manager_qry="SELECT * FROM manager where id='".$_SESSION['id']."'";
		$manager_result=mysqli_query($mysqli,$manager_qry);
		$manager_row=mysqli_fetch_assoc($manager_result);
		$agent_size = $manager_row['size'];
		$agent_total = $manager_row['total'];
		$agent_day = $manager_row['day'];
		$agent_multi = $manager_row['multi'];
		$agent_inventory = $manager_row['inventory'];
		$agent_type = $manager_row['type'];
		$agent = $manager_row['owner'];
            
        $qry_total = "SELECT SUM(total) AS totalSum FROM users WHERE agent = '$agent'";
            $total_total = mysqli_fetch_array(mysqli_query($mysqli, $qry_total));
            $total_total = $total_total['totalSum'];
        
        $qry_adate = "SELECT SUM(adate) AS adateSum FROM users WHERE agent = '$agent'";
            $total_adate = mysqli_fetch_array(mysqli_query($mysqli, $qry_adate));
            $total_adate = $total_adate['adateSum'];
            
        $qry_users="SELECT COUNT(*) as num FROM users WHERE agent = '$agent'";
            $total_users = mysqli_fetch_array(mysqli_query($mysqli,$qry_users));
            $total_users = $total_users['num'];
        
        $qry_multi = "SELECT SUM(multi) AS multiSum FROM users WHERE agent = '$agent'";
            $total_multi = mysqli_fetch_array(mysqli_query($mysqli,$qry_multi));
            $total_multi = $total_multi['multiSum'];
        
        $qry_servers="SELECT COUNT(*) as num FROM configs WHERE agent = '$agent'";
            $total_servers= mysqli_fetch_array(mysqli_query($mysqli,$qry_servers));
            $total_servers = $total_servers['num'];
    }
	
    function generateUniqueUsername($prefix, $length, $mysqli) {
        do {
            $random_username = $prefix . generateRandomString($length);
            $query = "SELECT COUNT(*) as count FROM users WHERE username = '$random_username'";
            $result = mysqli_query($mysqli, $query);
            $row = mysqli_fetch_assoc($result);
        } while ($row['count'] > 0);
        
        return $random_username;
    }

    if (isset($_POST['submit'])){
        
        $sql = "SELECT * FROM settings";
        $result = mysqli_query($mysqli, $sql);
        $row = mysqli_fetch_assoc($result);
            
        $manager_gb = $row['manager_gb'];
        $manager_day = $row['manager_day'];
        $manager_total = $row['manager_total'];
        $manager_multi = $row['manager_multi'];
        $manager_server = $row['manager_server'];
        $num_accounts = $_POST['num_accounts'];
        
        $sub = 'بسته ' . $_POST['date'] . ' روزه';
    
        if ($agent_type == "1"){
                
                $addGB = $_POST['total'] * $num_accounts + $total_total;
                if ($agent_size < $addGB) {
                    $_SESSION['msg2'] = "40";
                    header("location:add_usersPro.php?add");
                    exit;
                }
                $addDAY = $_POST['date'] * $num_accounts + $total_adate;
                if ($agent_day < $addDAY) {
                    $_SESSION['msg2'] = "42";
                    header("location:add_usersPro.php?add");
                    exit;
                }
                $addUSERS = $num_accounts + $total_users;
                if ($agent_total < $addUSERS) {
                    $_SESSION['msg2'] = "44";
                    header("location:add_usersPro.php?add");
                    exit;
                }
                $addMULTI = $_POST['op_multi'] * $num_accounts + $total_multi;
                if ($agent_multi < $addMULTI) {
                    $_SESSION['msg2'] = "46";
                    header("location:add_usersPro.php?add");
                    exit;
                }
            
                for ($i = 0; $i < $num_accounts; $i++) {
    
                    $random_username = generateUniqueUsername($_POST['name_accounts'], $_POST['na_accounts'], $mysqli);
                    $random_password = generateRandomString(4);
                    
                    $data = array(
                        'username'      => $random_username,
                        'password'      => $random_password,
                        'total'         => $_POST['total'],
                        'adate'         => $_POST['date'],
            			'owner'         =>  '_'.$agent,			 
                        'agent'         => $agent,
                        'permium'       => 'Pro',	 
                        'oprator'       => 'ALL',
                        'multi'         => $_POST['op_multi'],
                        'full'          => $_POST['total'],
                        'id_from'       => $_POST['op_agents'],		
                        'from_id'       => $_POST['op_agents'],
                        'subscription'  => $sub,
                        'date_buy'      => $date_buy,
                    );
            
                    $qry = Insert('users', $data);
                }
            
                $_SESSION['msg2'] = "10";
                header("location:usersPro.php");
                exit;
        			
    		} elseif ($agent_type == "1"){
    		  
    		    $addGB = $_POST['total'] * $manager_gb;
    		    if ($addGB > $agent_inventory) {
                    $_SESSION['msg2'] = "41";
                    header("location:add_usersPro.php?add");
                    exit;
    		    }
    		    $addDAY = $_POST['date'] * $manager_day;
    		    if ($addDAY > $agent_inventory) {
                    $_SESSION['msg2'] = "43";
                    header("location:add_usersPro.php?add");
                    exit;
                }
    		    $addTOTAL = $num_accounts * $manager_total;
    		    if ($addTOTAL > $agent_inventory) {
                    $_SESSION['msg2'] = "45";
                    header("location:add_usersPro.php?add");
                    exit;
                }
    		    $addMULTI = $_POST['op_multi'] * $manager_multi;
    		    if ($addMULTI > $agent_inventory) {
                    $_SESSION['msg2'] = "47";
                    header("location:add_usersPro.php?add");
                    exit;
                }
                
                $SUM_Mony = $addGB+$addDAY+$addTOTAL+$addMULTI;
                $Mony = array( 
				    'inventory'  =>  $agent_inventory-$SUM_Mony,
			    );

				Update('manager', $Mony, "WHERE id = '".$_SESSION['id']."'"); 
					
                for ($i = 0; $i < $num_accounts; $i++) {
    
                    $random_username = generateUniqueUsername($_POST['name_accounts'], $_POST['na_accounts'], $mysqli);
                    $random_password = generateRandomString(4);
                    
                    $data = array(
                        'username'      => $random_username,
                        'password'      => $random_password,
                        'total'         => $_POST['total'],
                        'adate'         => $_POST['date'],
            			'owner'         =>  '_'.$agent,			 
                        'agent'         => $agent,
                        'permium'       => 'Pro',	 
                        'oprator'       => 'ALL',
                        'multi'         => $_POST['op_multi'],
                        'full'          => $_POST['total'],
                        'id_from'       => $_POST['op_agents'],		
                        'from_id'       => $_POST['op_agents'],
                        'subscription'  => $sub,
                        'date_buy'      => $date_buy,
                    );
            
                    $qry = Insert('users', $data);
                }
            
                $_SESSION['msg2'] = "10";
                header("location:usersPro.php");
                exit;
    		} else {
    		    
    		    $addUSERS = $num_accounts + $total_users;
                if ($agent_total < $addUSERS) {
                    $_SESSION['msg2'] = "44";
                    header("location:add_usersPro.php?add");
                    exit;
                }
                
    		    for ($i = 0; $i < $num_accounts; $i++) {
    
                    $random_username = generateUniqueUsername($_POST['name_accounts'], $_POST['na_accounts'], $mysqli);
                    $random_password = generateRandomString(4);
                    
                    $data = array(
                        'username'      => $random_username,
                        'password'      => $random_password,
                        'total'         => $_POST['total'],
                        'adate'         => $_POST['date'],
            			'owner'         =>  '_'.$agent,			 
                        'agent'         => $agent,
                        'permium'       => 'Pro',	 
                        'oprator'       => 'ALL',
                        'multi'         => $_POST['op_multi'],
                        'full'          => $_POST['total'],
                        'id_from'       => $_POST['op_agents'],		
                        'from_id'       => $_POST['op_agents'],
                        'subscription'  => $sub,
                        'date_buy'      => $date_buy,
                    );
            
                    $qry = Insert('users', $data);
                }
            
                $_SESSION['msg2'] = "10";
                header("location:usersPro.php");
                exit;
    		}
    }

?>
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="page_title_block">
            <div class="col-md-5 col-xs-12">
            	<?php
                if ($manager_row['creUsers'] == '0'){
                    $_SESSION['msg2']="27";
                	header( "Location:usersPro.php");
                	exit;
                }
                ?>
              <div class="page_title">اضافه کردن کاربر انبوه</div>
            </div>
          </div>
          <div class="clearfix"></div>
          <div class="row mrg-top">
            <div class="col-md-12">
              <div class="col-md-12 col-sm-12">
          <?php if (isset($_SESSION['msg2']) && isset($client_lang[$_SESSION['msg2']])) { ?>
               	 <div class="alert alert-danger alert-dismissible" role="alert">
               	     <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                	<?php echo $client_lang[$_SESSION['msg2']]; ?></a> </div>
                <?php unset($_SESSION['msg2']);}?>	
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
                    <label class="col-md-3 control-label">حجم اکانت ها :</label>
                    <div class="col-md-6">
                      <input type="number" name="total" id="total" min="0" max="1024" class="form-control" required>
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
                        <select name="op_agents" id="op_agents" class="select2" required>
                            <?php
                            $sql = "SELECT * FROM agents WHERE agent = '$agent'";
                            $agents_result = mysqli_query($mysqli, $sql);
                            $disableSubmit = false;
                            if ($agents_result && mysqli_num_rows($agents_result) > 0) {
                                while ($row = mysqli_fetch_assoc($agents_result)) {
                                    $selected = ($row['from_id'] == $user_row['id_from']) ? "selected" : "";
                                    echo "<option value='" . $row['from_id'] . "' " . $selected . ">" . $row['name'] . "</option>";
                                }
                                mysqli_free_result($agents_result);
                            } else {
                                echo "<option value=''>هیچ نماینده‌ای یافت نشد</option>";
                                $disableSubmit = true; // دکمه غیرفعال شود
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">تعداد دستگاه‌های قابل اتصال :</label>
                    <div class="col-md-6">
                        <input type="number" name="op_multi" id="op_multi" min="1" max="99999" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-9 col-md-offset-3">
                        <button type="submit" name="submit" class="btn btn-primary" <?php echo $disableSubmit ? 'disabled' : ''; ?>>ذخیره</button>
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