<?php 
include("includes/connection.php");
include('includes/function.php');
include('includes/jdf.php'); 

$time = date('Y-m-d H:i:s');

$username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
$password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

if (isset($_SESSION['id'])) {
    $stmt = $mysqli->prepare("SELECT * FROM admin WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['id']);
    $stmt->execute();
    $SES_row = $stmt->get_result()->fetch_assoc();
}

if (empty($username)) {
    $_SESSION['msg'] = "1";
    header("Location: index.php");
    exit;
} elseif (empty($password)) {
    $_SESSION['msg'] = "2";
    header("Location: index.php");
    exit;
}

$stmt = $mysqli->prepare("SELECT * FROM admin WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    if (password_verify($password, $row['password'])) {

        if ($row['status'] === 'active') {

            $_SESSION['id'] = $row['id'];
            $_SESSION['admin_name'] = $row['username'];
            $_SESSION['admin_pass'] = $row['password'];

            $data = array('login' => $time);
            Update('admin', $data, "WHERE id = '".$row['id']."'");

            header("Location: home.php");
            exit;
        } else {
            $_SESSION['msg'] = "28";
            header("Location: index.php");
            exit;
        }
    } else {
        $_SESSION['msg'] = "4";
        header("Location: index.php");
        exit;
    }
} else {
    $_SESSION['msg'] = "4";
    header("Location: index.php");
    exit;
}
?>
