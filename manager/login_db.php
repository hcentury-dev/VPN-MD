<?php 
include("includes/connection.php");
include('includes/function.php');
include('includes/jdf.php'); 
date_default_timezone_set('Asia/Tehran');

    $Time = date('Y-m-d H:i:s');
    
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

    if (isset($_SESSION['id'])) {
        $SES_qry = "SELECT * FROM manager WHERE id='".$_SESSION['id']."'";
        $SES_result = mysqli_query($mysqli, $SES_qry);
        $SES_row = mysqli_fetch_assoc($SES_result);
    }
    
    if (empty($username)) {
        $_SESSION['msg'] = "1";
        header("Location: index.php");
        exit;
    } elseif (empty($password)) {
        $_SESSION['msg'] = "2";
        header("Location: index.php");
        exit;
    } else {
        
        $stmt = $mysqli->prepare("SELECT * FROM manager WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
        
            if (password_verify($password, $row['password'])) {
                    
                if ($row['status'] == 'active') {
                    
                    $_SESSION['id'] = $row['id'];
                    $_SESSION['name'] = $row['username'];
                    
                    if(!isset($_COOKIE['manager_id'])) {
                        $manager_id = uniqid('manager_', true);
                        setcookie('manager_id', $manager_id, time() + (86400 * 30), "/");
                    } else {
                        $manager_id = $_COOKIE['manager_id'];
                    }
        
                    if (strpos($row['devices'], $manager_id) === false) {
                        if ($row['login'] < $row['device']) {
                            $data = array(
                                'dlogin'   => $Time,
                                'login'    => $row['login'] + 1,
                                'devices'  => $row['devices'] . $manager_id . '|',
                            );
        
                            Update('manager', $data, "WHERE id = '".$row['id']."'");
                        } else {
                            $_SESSION['msg2'] = "102"; 
                            // session_start();
                            unset($_SESSION["name"]);
                            // session_destroy();
                            header("Location:index.php");
                            exit;
                        }
                    } else {
                        $data = array(
                            'dlogin'   => $Time,
                        );
                        Update('manager', $data, "WHERE id = '".$row['id']."'");
                    }
                    
                    header("Location:home.php");
                    exit;
                    
                } elseif ($row['type'] == '0' && $row['status'] == 'expire') {
                    $_SESSION['msg2'] = "100"; 
                    header("Location:index.php");
                    exit;
                } else {
                    $_SESSION['msg2'] = "28"; 
                    header("Location:index.php");
                    exit;
                }
                    
            } else {
                $_SESSION['msg2'] = "4"; 
                header("Location:index.php");
                exit;
            }
        } else {
            $_SESSION['msg2'] = "4"; 
            header("Location:index.php");
            exit;			 
        }
    }
?>
