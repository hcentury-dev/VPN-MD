<?php 

    include('includes/header.php');
    include('includes/function.php');
	include('language/language.php'); 
	
// 	ini_set('display_errors', 1);
//     ini_set('display_startup_errors', 1);
//     error_reporting(E_ALL);
    
    $date_buy = date('Y-m-d');
    
    function days_left($expiry_date){
        $today = time();
        $diff = strtotime($expiry_date) - $today;
        if ($diff < 0) {
            return 0;
        } else {
            return ceil($diff / (60 * 60 * 24));
        }
    }

    if (isset($_SESSION['id'])){
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
	
    function futureDate($days) {
	    
        $today = new DateTime();
        $today->add(new DateInterval("P{$days}D"));
        return $today->format('Y-m-d');
    	    
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
    
    if (isset($_POST['submit']) and isset($_GET['add'])){
        
        global $mysqli;
        $sql = "SELECT username FROM users where username= '".$_POST['username']."' ";       
        $result = mysqli_query($mysqli,$sql);
        $num_rows = mysqli_num_rows($result);
        
        $day = date('Y-m-d', strtotime('+' . $_POST['date'] . ' day'));
        $sub = 'بسته ' . $_POST['date'] . ' روزه';
    
        $sql = "SELECT * FROM settings";
            $result = mysqli_query($mysqli, $sql);
            $row = mysqli_fetch_assoc($result);
            
            $manager_gb = $row['manager_gb'];
            $manager_day = $row['manager_day'];
            $manager_total = $row['manager_total'];
            $manager_multi = $row['manager_multi'];
            $manager_server = $row['manager_server'];
                
        if ($num_rows > 0){
                
                $_SESSION['msg2']="22";
                header('Location:add_userPro.php?add');              
                exit('<meta http-equiv="Refresh" content="0; url="add_userPro.php?add">');          
                
                
    	} else {

            if ($agent_type == "1"){
                
                $addGB = $_POST['total'] + $total_total;
                if ($agent_size < $addGB) {
                    $_SESSION['msg2'] = "40";
                    header("location:add_userPro.php?add");
                    exit;
                }
                $addDAY = $_POST['date'] + $total_adate;
                if ($agent_day < $addDAY) {
                    $_SESSION['msg2'] = "42";
                    header("location:add_userPro.php?add");
                    exit;
                }
                $addUSERS = 1 + $total_users;
                if ($agent_total < $addUSERS) {
                    $_SESSION['msg2'] = "44";
                    header("location:add_userPro.php?add");
                    exit;
                }
                $addMULTI = $_POST['op_multi'] + $total_multi;
                if ($agent_multi < $addMULTI) {
                    $_SESSION['msg2'] = "46";
                    header("location:add_userPro.php?add");
                    exit;
                }
            
                $data = array(
         		'username'  =>  $_POST['username'],
        		'password'  =>  $_POST['password'],
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
        
            	$qry = Insert('users',$data);

    			$_SESSION['msg2']="10";
    			header("location:usersPro.php");	 
    			exit;
        			
    		} elseif ($agent_type == "2"){
    		    
    		  if ($agent_size == "10"){
    		    
        		$sub = 'بسته ' . $_POST['plan_day'] . ' روزه';
    		  
    		    if ($_POST['plan_price'] > $agent_inventory) {
                    $_SESSION['msg2'] = "400";
                    header("location:add_userPro.php?add");
                    exit;
    		    }
                
                $Mony = array( 
				    'inventory'  =>  $agent_inventory-$_POST['plan_price'],
			    );

				Update('manager', $Mony, "WHERE id = '".$_SESSION['id']."'"); 
					
                $data = array(
         		'username'  =>  $_POST['username'],
        		'password'  =>  $_POST['password'],
                'total'         => $_POST['plan_size'],
                'adate'         => $_POST['plan_day'],
    			'owner'         =>  '_'.$agent,			 
                'agent'         => $agent,
                'permium'       => 'Pro',	 
                'oprator'       => 'ALL',
                'multi'         => $_POST['plan_multi'],
                'full'          => $_POST['plan_size'],
                'price'         => $_POST['plan_price'],
                'id_from'       => $_POST['op_agents'],		
                'from_id'       => $_POST['op_agents'],
                'subscription'  => $sub,
                'date_buy'      => $date_buy,
                );
        
            	$qry = Insert('users',$data);

    			$_SESSION['msg2']="10";
    			header("location:usersPro.php");	 
    			exit;
    		  } else {
    		    
    		    $sub = 'بسته ' . $_POST['date'] . ' روزه';
    		  
    		    $addGB = $_POST['total'] * $manager_gb;
    		    if ($addGB > $agent_inventory) {
                    $_SESSION['msg2'] = "41";
                    header("location:add_userPro.php?add");
                    exit;
    		    }
    		    $addDAY = $_POST['date'] * $manager_day;
    		    if ($addDAY > $agent_inventory) {
                    $_SESSION['msg2'] = "43";
                    header("location:add_userPro.php?add");
                    exit;
                }
    		    $addTOTAL = 1 * $manager_total;
    		    if ($addTOTAL > $agent_inventory) {
                    $_SESSION['msg2'] = "45";
                    header("location:add_userPro.php?add");
                    exit;
                }
    		    $addMULTI = $_POST['op_multi'] * $manager_multi;
    		    if ($addMULTI > $agent_inventory) {
                    $_SESSION['msg2'] = "47";
                    header("location:add_userPro.php?add");
                    exit;
                }
                
                $SUM_Mony = $addGB+$addDAY+$addTOTAL+$addMULTI;
                $Mony = array( 
				    'inventory'  =>  $agent_inventory-$SUM_Mony,
			    );

				Update('manager', $Mony, "WHERE id = '".$_SESSION['id']."'"); 
					
                $data = array(
         		'username'  =>  $_POST['username'],
        		'password'  =>  $_POST['password'],
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
        
            	$qry = Insert('users',$data);

    			$_SESSION['msg2']="10";
    			header("location:usersPro.php");	 
    			exit;
    		  }
    		    
    		} else {
    		           
    		    $addUSERS = 1 + $total_users;
                if ($agent_total < $addUSERS) {
                    $_SESSION['msg2'] = "44";
                    header("location:add_userPro.php?add");
                    exit;
                }
                
    		    $data = array(
         		'username'  =>  $_POST['username'],
        		'password'  =>  $_POST['password'],
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
        
            	$qry = Insert('users',$data);
    			$_SESSION['msg2']="10";
    			header("location:usersPro.php");	 
    			exit;
    		}
        }
        
    }
	
	if (isset($_GET['user_id'])){
		$user_qry="SELECT * FROM users where id='".$_GET['user_id']."'";
		$user_result=mysqli_query($mysqli,$user_qry);
		$user_row=mysqli_fetch_assoc($user_result);
	}
	
	if (isset($_POST['submit']) and isset($_POST['user_id'])){
		
		$sql = "SELECT * FROM settings";
        $result = mysqli_query($mysqli, $sql);
        $row = mysqli_fetch_assoc($result);
        
        $manager_gb = $row['manager_gb'];
        $manager_day = $row['manager_day'];
        $manager_total = $row['manager_total'];
        $manager_multi = $row['manager_multi'];
        $manager_server = $row['manager_server'];
        
        $day = date('Y-m-d', strtotime('+' . $_POST['date'] . ' day'));
        $sub = 'بسته ' . $_POST['date'] . ' روزه';
    
        if ($user_row['login'] == true && $user_row['date'] != '0000-00-00') {
            
            if ($agent_type == "1"){
                
                if ($_POST['total'] >= $user_row['total']) {
                    $addGB = $_POST['total'] + $total_total;
                    $addGB2 = $_POST['total'];
                    if ($agent_size < $addGB) {
                        exitWithMessage(41);
                    }
                } else {
                    $addGB2 = $_POST['total'];
                }
                
                
                
                /////////////////////////////////////////
                // محاسبه اختلاف روز
                $date_day = days_left($user_row['date']); // روزهای باقی‌مانده فعلی کاربر
                if ($_POST['date'] > $date_day) {
                
                    $difference = $_POST['date'] - $date_day; // فقط اختلاف روز جدید
                
                    // بررسی اینکه نماینده به اندازه کافی موجودی داره
                    if ($agent_day < $difference) {
                        exitWithMessage(45); // خطای کمبود روز
                    }
                
                    // کم کردن اختلاف از موجودی نماینده
                    $NewAgentDay = $agent_day - $difference;
                    $updateAgent = mysqli_query($mysqli, "UPDATE manager SET day = '$NewAgentDay' WHERE id='".$_SESSION['id']."'");
                
                    // بروزرسانی تاریخ پایان کاربر
                    $addDAY2 = futureDate($_POST['date']);
                } else {
                    $addDAY2 = $user_row['date']; // اگر روز کمتر یا مساوی بود
                }
                /////////////////////////////////////////
                
                if ($_POST['op_multi'] >= $user_row['multi']) {
                    $addMULTI = $_POST['op_multi'] + $total_multi;
                    $addMULTI2 = $_POST['op_multi'];
                    if ($agent_multi < $addMULTI) {
                        exitWithMessage(47);
                    }
                } else {
                    $addMULTI2 = $_POST['op_multi'];
                }
                if ($_POST['op_multi'] == 0) {
                    exitWithMessage(48);
                }
                
				$data = [
                    'password'     => $_POST['password'],
                    'total'        => $addGB2,
                    'date'         => $addDAY2,
                    'multi'        => $addMULTI2,
                    'id_from'      => $_POST['op_agents'],
                    'from_id'      => $_POST['op_agents'],
                ];
                $user_edit=Update('users', $data, "WHERE id = '".$_POST['user_id']."'");
    			if ($user_edit > 0){
        			$_SESSION['msg2']="11";
        			header("location:usersPro.php");	 
        			exit;
    			}
    			
            } elseif ($agent_type == "2"){
    		    
    		    if ($agent_size == "20"){
    		        
    		        if ($_POST['total'] > $user_row['total']) {
                    $difference = $_POST['total'] - $user_row['total'];
                    $addGB = $difference * $manager_gb; 
                    if ($addGB > $agent_inventory) {
                        exitWithMessage(41);
                    }
                }                
                    $date_day = days_left($user_row['date']);
                    if ($_POST['date'] > $date_day) {
                        $difference = $_POST['date'] - $date_day;
                        $addDAY = $difference * $manager_day; 
                        if ($addDAY > $agent_inventory) {
                            exitWithMessage(45);
                        }
                    }
                    if ($_POST['op_multi'] > $user_row['multi']) {
                        $difference = $_POST['op_multi'] - $user_row['multi'];
                        $addMULTI = $difference * $manager_multi; 
                        if ($addMULTI > $agent_inventory) {
                            exitWithMessage(47);
                        }
                    } 
                    if ($_POST['op_multi'] == 0) {
                            exitWithMessage(48);
                    }
    
                    $SUM_Mony = $addGB + $addDAY + $addMULTI;
                    $Mony = array( 
    				    'inventory'  =>  $agent_inventory-$SUM_Mony,
    			    );
    
    				Update('manager', $Mony, "WHERE id = '".$_SESSION['id']."'"); 
    				$addDate = futureDate($_POST['date']);
    				
    				$data = [
                        'password'     => $_POST['password'],
                        'total'        => $_POST['total'],
                        'date'         => $addDate,
                        'multi'        => $_POST['op_multi'],
                        'id_from'      => $_POST['op_agents'],
                        'from_id'      => $_POST['op_agents'],
                    ];
                    $user_edit=Update('users', $data, "WHERE id = '".$_POST['user_id']."'");
        			if ($user_edit > 0){
            			$_SESSION['msg2']="11";
            			header("location:usersPro.php");	 
            			exit;
        			}

    		    }
                
            } else {
                $addDate = futureDate($_POST['date']);
                $data = [
                    'password'     => $_POST['password'],
                    'total'        => $_POST['total'],
                    'date'         => $addDate,
                    'multi'        => $_POST['op_multi'],
                    'id_from'      => $_POST['op_agents'],
                    'from_id'      => $_POST['op_agents'],
                ];
                $user_edit=Update('users', $data, "WHERE id = '".$_POST['user_id']."'");
    			if ($user_edit > 0){
        			$_SESSION['msg2']="11";
        			header("location:usersPro.php");	 
        			exit;
    			}
            }
            
        } else if ($user_row['date'] == '0000-00-00') {
            
            if ($agent_type == "1"){
                
                if ($_POST['total'] > $user_row['total']) {
                    $addGB2 = $_POST['total']; 
                    $addGB = $_POST['total'] + $total_total;
                    if ($agent_size < $addGB) {
                        exitWithMessage(41);
                    }
                } else {
                    $addGB2 = $_POST['total'];
                }               
                if ($_POST['op_multi'] > $user_row['multi']) {
                    $addMULTI2 = $_POST['op_multi']; 
                    $addMULTI = $_POST['op_multi'] + $total_multi;
                    if ($agent_multi < $addMULTI) {
                        exitWithMessage(47);
                    }
                } else {
                    $addMULTI2 = $_POST['op_multi'];
                } 
                if ($_POST['op_multi'] == 0) {
                    exitWithMessage(48);
                }

                $data = [
                    'password'     => $_POST['password'],
                    'total'        => $addGB2,
                    'multi'        => $addMULTI2,
                    'id_from'      => $_POST['op_agents'],
                    'from_id'      => $_POST['op_agents'],
                ];
                $user_edit=Update('users', $data, "WHERE id = '".$_POST['user_id']."'");
    			if ($user_edit > 0){
        			$_SESSION['msg2']="11";
        			header("location:usersPro.php");	 
        			exit;
    			}
    			
            } elseif ($agent_type == "2"){
    		  if ($agent_size == "20"){
                if ($_POST['total'] > $user_row['total']) {
                    $difference = $_POST['total'] - $user_row['total'];
                    $addGB = $difference * $manager_gb; 
                    if ($addGB > $agent_inventory) {
                        exitWithMessage(41);
                    }
                }               
                if ($_POST['op_multi'] > $user_row['multi']) {
                    $difference = $_POST['op_multi'] - $user_row['multi'];
                    $addMULTI = $difference * $manager_multi; 
                    if ($addMULTI > $agent_inventory) {
                        exitWithMessage(47);
                    }
                } 
                if ($_POST['op_multi'] == 0) {
                        exitWithMessage(48);
                }

                $SUM_Mony = $addGB + $addMULTI;
                $Mony = array( 
				    'inventory'  =>  $agent_inventory-$SUM_Mony,
			    );

				Update('manager', $Mony, "WHERE id = '".$_SESSION['id']."'"); 
				
				$data = [
                    'password'     => $_POST['password'],
                    'total'        => $_POST['total'],
                    'multi'        => $_POST['op_multi'],
                    'id_from'      => $_POST['op_agents'],
                    'from_id'      => $_POST['op_agents'],
                ];
                $user_edit=Update('users', $data, "WHERE id = '".$_POST['user_id']."'");
    			if ($user_edit > 0){
        			$_SESSION['msg2']="11";
        			header("location:usersPro.php");	 
        			exit;
    			}
    		  }
            } else {
                $data = [
                    'password'     => $_POST['password'],
                    'total'        => $_POST['total'],
                    'multi'        => $_POST['op_multi'],
                    'id_from'      => $_POST['op_agents'],
                    'from_id'      => $_POST['op_agents'],
                ];
                $user_edit=Update('users', $data, "WHERE id = '".$_POST['user_id']."'");
    			if ($user_edit > 0){
        			$_SESSION['msg2']="11";
        			header("location:usersPro.php");	 
        			exit;
    			}
            }
        }
	}
	
	function exitWithMessage($msg) {
        $_SESSION['msg2'] = $msg;
        header("location:add_userPro.php?user_id = '".$_POST['user_id']."'");
        exit;
    }
	
?>

 <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="page_title_block">
            <div class="col-md-5 col-xs-12">
                <?php
                if(isset($_GET['user_id'])){
                    if ($manager_row['editUsers'] == '0'){
                        $_SESSION['msg2']="27";
                    	header("Location:usersPro.php");
                    	exit;
                    }
                } else {
                    if ($manager_row['creUsers'] == '0'){
                        $_SESSION['msg2']="27";
                    	header("Location:usersPro.php");
                    	exit;
                    }
                }
                ?>
              <div class="page_title"><?php if(isset($_GET['user_id'])){?>ویرایش<?php }else{?>اضافه کردن<?php }?> کاربر اختصاصی</div>
            </div>
          </div>
          <div class="clearfix"></div>
          <div class="row mrg-top">
            <div class="col-md-12">
              <div class="col-md-12 col-sm-12">
            <?php if (isset($_SESSION['msg2']) && isset($client_lang[$_SESSION['msg2']])) { ?>
               	 <div class="alert alert-danger alert-dismissible" role="alert"> <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                	<?php echo $client_lang[$_SESSION['msg2']]; ?></a> </div>
                <?php unset($_SESSION['msg2']);}?>	
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
                
                <?php if ($agent_type == "2" && $agent_size == "10"){?>
                    <div class="form-group">
                        <label class="col-md-3 control-label">انتخاب پلن :</label>
                        <div class="col-md-6">
                            <select name="agents" id="agents" class="select2" required onchange="updatePlanDetails()">
                                <?php
                                $sql = "SELECT * FROM plans WHERE status = 'active'"; // فقط پلن‌های فعال
                                $agents_result = mysqli_query($mysqli, $sql);
                                $disableSubmit = false;
                                if ($agents_result && mysqli_num_rows($agents_result) > 0) {
                                    $first = true; // بررسی اولین گزینه
                                    while ($row = mysqli_fetch_assoc($agents_result)) {
                                        $size = (int) $row['size'];
                                        $day = (int) $row['day'];
                                        $multi = $row['multi'];
                                        $price = number_format($row['price']);
                                        $sizeText = ($size >= 1024) ? "نامحدود" : "$size گیگ";
                                        $dayText = ($day >= 1800) ? "نامحدود" : "$day روز";
                                        $plan_info = "$sizeText 👈 $dayText 👈 $multi کاربره 👈 $price تومان";
                                        $selected = $first ? "selected" : "";
                                        $first = false;
                    
                                        echo "<option value='" . $row['id'] . "' 
                                                  data-size='" . $row['size'] . "' 
                                                  data-day='" . $row['day'] . "' 
                                                  data-price='" . $row['price'] . "' 
                                                  data-multi='" . $row['multi'] . "' 
                                                  " . $selected . ">" . $plan_info . "</option>";
                                    }
                                    mysqli_free_result($agents_result);
                                } else {
                                    echo "<option value=''>هیچ ‌پلنی یافت نشد</option>";
                                    $disableSubmit = true;
                                }
                                ?>
                            </select>
                        </div>
                </div>
                    <input type="hidden" name="plan_size" id="plan_size">
                    <input type="hidden" name="plan_day" id="plan_day">
                    <input type="hidden" name="plan_price" id="plan_price">
                    <input type="hidden" name="plan_multi" id="plan_multi">        
                <?php } else {?>        
                    <div class="form-group">
                    <label class="col-md-3 control-label">حجم مصرفی :</label>
                    <div class="col-md-6"><?php if(isset($_GET['user_id'])){?>
                      <input type="number" name="size" id="size" min="0" max="1000000" value="<?php if(isset($_GET['user_id'])){echo $user_row['size'];}?>" class="form-control" disabled required>
                      <?php } else {?>
                      <input type="number" name="size" id="size" min="0" max="1000000" value="<?php if(isset($_GET['user_id'])){echo $user_row['size'];}?>" class="form-control" disabled required>
                      <?php }?>
                    </div>
                  </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">حجم کل :</label>
                        <div class="col-md-6">
                          <input type="number" name="total" id="total" min="0" max="1024" value="<?php if(isset($_GET['user_id'])){echo $user_row['total'];}?>" class="form-control" required>
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
                              <input type="number" name="date" id="date" min="0" max="1825" value="<?php if(isset($_GET['user_id'])){echo $date_day;}?>" class="form-control" disabled required>
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
                            <label class="col-md-3 control-label">حساب مولتی یوزر :</label>
                            <?php if(isset($_GET['user_id'])){ ?>                        
                                <div class="col-md-3">
                                    <select name="op_multi" id="op_multi" class="select2">
                                        <option value="0" <?php if($user_row['multi']=='0'){?>selected<?php }?>>کاربر جدید خریداری کنید</option>
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
                            <?php }else{?>
                                <div class="col-md-6">
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
                            <?php }?>
                        </div>        
                <?php
                }
                ?>
                
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
                        <?php if(isset($_GET['user_id'])){ ?>
                        <label class="col-md-3 control-label">دستگاه های متصل :</label>
                        <div class="col-md-6">
                            <input type="text" id="connectedDevices" value="<?php if(isset($_GET['user_id'])){echo $user_row['device'];}?>" class="form-control" disabled>
                        </div>
                        <?php }?>
                    </div>
                    <div class="form-group">
                    <div class="col-md-9 col-md-offset-3">
                        <button type="submit" name="submit" class="btn btn-primary" <?php echo $disableSubmit ? 'disabled' : ''; ?>>ذخیره</button>
                    </div>
                </div>  
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
<script>
document.addEventListener("DOMContentLoaded", function() {
    updatePlanDetails(); // مقداردهی اولیه هنگام لود صفحه
});

function updatePlanDetails() {
    var selectedPlan = document.getElementById("agents").selectedOptions[0];

    if (selectedPlan) {
        document.getElementById("plan_size").value = selectedPlan.getAttribute("data-size");
        document.getElementById("plan_day").value = selectedPlan.getAttribute("data-day");
        document.getElementById("plan_price").value = selectedPlan.getAttribute("data-price");
        document.getElementById("plan_multi").value = selectedPlan.getAttribute("data-multi");
    }
}
</script>

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

<?php include('includes/footer.php');?>                  