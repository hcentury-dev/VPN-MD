<?php 

    include("includes/header.php");
    require("includes/function.php");
    require("language/language.php");
    include('includes/jdf.php'); 

	ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    
    if(isset($_SESSION['id']))
    { 
        $qry="SELECT * FROM notification WHERE id=1";
        $result=mysqli_query($mysqli,$qry);
        $row=mysqli_fetch_assoc($result);
    }

    $agents_qry = "SELECT * FROM agents ORDER BY name";
    $agents_result = mysqli_query($mysqli, $agents_qry);

    if(isset($_POST['submit']))
    {

		if (isset($_POST['agent'])) {
            $selected_agents = $_POST['agent'];
        
            if (in_array("all", $selected_agents)) {
                $all_agents_query = mysqli_query($mysqli, "SELECT agent FROM agents");
                $all_agents = [];
                while ($row = mysqli_fetch_assoc($all_agents_query)) {
                    $all_agents[] = $row['agent'];
                }
                $selected_agents = $all_agents;
            }
        
            $agent_list = implode(',', $selected_agents);
        }

        $data = array( 
            'title'  => $_POST['title'],
            'text'   => $_POST['text'],
            'link'   => $_POST['link'],
            'users'  => $_POST['users'],
            'status' => $_POST['op_status'],
            'agent'  => $agent_list,
            'date'   => ""
        );
        
        $channel_edit = Update('notification', $data, "WHERE id = 1"); 

        $_SESSION['msg'] = "11"; 
        header("Location: notification.php");
        exit;
    }
?>

<style>
.user-container {
    max-height: 200px;
    overflow-y: auto;
    padding: 10px;
    /* border: 1px solid #ddd; */
    border-radius: 10px;
    background-color: #f5f5f5;
}

.user-box {
    display: inline-block;
    background-color: #18aa4acf;
    padding: 5px 7px;
    margin: 5px;
    border-radius: 30px;
    font-size: 14px;
    color: #ffffff;
}
</style>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="page_title_block">
                <div class="col-md-5 col-xs-12">
                    <div class="page_title">اعلان برنامه</div>
                </div>
            </div>
            <div class="clearfix"></div>
            <div class="row mrg-top">
                <div class="col-md-12">
                    <div class="col-md-12 col-sm-12">
                        <?php if(isset($_SESSION['msg'])){?> 
                        <div class="alert alert-success alert-dismissible" role="alert"> 
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                            <?php echo $client_lang[$_SESSION['msg']] ; ?>
                        </div>
                        <?php unset($_SESSION['msg']);}?> 
                    </div>
                </div>
            </div>
            <div class="card-body mrg_bottom"> 
                <form action="" name="addedituser" method="post" class="form form-horizontal" enctype="multipart/form-data">
                    <input type="hidden" name="news_id" value='<?php echo $_GET['news_id']; ?>' ?>
                    <div class="section">
                        <div class="section-body">
                            <!-- انتخاب نماینده -->
                            <div class="form-group">
                                <label class="col-md-3 control-label">انتخاب نماینده:</label>
                                <div class="col-md-6">
                                    <select name="agent[]" id="agent" class="select2" multiple>
                                        <option value="all">همه</option>
                                        <?php while ($agent = mysqli_fetch_assoc($agents_result)) { ?>
                                            <option value="<?php echo $agent['agent']; ?>" 
                                            <?php if(isset($row['agent']) && in_array($agent['agent'], explode(',', $row['agent']))) echo 'selected'; ?>>
                                                <?php echo $agent['name']; ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-md-3 control-label">عنوان اعلان :</label>
                                <div class="col-md-6">
                                    <input type="text" name="title" id="title" value="<?php echo $row['title'];?>" class="form-control" required autocomplete="off">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-md-3 control-label">متن اعلان :</label>
                                <div class="col-md-6">
                                    <input type="text" name="text" id="text" value="<?php echo $row['text'];?>" class="form-control" required autocomplete="off">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-md-3 control-label">لینک اعلان :</label>
                                <div class="col-md-6">
                                    <input type="text" name="link" id="link" value="<?php echo $row['link'];?>" class="form-control" required autocomplete="off">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-md-3 control-label">کاربران مشاهده شده :</label>
                                <div class="col-md-6">
                                    <div id="usersDisplay" class="user-container">
                                        <?php 
                                            $users = str_replace('|', ' ', $row['users']);
                                            $users = preg_replace('/\s+/', ' ', trim($users));
                                            $users_array = explode(' ', $users);
                                            if (empty($row['users']) || count($users_array) == 0 || $users == '|') {
                                                echo '<p>لیست خالی است</p>';
                                            } else {
                                                $formatted_users = '';
                                                foreach ($users_array as $user) {
                                                    $formatted_users .= '<span class="user-box">' . htmlspecialchars($user) . '</span> ';
                                                }
                                                echo $formatted_users;
                                            }
                                        ?>
                                    </div>
                                    <input type="hidden" name="users" id="users" value="<?php echo $row['users']; ?>" />
                                </div>
                                <div class="col-md-3">
                                    <button type="button" id="clearUsers" class="btn btn-danger">ریست نمایش کاربران</button>
                                </div>
                            </div>

                            
                            <div class="form-group">
                                <div class="col-md-9 col-md-offset-3">
                                    <p></p>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-md-3 control-label">وضعیت اعلان</label>
                                <div class="col-md-6">
                                    <select name="op_status" id="op_status" class="select2">
                                        <option value="deactive" <?php if($row['status']=='deactive'){?>selected<?php }?>>غیرفعال</option>
                                        <option value="active" <?php if($row['status']=='active'){?>selected<?php }?>>فعال</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-md-9 col-md-offset-3">
                                    <button type="submit" name="submit" class="btn btn-primary">ذخیره</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('clearUsers').addEventListener('click', function() {
        document.getElementById('usersDisplay').innerHTML = '';
        document.getElementById('users').value = '';
    });

    document.getElementById('clearUsers').addEventListener('click', function() {
        var userId = <?php echo $row['id']; ?>;
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "clear_users.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                document.getElementById('users').value = '';
            }
        };
        xhr.send("user_id=" + userId);
    });

    document.getElementById('agent').addEventListener('change', function() {
        let select = this;
        let allSelected = false;
        for (let option of select.options) {
            if (option.value === "all" && option.selected) {
                allSelected = true;
                break;
            }
        }
        for (let option of select.options) {
            option.selected = allSelected || option.selected;
        }
    });
</script>

<?php include("includes/footer.php");?>
