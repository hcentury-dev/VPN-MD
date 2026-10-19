<?php 
	include("includes/connection.php");
 	include("includes/function.php"); 
 	include("language/app_language.php"); 	

	$protocol = strtolower( substr( $_SERVER[ 'SERVER_PROTOCOL' ], 0, 5 ) ) == 'https' ? 'https' : 'http'; 

	$file_path = $protocol.'://'.$_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']).'/';
	 
	function get_thumb($filename,$thumb_size){	
		$protocol = strtolower( substr( $_SERVER[ 'SERVER_PROTOCOL' ], 0, 5 ) ) == 'https' ? 'https' : 'http'; 

		$file_path = $protocol.'://'.$_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']).'/';

		return $thumb_path=$file_path.'thumb.php?src='.$filename.'&size='.$thumb_size;
	}

	function get_resolution($filename){	
		$data = getimagesize($filename);
		$width = $data[0];
		$height = $data[1];

		return $width.'X'.$height;
	}

	function get_size($filename){	 
		$size = filesize($filename);
	    $units = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
	    $power = $size > 0 ? floor(log($size, 1024)) : 0;
	    return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
     
	}

	function get_total_wallpaper($cat_id,$type=null){	
 		global $mysqli;

 		if($type==null || $type==''){
 			$qry_wallpaper="SELECT COUNT(*) as num FROM tbl_wallpaper WHERE cat_id='".$cat_id."'";	
 		}else{
 			$type=trim($type);
 			$qry_wallpaper="SELECT COUNT(*) as num FROM tbl_wallpaper WHERE cat_id='".$cat_id."' AND `wallpaper_type`='$type'";
 		}
		 
		$total_wallpaper = mysqli_fetch_array(mysqli_query($mysqli,$qry_wallpaper));
		$total_wallpaper = $total_wallpaper['num'];
		 
		return $total_wallpaper;

	}

 	$get_method = checkSignSalt($_POST['data']);
 	
 	if ($get_method['method_name']=="get_server") {

    $sql = "SELECT * FROM `users` WHERE username = '$username' and password = '$password'";
    $result = mysqli_query($db, $sql);
    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);

		while($data = mysqli_fetch_assoc($result))
		{	
 			$row['name'] = $data['name']; 
 			$row['src'] = $data['src'];
            $row['locations'] = $data['locations'];
            $row['permium'] = $data['permium'];
            $row['status'] = $data['status'];
            $row['date'] = $data['date'];
			array_push($jsonObj,$row);
		}

		echo json_encode($jsonObj);
            die;
		
	}
	
	 
?>