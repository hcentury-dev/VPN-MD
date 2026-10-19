<?php 
  include("includes/header.php");
  require("includes/function.php");
  require("language/language.php");

$qry1 = "SELECT * FROM massage WHERE id='1'";
$result1 = mysqli_query($mysqli, $qry1);
$massage1 = mysqli_fetch_assoc($result1);

if (isset($_POST['submit1'])) {
    $date = $_POST['dateInput'];
    $time = $_POST['timeInput'];
    $dateTime = "$date $time";
    $dateTime = mysqli_real_escape_string($mysqli, $dateTime);
    
    $data = array(
        'title' => $_POST['massage1'],
        'users' => $_POST['users'],
        'date'     => $dateTime,
    );

    $settings_edit = Update('massage', $data, "WHERE id = '1'");

    $_SESSION['msg'] = "11";
    header("Location: massage.php");
    exit; 
}

if (isset($_POST['submit2'])) {
    $date = $_POST['dateInput'];
    $time = $_POST['timeInput'];
    $dateTime = "$date $time";
    $dateTime = mysqli_real_escape_string($mysqli, $dateTime);

    if (isset($_POST['user_ids']) && is_string($_POST['user_ids']) && !empty($_POST['user_ids'])) {
        $user_ids = explode(',', $_POST['user_ids']);
        
        if (is_array($user_ids) && !empty($user_ids)) {
            foreach ($user_ids as $user_id) {
                $user_id = intval($user_id);

                if ($user_id > 0) {
                    $data = array(
                        'massage_user' => $_POST['massage5'],
                        'massage_date' => $dateTime,
                    );

                    $settings_edit = Update('users', $data, "WHERE id = '$user_id'");

                    if (!$settings_edit) {
                        echo "Error: " . mysqli_error($mysqli);
                    }
                } else {
                    echo "خطا: ID کاربر معتبر نیست.";
                }
            }

            $_SESSION['msg'] = "11";
            header("Location: massage.php");
            exit; 
        } else {
            echo "خطا: داده‌های کاربران خالی است.";
        }
    } else {
        echo "خطا: داده‌های کاربران ارسال نشده است.";
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
            </ul>

                <div class="tab-content">
                    <!-- تب نوتیفکیشن همگانی -->
                    <div role="tabpanel" class="tab-pane active" id="app_not1">
                        <form action="" name="app_not1" method="post" class="form form-horizontal" enctype="multipart/form-data">
                            <div class="section">
                                <div class="section-body">
                                    <div class="form-group">
                                        <label class="col-md-3 control-label">متن اعلان :</label>
                                        <div class="col-md-6">
                                            <input type="text" name="massage1" id="massage1" minlength="4" maxlength="120" value="<?php echo $massage1['title']; ?>" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label">کاربران ارسال شده :</label>
                                        <div class="col-md-6">
                                            <input type="text" name="users" id="users" value="<?php echo $massage1['users']; ?>" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label">زمان اجرا :</label>
                                        <div class="col-md-6">
                                            <input type="text" value="<?php echo $massage1['date']; ?>" class="form-control" disabled="disabled">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label">انتخاب تاریخ و ساعت :</label>
                                        <div class="col-md-6">
                                            <div class="date-time-container">
                                                <input type="date" id="dateInput" name="dateInput" >
                                                <input type="time" id="timeInput" name="timeInput" >
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-md-9 col-md-offset-3">
                                            <button type="submit" name="submit1" class="btn btn-primary">ذخیره</button>
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
                                        <label class="col-md-3 control-label">متن اعلان :</label>
                                        <div class="col-md-6">
                                            <input type="text" name="massage5" id="massage5" minlength="2" maxlength="120" value="" class="form-control" required>
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
                                        <label class="col-md-3 control-label">انتخاب تاریخ و ساعت :</label>
                                        <div class="col-md-6">
                                            <div class="date-time-container">
                                                <input type="date" id="dateInput" name="dateInput" required>
                                                <input type="time" id="timeInput" name="timeInput" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-md-9 col-md-offset-3">
                                            <input type="hidden" name="user_ids" id="userIds">
                                            <button type="submit" name="submit2" class="btn btn-primary">ذخیره</button>
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