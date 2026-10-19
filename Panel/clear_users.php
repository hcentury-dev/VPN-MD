<?php

    include("includes/connection.php");

    if (isset($_POST['user_id'])) {
        $user_id = $_POST['user_id'];

        $qry = "UPDATE notification SET users = '' WHERE id = '$user_id'";
        if (mysqli_query($mysqli, $qry)) {
            echo "success";
        } else {
            echo "error";
        }
    }