<?php error_reporting(0);

function toPersianNumbers($number) {
    $persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $number = preg_replace_callback('/\d/', function($matches) use ($persianNumbers) {
        return $persianNumbers[$matches[0]];
    }, $number);
    return $number;
}
function uploadFile($file, $targetDir) {
    $targetFile = $targetDir . basename($file["name"]);
    
    // Check if file already exists
    if (file_exists($targetFile)) {
        return false; // or handle duplicate
    }
    
    // Try to upload file
    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        return $targetFile;
    } else {
        return false;
    }
}
function futureDate($days) {
    $today = new DateTime();
    $today->add(new DateInterval("P{$days}D"));
    return $today->format('Y-m-d H:i:s');
}

function days_left($expiry_date){
    $today = time();
    $diff = strtotime($expiry_date) - $today;
    if ($diff < 0) {
        return 0;
    } else {
        return ceil($diff / (60 * 60 * 24));
    }
}
                        
function generateRandomString($length = 6) {
          $characters = '0123456789';
          $charactersLength = strlen($characters);
          $randomString = '';
          for ($i = 0; $i < $length; $i++) {
              $randomString .= $characters[random_int(0, $charactersLength - 1)];
          }
          return $randomString;
}

// function generateUniqueUsername($prefix, $length, $mysqli) {
//     do {
//         $random_username = $prefix . generateRandomString($length);
//         $query = "SELECT COUNT(*) as count FROM users WHERE username = '$random_username'";
//         $result = mysqli_query($mysqli, $query);
//         $row = mysqli_fetch_assoc($result);
//     } while ($row['count'] > 0);
    
//     return $random_username;
// }
function generateUniqueUsername($prefix, $length, $mysqli) {

    for ($attempt = 0; $attempt < 100; $attempt++) {

        $random_username = $prefix . generateRandomString($length);

        $safe_username = mysqli_real_escape_string($mysqli, $random_username);

        $query = "SELECT id FROM users 
                  WHERE username = '$safe_username' 
                  LIMIT 1";

        $result = mysqli_query($mysqli, $query);

        if (!$result) {
            error_log("Username check error: " . mysqli_error($mysqli));
            return false;
        }

        if (mysqli_num_rows($result) === 0) {
            return $random_username;
        }
    }

    error_log("Could not generate unique username after 100 attempts");
    return false;
}
#Admin Login
function adminUser($username, $password){
    
    global $mysqli;

    $sql = "SELECT id,username FROM admin where username = '".$username."' and password = '".md5($password)."'";       
    $result = mysqli_query($mysqli,$sql);
    $num_rows = mysqli_num_rows($result);
     
    if ($num_rows > 0){
        while ($row = mysqli_fetch_array($result)){
            echo $_SESSION['ADMIN_ID'] = $row['id'];
                        echo $_SESSION['ADMIN_USERNAME'] = $row['username'];
                                      
        return true; 
        }
    }
    
}

# Insert Data 
function Insert($table, $data){
    global $mysqli;

    $fields = array_keys($data);  
    $values = array_map(array($mysqli, 'real_escape_string'), array_values($data));
    
    $query = "INSERT INTO $table(" . implode(",", $fields) . ") VALUES ('" . implode("','", $values) . "')";

    if (mysqli_query($mysqli, $query)) {
        return mysqli_insert_id($mysqli); // ✅ برگشت id درج‌شده
    } else {
        error_log("Insert error in table $table: " . mysqli_error($mysqli));
        return 0; // یا false برگردونی هم خوبه
    }
}


// Update Data, Where clause is left optional
function Update($table_name, $form_data, $where_clause='')
{   
    global $mysqli;
    // check for optional where clause
    $whereSQL = '';
    if(!empty($where_clause))
    {
        // check to see if the 'where' keyword exists
        if(substr(strtoupper(trim($where_clause)), 0, 5) != 'WHERE')
        {
            // not found, add key word
            $whereSQL = " WHERE ".$where_clause;
        } else
        {
            $whereSQL = " ".trim($where_clause);
        }
    }
    // start the actual SQL statement
    $sql = "UPDATE ".$table_name." SET ";

    // loop and build the column /
    $sets = array();
    foreach($form_data as $column => $value)
    {
         $sets[] = "`".$column."` = '".$value."'";
    }
    $sql .= implode(', ', $sets);

    // append the where statement
    $sql .= $whereSQL;
         
    // run and return the query result
    return mysqli_query($mysqli,$sql);
}

//Delete Data, the where clause is left optional incase the user wants to delete every row!
function Delete($table_name, $where_clause='')
{   
    global $mysqli;
    // check for optional where clause
    $whereSQL = '';
    if(!empty($where_clause))
    {
        // check to see if the 'where' keyword exists
        if(substr(strtoupper(trim($where_clause)), 0, 5) != 'WHERE')
        {
            // not found, add keyword
            $whereSQL = " WHERE ".$where_clause;
        } else
        {
            $whereSQL = " ".trim($where_clause);
        }
    }
    // build the query
    $sql = "DELETE FROM ".$table_name.$whereSQL;
     
    // run and return the query result resource
    return mysqli_query($mysqli,$sql);
}

//Delete Data, the where clause is left optional incase the user wants to delete every row!
function LastID($table_name)
{   
    global $mysqli;
    return mysqli_insert_id($mysqli);
}

 ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
//GCM function
function Send_GCM_msg($registration_id,$data)
{
    $data1['data']=$data;
 
    $url = 'https://fcm.googleapis.com/fcm/send';
  
    $registatoin_ids = array($registration_id);
     // $message = array($data);
   
         $fields = array(
             'registration_ids' => $registatoin_ids,
             'data' => $data1,
         );
  
         $headers = array(
             'Authorization: key='.APP_GCM_KEY.'',
             'Content-Type: application/json'
         );
         // Open connection
         $ch = curl_init();
  
         // Set the url, number of POST vars, POST data
         curl_setopt($ch, CURLOPT_URL, $url);
  
         curl_setopt($ch, CURLOPT_POST, true);
         curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  
         // Disabling SSL Certificate support temporarly
         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  
         curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
  
         // Execute post
         $result = curl_exec($ch);
         if ($result === FALSE) {
             die('Curl failed: ' . curl_error($ch));
         }
  
         // Close connection
         curl_close($ch);
       //echo $result;exit;
}


//Image compress
function compress_image($source_url, $destination_url, $quality) 
{

    $info = getimagesize($source_url);

        if ($info['mime'] == 'image/jpeg')
              $image = imagecreatefromjpeg($source_url);

        elseif ($info['mime'] == 'image/gif')
              $image = imagecreatefromgif($source_url);

        elseif ($info['mime'] == 'image/png')
              $image = imagecreatefrompng($source_url);

        imagejpeg($image, $destination_url, $quality);
        return $destination_url;
}

//Create Thumb Image
function create_thumb_image($target_folder ='',$thumb_folder = '', $thumb_width = '',$thumb_height = '')
 {  
     //folder path setup
         $target_path = $target_folder;
         $thumb_path = $thumb_folder;  
          

         $thumbnail = $thumb_path;
         $upload_image = $target_path;

            list($width,$height) = getimagesize($upload_image);
            $thumb_create = imagecreatetruecolor($thumb_width,$thumb_height);
            switch($file_ext){
                case 'jpg':
                    $source = imagecreatefromjpeg($upload_image);
                    break;
                case 'jpeg':
                    $source = imagecreatefromjpeg($upload_image);
                    break;
                case 'png':
                    $source = imagecreatefrompng($upload_image);
                    break;
                case 'gif':
                    $source = imagecreatefromgif($upload_image);
                     break;
                default:
                    $source = imagecreatefromjpeg($upload_image);
            }
       imagecopyresized($thumb_create, $source, 0, 0, 0, 0, $thumb_width, $thumb_height, $width,$height);
            switch($file_ext){
                case 'jpg' || 'jpeg':
                    imagejpeg($thumb_create,$thumbnail,80);
                    break;
                case 'png':
                    imagepng($thumb_create,$thumbnail,80);
                    break;
                case 'gif':
                    imagegif($thumb_create,$thumbnail,80);
                     break;
                default:
                    imagejpeg($thumb_create,$thumbnail,80);
            }
   }

function checkSignSalt($data_info){

        $key="MD_wallpaper";

        $data_json = $data_info;

        $data_arr = json_decode(urldecode(base64_decode($data_json)),true);

   

            if($data_arr['sign'] == '' && $data_arr['salt'] == '' ){
            
                $set['HD_WALLPAPER'][] = array("success" => -1, "MSG" => "شناسه نامعتبر است.");
                header( 'Content-Type: application/json; charset=utf-8' );
                echo $val= str_replace('\\/', '/', json_encode($set,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                exit();

            }else{
                
                $data_arr['salt'];    
                
                $md5_salt=md5($key.$data_arr['salt']);

                if($data_arr['sign']!=$md5_salt){
                    
                    $set['HD_WALLPAPER'][] = array("success" => -1, "MSG" => "شناسه نامعتبر است.");   
                    header( 'Content-Type: application/json; charset=utf-8' );
                    echo $val= str_replace('\\/', '/', json_encode($set,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                    exit();
                }
            }
      
        return $data_arr;
    }

function verify_envato_purchase_code($product_code)
{ 
  
$url = "https://api.envato.com/v3/market/author/sale?code=".$product_code;
$curl = curl_init($url);


$personal_token = "M8tF6z8lzZBBkmZt4xm3dU4lw7Rlbrwp";
$header = array();
$header[] = 'Authorization: Bearer '.$personal_token;
$header[] = 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.11; rv:41.0) Gecko/20100101 Firefox/41.0';
$header[] = 'timeout: 20';
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl, CURLOPT_HTTPHEADER,$header);

$envatoRes = curl_exec($curl);
curl_close($curl);
$envatoRes = json_decode($envatoRes);
 
 print_r($envatoRes);

 return $envatoRes;   


//echo $envatoRes->buyer;

/* if (isset($envatoRes->item->name)) {   
    $data = " - VERIFIED ({$envatoRes->item->name}) (Supported: {$result})";
} else {  
    $data= " FAILED";
} 

echo $data;
exit;*/
}

function verify_data_on_server($product_id,$buyer_name,$purchase_code,$purchased_status,$admin_url,$package_name,$envato_buyer_email,$ios_bundle_identifier)
{  

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,"http://www.secureapp.viaviweb.in/verified_user.php");
    curl_setopt($ch, CURLOPT_POST, true);
    //curl_setopt($ch, CURLOPT_POSTFIELDS,"envato_product_id=1&envato_buyer_name=2&envato_purchase_code=2&envato_purchased_status=3&buyer_admin_url=4"); 
    curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query(array('envato_product_id' => $product_id,'envato_buyer_name' => $buyer_name,'envato_purchase_code' => $purchase_code,'envato_purchased_status' => $purchased_status,'buyer_admin_url' => $admin_url,'package_name' => $package_name,'envato_buyer_email' => $envato_buyer_email,'ios_bundle_identifier' => $ios_bundle_identifier)));

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $server_output = curl_exec($ch);
    curl_close ($ch);

     
   if ($server_output == "success") { echo "done1"; } else { echo "fail!";}     
}

