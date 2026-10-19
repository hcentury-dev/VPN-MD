<?php
require("includes/connection.php");

if (isset($_GET['query'])) {
    $search = mysqli_real_escape_string($mysqli, $_GET['query']);
    
    $query = "SELECT id, username FROM users WHERE username LIKE '%$search%'";
    $result = mysqli_query($mysqli, $query);
    
    $users = array();
    while($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    
    echo json_encode($users);
}
?>
