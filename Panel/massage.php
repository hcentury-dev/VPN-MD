<?php 
    
    include("includes/header.php");
    require("includes/function.php");
    require("language/language.php");

// 	ini_set('display_errors', 1);
//     ini_set('display_startup_errors', 1);
//     error_reporting(E_ALL);

    define('GOOGLE_API_KEY_JSON', '../account.json');
    
    function getAccessToken() {
        $key = json_decode(file_get_contents(GOOGLE_API_KEY_JSON), true);
        $clientEmail = $key['client_email'];
        $privateKey = $key['private_key'];
    
        $url = 'https://oauth2.googleapis.com/token';
        $scope = 'https://www.googleapis.com/auth/firebase.messaging';
    
        $jwtHeader = base64_encode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT'
        ]));
    
        $jwtPayload = base64_encode(json_encode([
            'iss' => $clientEmail,
            'scope' => $scope,
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => time(),
            'exp' => time() + 3600
        ]));
    
        $signature = '';
        openssl_sign($jwtHeader . '.' . $jwtPayload, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $jwtSignature = base64_encode($signature);
    
        $jwt = $jwtHeader . '.' . $jwtPayload . '.' . $jwtSignature;
    
        $response = file_get_contents($url, false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query([
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt
                ])
            ]
        ]));
    
        $data = json_decode($response, true);
        return $data['access_token'];
    }
    
    function sendFCMNotification($to, $title, $body) {
        $accessToken = getAccessToken();
    
        $url = 'https://fcm.googleapis.com/v1/projects/vnapps-0012/messages:send';
    
        $fields = [
            'message' => [
                'token' => $to,
                'notification' => [
                    'title' => $title,
                    'body' => $body
                ]
            ]
        ];
    
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ];
    
        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => implode("\r\n", $headers),
                'content' => json_encode($fields)
            ]
        ];
    
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
    
        return json_decode($response, true);
    }
    
    $qry1 = "SELECT * FROM massage WHERE id='1'";
    $result1 = mysqli_query($mysqli, $qry1);
    $massage1 = mysqli_fetch_assoc($result1);
    
    $qry2 = "SELECT token FROM devices";
    $result2 = mysqli_query($mysqli, $qry2);
    $tokens = [];
    while ($row = mysqli_fetch_assoc($result2)) {
        $tokens[] = $row['token'];
    }

    $qryManagers = "SELECT * FROM agents";
    $resultManagers = mysqli_query($mysqli, $qryManagers);

    if (isset($_POST['submit1'])) {
        $dateTime = date('Y-m-d H:i:s');
        
        $data = array(
            'title' => $_POST['massage1'],
            'text'  => $_POST['massage2'],
            'date'  => $dateTime,
        );

        foreach ($tokens as $deviceToken) {
            $response = sendFCMNotification($deviceToken, $_POST['massage1'], $_POST['massage2']);
            if (isset($response['error'])) {
                echo "Error sending notification to token: " . $deviceToken . " - " . $response['error'] . "<br>";
            }
        }

        $settings_edit = Update('massage', $data, "WHERE id = '1'");
    
        $_SESSION['msg'] = "3";
        header("Location: massage.php");
        exit; 
    }
    
    if (isset($_POST['submit2'])) {
        
        if (isset($_POST['user_ids']) && is_string($_POST['user_ids']) && !empty($_POST['user_ids'])) {
            $user_ids = explode(',', $_POST['user_ids']);
            
            if (is_array($user_ids) && !empty($user_ids)) {
                foreach ($user_ids as $user_id) {
                    $user_id = intval($user_id);
                    
        $qry1 = "SELECT username FROM users WHERE id = '$user_id'";
        $result1 = mysqli_query($mysqli, $qry1);
        
        if ($result1 && mysqli_num_rows($result1) > 0) {
            $user = mysqli_fetch_assoc($result1);
            $username = $user['username'];

            echo "یوزرنیم مربوط به ID کاربر $user_id: " . htmlspecialchars($username);
            
            $qry2 = "SELECT token FROM devices WHERE username = '$username'";
            $result2 = mysqli_query($mysqli, $qry2);
            
            if ($result2 && mysqli_num_rows($result2) > 0) {
                $tokens = [];
                while ($row = mysqli_fetch_assoc($result2)) {
                    $tokens[] = $row['token'];
                }

                foreach ($tokens as $deviceToken) {
                    $response = sendFCMNotification($deviceToken, $_POST['massage5'], $_POST['massage6']);
                    if (isset($response['error'])) {
                        echo "Error sending notification to token: " . $deviceToken . " - " . $response['error'] . "<br>";
                    }
                }
                
            }}}
                $_SESSION['msg'] = "3";
                header("Location: massage.php");
                exit; 
            } else {
                echo "خطا: داده‌های کاربران خالی است.";
            }
        } else {
            echo "خطا: داده‌های کاربران ارسال نشده است.";
        }
    }
    
    if (isset($_POST['submit3'])) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    $selectedManagerName = mysqli_real_escape_string($mysqli, $_POST['manager']);
    $dateTime = date('Y-m-d H:i:s');

    $data = array(
        'titleB' => $_POST['massage7'],
        'textB'  => $_POST['massage8'],
        'dateB'  => $dateTime,
    );

    // 1️⃣ ابتدا agent واقعی نماینده را از جدول agents بگیریم
    $qryAgent = "SELECT agent FROM agents WHERE name = '$selectedManagerName' LIMIT 1";
    $resultAgent = mysqli_query($mysqli, $qryAgent);

    if ($resultAgent && mysqli_num_rows($resultAgent) > 0) {
        $agentCode = mysqli_fetch_assoc($resultAgent)['agent'];

        // 2️⃣ دریافت توکن کاربران زیر نظر این agent
        $qryUsers = "
            SELECT d.token
            FROM devices d
            INNER JOIN users u ON d.username = u.username
            WHERE u.agent = '$agentCode'
        ";
        $resultUsers = mysqli_query($mysqli, $qryUsers);

        $tokens = [];
        if ($resultUsers && mysqli_num_rows($resultUsers) > 0) {
            while ($row = mysqli_fetch_assoc($resultUsers)) {
                $tokens[] = $row['token'];
            }
        }

        // 3️⃣ ارسال نوتیفیکیشن فقط به کاربران زیر نظر نماینده
        foreach ($tokens as $deviceToken) {
            $response = sendFCMNotification($deviceToken, $_POST['massage7'], $_POST['massage8']);
            error_log("FCM Response for token $deviceToken: " . print_r($response, true));
        }

        // ذخیره در جدول massage
        $settings_edit = Update('massage', $data, "WHERE id = '1'");

        $_SESSION['msg'] = "3";
        header("Location: massage.php");
        exit;
    } else {
        $_SESSION['msg'] = "نماینده یافت نشد!";
        header("Location: massage.php");
        exit;
    }
}

?>


<style type="text/css">
  .switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 26px;
  }

  .switch input {
    opacity: 0;
    width: 0;
    height: 0;
  }

  .slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    -webkit-transition: .4s;
    transition: .4s;
  }

  .slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 5px;
    bottom: 3px;
    background-color: white;
    -webkit-transition: .4s;
    transition: .4s;
  }

  input:checked + .slider {
    background-color: #e91e63;
  }

  input:focus + .slider {
    box-shadow: 0 0 1px #e91e63;
  }

  input:checked + .slider:before {
    -webkit-transform: translateX(20px);
    -ms-transform: translateX(20px);
    transform: translateX(20px);
  }

  .slider.round {
    border-radius: 34px;
  }

  .slider.round:before {
    border-radius: 50%;
  }
    form {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        label {
            font-weight: bold;
        }
        input[type="date"],
        input[type="time"] {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 100%;
        }
        .date-time-container {
        display: flex;
        gap: 10px;
    }
.form-group {
    margin-bottom: 20px;
}
  .selected-users {
      list-style-type: none;
      padding: 0;
  }

  .selected-users li {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 5px;
      background-color: #f5f5f5;
      margin-bottom: 5px;
      border-radius: 4px;
  }

  .selected-users li .remove-user {
      color: red;
      cursor: pointer;
  }
  .user-search-result li {
      padding: 5px;
      cursor: pointer;
  }

  .user-search-result li:hover {
      background-color: #f0f0f0;
  }
  .selected-users {
    list-style-type: none;
    padding: 0;
    margin: 0;
}
.selected-users li {
    display: flex;
    justify-content: flex-end;
    padding: 5px 10px;
    background-color: #f0f0f0;
    color: #000;
    margin-bottom: 5px;
    border-radius: 4px;
    flex-wrap: nowrap;
    flex-direction: row-reverse;
    align-content: space-around;
    align-items: baseline;
    max-width: 100%;
    width: auto;
}

.selected-users li .remove-user {
    color: red;
    cursor: pointer;
    margin-left: 10px;
    font-size: 16px;
    font-weight: bold;
}

/* استایل نتایج جستجو */
.user-search-result {
    background-color: #ffffff;
    position: absolute;
    width: 100%;
    z-index: 1000;
    max-height: 200px;
    overflow-y: auto;
}
.user-search-result li {
    padding: 10px;
    cursor: pointer;
    color: #000;
    background-color: #fff;
    border-bottom: 1px solid #ccc;
}
.user-search-result li:hover {
    background-color: #f0f0f0;
}
</style>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="page_title_block">
                <div class="col-md-5 col-xs-12">
                    <div class="page_title">نوتیفکیشن ها</div>
                </div>
            </div>
            <div class="clearfix"></div>

            <?php if (isset($_SESSION['msg'])) { ?>
                <div class="row mrg-top">
                    <div class="col-md-12">
                        <div class="col-md-12 col-sm-12">
                            <div class="alert alert-success alert-dismissible" role="alert">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">×</span>
                                </button>
                                <?php echo $client_lang[$_SESSION['msg']]; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php unset($_SESSION['msg']); } ?>
            <ul class="nav nav-tabs" role="tablist">
                <li role="presentation" class="active"><a href="#app_not1" aria-controls="app_not1" role="tab" data-toggle="tab">نوتیفکیشن همگانی</a></li>
                <li role="presentation"><a href="#app_not2" aria-controls="app_not2" role="tab" data-toggle="tab">نوتیفکیشن کاربر</a></li>
                <li role="presentation"><a href="#app_not3" aria-controls="app_not3" role="tab" data-toggle="tab">نوتیفکیشن همکاران</a></li>
            </ul>

                <div class="tab-content">
                    <!-- تب نوتیفکیشن همگانی -->
                    <div role="tabpanel" class="tab-pane active" id="app_not1">
                        <form action="" name="app_not1" method="post" class="form form-horizontal" enctype="multipart/form-data">
                            <div class="section">
                                <div class="section-body">
                                    <div class="form-group">
                                        <label class="col-md-3 control-label">متن تایتل :</label>
                                        <div class="col-md-6">
                                            <input type="text" name="massage1" id="massage1" minlength="1" maxlength="120" value="<?php echo $massage1['title']; ?>" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label">متن اصلی :</label>
                                        <div class="col-md-6">
                                            <input type="text" name="massage2" id="massage2" minlength="1" maxlength="120" value="<?php echo $massage1['text']; ?>" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label">زمان آخرین اعلان ارسال شده :</label>
                                        <div class="col-md-6">
                                            <input type="text" value="<?php echo $massage1['date']; ?>" class="form-control" disabled="disabled">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-md-9 col-md-offset-3">
                                            <button type="submit" name="submit1" class="btn btn-primary">ارسال</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- تب نوتیفکیشن کاربر -->
                    <div role="tabpanel" class="tab-pane" id="app_not2">
                        <form action="" name="app_not2" method="post" class="form form-horizontal" enctype="multipart/form-data">
                            <div class="section">
                                <div class="section-body">
                                    <div class="form-group">
                                        <label class="col-md-3 control-label">متن تایتل :</label>
                                        <div class="col-md-6">
                                            <input type="text" name="massage5" id="massage5" minlength="1" maxlength="120" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label">متن اصلی :</label>
                                        <div class="col-md-6">
                                            <input type="text" name="massage6" id="massage6" minlength="1" maxlength="120" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label">انتخاب کاربران :</label>
                                        <div class="col-md-6">
                                            <input type="text" id="userSearch" placeholder="جستجوی کاربران..." class="form-control">
                                            <ul class="user-search-result" id="userSearchResult"></ul>
                                            <ul class="selected-users" id="selectedUsers"></ul>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-md-9 col-md-offset-3">
                                            <input type="hidden" name="user_ids" id="userIds">
                                            <button type="submit" name="submit2" class="btn btn-primary">ارسال</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- تب نوتیفکیشن همکاران -->
                    <div role="tabpanel" class="tab-pane" id="app_not3">
                    <form action="" name="app_not3" method="post" class="form form-horizontal" enctype="multipart/form-data">
                        <div class="section">
                            <div class="section-body">
                                <div class="form-group">
                                    <label class="col-md-3 control-label">انتخاب همکار:</label>
                                    <div class="col-md-6">
                                        <select name="manager" class="form-control" required>
                                            <option value="">انتخاب کنید</option>
                                            <?php while ($row = mysqli_fetch_assoc($resultManagers)) { ?>
                                                <option value="<?php echo $row['name']; ?>"><?php echo $row['name']; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label">متن تایتل :</label>
                                    <div class="col-md-6">
                                        <input type="text" name="massage7" id="massage7" minlength="1" maxlength="120" value="<?php echo $massage1['titleB']; ?>" class="form-control" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label">متن اصلی :</label>
                                    <div class="col-md-6">
                                        <input type="text" name="massage8" id="massage8" minlength="1" maxlength="120" value="<?php echo $massage1['textB']; ?>" class="form-control" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label">زمان آخرین اعلان ارسال شده :</label>
                                    <div class="col-md-6">
                                        <input type="text" value="<?php echo $massage1['dateB']; ?>" class="form-control" disabled="disabled">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-md-9 col-md-offset-3">
                                        <button type="submit" name="submit3" class="btn btn-primary">ارسال</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                </div>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userSearchInput = document.getElementById('userSearch');
    const userSearchResult = document.getElementById('userSearchResult');
    const selectedUsers = document.getElementById('selectedUsers');
    const userIdsInput = document.getElementById('userIds');
    let selectedUserIds = [];

    userSearchInput.addEventListener('input', function() {
        const query = this.value;

        if (query.length > 1) {
            fetch(`search_users.php?query=${query}`)
                .then(response => response.json())
                .then(users => {
                    userSearchResult.innerHTML = '';
                    users.forEach(user => {
                        const li = document.createElement('li');
                        li.textContent = user.username;
                        li.dataset.id = user.id;
                        userSearchResult.appendChild(li);
                    });
                });
        } else {
            userSearchResult.innerHTML = '';
        }
    });

    userSearchResult.addEventListener('click', function(e) {
        if (e.target.tagName === 'LI') {
            const userId = e.target.dataset.id;
            const userName = e.target.textContent;

            if (!selectedUserIds.includes(userId)) {
                selectedUserIds.push(userId);

                const li = document.createElement('li');
                li.innerHTML = `${userName} <span class="remove-user">حذف</span>`;
                li.dataset.id = userId;
                selectedUsers.appendChild(li);
            }

            userIdsInput.value = selectedUserIds.join(',');
            userSearchResult.innerHTML = '';
            userSearchInput.value = '';
        }
    });

    selectedUsers.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-user')) {
            const li = e.target.parentElement;
            const userId = li.dataset.id;
            selectedUserIds = selectedUserIds.filter(id => id !== userId);
            li.remove();
            userIdsInput.value = selectedUserIds.join(',');
        }
    });
});
</script>

<?php include("includes/footer.php"); ?>