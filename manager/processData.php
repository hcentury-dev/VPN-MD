<?php 
	require("includes/connection.php");
	require("includes/function.php");
	require("language/language.php");

	$response=array();

	switch ($_POST['action']) {
		case 'delete_wallpaper':
			$id=$_POST['wallpaper_id'];
			$img_res=mysqli_query($mysqli,'SELECT * FROM tbl_wallpaper WHERE id=$id');
	      	$img_res_row=mysqli_fetch_assoc($img_res);

	      	if($img_res_row['image']!="")
	      	{
	        	unlink('categories/'.$img_res_row['cat_id'].'/'.$img_res_row['image']);
	        	unlink('categories/'.$img_res_row['cat_id'].'/thumbs/'.$img_res_row['image']);
	      	}
	      	Delete('tbl_wallpaper','id='.$id.'');
	      	$_SESSION['msg2']="12";
	      	$response['status']=1;
	      	echo json_encode($response);
			break;

		case 'featured_wallpaper':
			$id=$_POST['wallpaper_id'];
			$for_action=$_POST['for_action'];

			if($for_action=='active'){
				$data = array('featured'  =>  '1');
			    $edit_status=Update('tbl_wallpaper', $data, "WHERE id = '$id'");
			    $_SESSION['msg2']="13";
			}else{
				$data = array('featured'  =>  '0');
			    $edit_status=Update('tbl_wallpaper', $data, "WHERE id = '$id'");
			    $_SESSION['msg2']="14";
			}
			
	      	$response['status']=1;
	      	echo json_encode($response);
			break;
		
		default:
			# code...
			break;
	}


	// if(isset($_POST['action']) && $_POST['action']=='delete_wallpaper'){

	// 	$id=$_POST['wallpaper_id'];
	// 	$img_res=mysqli_query($mysqli,'SELECT * FROM tbl_wallpaper WHERE id=$id');
 //      	$img_res_row=mysqli_fetch_assoc($img_res);

 //      	if($img_res_row['image']!="")
 //      	{
 //        	unlink('categories/'.$img_res_row['cat_id'].'/'.$img_res_row['image']);
 //        	unlink('categories/'.$img_res_row['cat_id'].'/thumbs/'.$img_res_row['image']);
 //      	}
 //      	Delete('tbl_wallpaper','id='.$id.'');
 //      	$_SESSION['msg2']="12";
 //      	exit;
	// }

?>