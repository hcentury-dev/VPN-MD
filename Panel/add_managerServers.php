<?php 
include('includes/header.php');
include('includes/function.php');
include('language/language.php'); 
include('includes/jdf.php'); 

$Time = date('Y-m-d H:i:s');
// $agent_qry = "SELECT * FROM agents";
// $agent_result = mysqli_query($mysqli, $agent_qry);
$agent_qry = "
    SELECT agents.*
    FROM agents
    INNER JOIN manager ON agents.name = manager.username
";
$agent_result = mysqli_query($mysqli, $agent_qry);
$SRC = $_POST['src'].'.png';
  
// لیست کشورها
$countries = [
    "ge" => ["آلمان", "Germany"],
    "usa" => ["آمریکا", "USA"],
    "ir" => ["ایران", "Iran"],
    "em" => ["امارات", "Emirates"],
    "en" => ["انگلیس", "England"],
    "tr" => ["ترکیه", "Turkey"],
    "sw" => ["سوئد", "Sweden"],
    "fr" => ["فرانسه", "France"],
    "fl" => ["فلاند", "Finland"],
    "ne" => ["هلند", "Netherlands"],
    "ca" => ["کانادا", "Canada"],
    "mk" => ["مکزیک", "Mexico"],
    "sp" => ["اسپانیا", "Spain"],
    "aus" => ["استرالیا", "Australia"],
    "in" => ["هند", "India"],
    "sing" => ["سنگاپور", "Singapore"],
    "br" => ["برزیل", "Brazil"],
    "ru" => ["روسیه", "Russia"],
    "er" => ["ایرلند", "Ireland"],
    "la" => ["لهستان", "Poland"],
    "et" => ["ایتالیا", "Italy"],
    "ok" => ["اوکراین", "Ukraine"]
];

if(isset($_POST['src']) && isset($countries[$_POST['src']])) {
    $loc = $countries[$_POST['src']][0];
    $loc2 = $countries[$_POST['src']][1];
}

// دریافت اطلاعات سرور در صورت ویرایش
if(isset($_GET['server_id'])) {
    $user_qry = "SELECT * FROM configs WHERE id = '".$_GET['server_id']."'";
    $user_result = mysqli_query($mysqli, $user_qry);
    $user_row = mysqli_fetch_assoc($user_result);
    
    // اگر در حال ویرایش هستیم و src وجود دارد، مقادیر loc و loc2 را تنظیم می‌کنیم
    if(isset($user_row['src'])) {
        $src_code = str_replace('.png', '', $user_row['src']);
        if(isset($countries[$src_code])) {
            $loc = $countries[$src_code][0];
            $loc2 = $countries[$src_code][1];
        }
    }
}

// پردازش فرم ارسالی
if(isset($_POST['submit'])) {
    $is_json = ($_POST['config_type'] == 'json') ? 'true' : 'false';
    $config_value = ($is_json == 'true') ? $_POST['json_config'] : $_POST['link'];
    
    // پردازش نمایندگان انتخاب شده
$agent_str = '';
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

    // حذف ADMIN از لیست
    $selected_agents = array_filter($selected_agents, function($agent) {
        return strtoupper($agent) !== "ADMIN";
    });

    $agent_str = implode(',', $selected_agents);
}
    
    $data = [
        'name_fa' => $_POST['name_fa'],
        'name_en' => $_POST['name_en'],
        'title_fa' => "اختصاصی",
        'title_en' => "dedicated",
        'fragment' => ($_POST['config_type'] != 'json') ? $_POST['op_fragment'] : 'false',
        'link' => $config_value,
        'locations_fa' => $loc,
        'locations_en' => $loc2,
        'agent' => $agent_str,
        'src' => $SRC,
        'permium' => 'Pro',
        'oprator' => 'ALL',
        'json' => $is_json,
        'date' => $Time
    ];
    
    if(isset($_GET['add'])) {
        // افزودن سرور جدید
        $qry = Insert('configs', $data);
        $_SESSION['msg'] = "10";
        header("Location:managerServers.php");
        exit;
    } elseif(isset($_POST['server_id'])) {
        // ویرایش سرور موجود
        $server_edit = Update('configs', $data, "WHERE id = '".$_POST['server_id']."'");
        if($server_edit > 0) {
            $_SESSION['msg'] = "11";
            header("Location:managerServers.php");
            exit;
        }
    }
}
?>

<meta charset="utf8mb4">
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="page_title_block">
                <div class="col-md-5 col-xs-12">
                    <div class="page_title"><?= isset($_GET['server_id']) ? 'ویرایش' : 'اضافه کردن' ?> سرور همکاران</div>
                </div>
            </div>
            <div class="clearfix"></div>
            <div class="row mrg-top">
                <div class="col-md-12">
                    <div class="col-md-12 col-sm-12">
                        <?php if(isset($_SESSION['msg'])): ?>
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                            <?= $client_lang[$_SESSION['msg']] ?>
                        </div>
                        <?php unset($_SESSION['msg']); endif; ?>
                    </div>
                </div>
            </div>
            <div class="card-body mrg_bottom">
                <form action="" name="addedituser" method="post" class="form form-horizontal" enctype="multipart/form-data">
                    <input type="hidden" name="server_id" value="<?= $_GET['server_id'] ?? '' ?>">
                    <div class="section">
                        <div class="section-body">
                            <div class="form-group">
                                <label class="col-md-3 control-label">نام سرور فارسی:</label>
                                <div class="col-md-6">
                                    <input type="text" name="name_fa" id="name_fa" value="<?= $user_row['name_fa'] ?? '' ?>" class="form-control" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-3 control-label">نام سرور لاتین:</label>
                                <div class="col-md-6">
                                    <input type="text" name="name_en" id="name_en" value="<?= $user_row['name_en'] ?? '' ?>" class="form-control" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-3 control-label">نوع کانفیگ:</label>
                                <div class="col-md-6">
                                    <select name="config_type" id="config_type" class="select2" required onchange="toggleConfigFields()">
                                        <option value="normal" <?= (isset($user_row['json']) && $user_row['json'] == 'false') ? 'selected' : '' ?>>معمولی</option>
                                        <option value="json" <?= (isset($user_row['json']) && $user_row['json'] == 'true') ? 'selected' : '' ?>>جیسون</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group" id="link_field">
                                <label class="col-md-3 control-label">کانفیگ:</label>
                                <div class="col-md-6">
                                    <input type="text" name="link" id="link" value="<?= (isset($user_row['json']) && $user_row['json'] == 'false') ? ($user_row['link'] ?? '') : '' ?>" class="form-control">
                                </div>
                            </div>
                            <div class="form-group" id="json_field" style="display:none;">
                                <label class="col-md-3 control-label">کانفیگ JSON:</label>
                                <div class="col-md-6">
                                    <textarea name="json_config" id="json_config" class="form-control"><?= (isset($user_row['json']) && $user_row['json'] == 'true') ? htmlspecialchars($user_row['link'] ?? '', JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '' ?></textarea>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-3 control-label">پرچم کشور</label>
                                <div class="col-md-6">
                                    <select name="src" id="src" class="select2">
                                        <?php foreach($countries as $code => $country): ?>
                                            <?php 
                                            $selected = false;
                                            if(isset($user_row['src'])) {
                                                $selected_src = str_replace('.png', '', $user_row['src']);
                                                $selected = ($selected_src == $code);
                                            }
                                            ?>
                                            <option value="<?= $code ?>" <?= $selected ? 'selected' : '' ?>><?= $country[0] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group" id="fragment_field">
                                <label class="col-md-3 control-label">فرگمنت</label>
                                <div class="col-md-6">
                                    <select name="op_fragment" id="op_fragment" class="select2">
                                        <option value="true" <?= (isset($user_row['fragment']) && $user_row['fragment'] == 'true') ? 'selected' : '' ?>>فعال</option>
                                        <option value="false" <?= (isset($user_row['fragment']) && $user_row['fragment'] == 'false') ? 'selected' : '' ?>>غیرفعال</option>
                                    </select>
                                </div>
                            </div>
                            <?php
                            /*
                            // <div class="form-group">
                            //     <label class="col-md-3 control-label">انتخاب نماینده:</label>
                            //     <div class="col-md-6">
                            //     <select name="agent[]" id="agent" class="select2" multiple>
                            //         <option value="all">همه</option>
                            //         <?php while ($agent_row = mysqli_fetch_assoc($agent_result)) { ?>
                            //             <option value="<?php echo $agent_row['agent']; ?>" 
                            //                 <?php if(isset($user_row) && in_array($agent_row['agent'], explode(',', $user_row['agent']))) echo 'selected'; ?>>
                            //                 <?php echo $agent_row['name']; ?>
                            //             </option>
                            //         <?php } ?>
                            //     </select>
                            //     </div>
                            // </div>   
                            */
                            ?>
                            <div class="form-group">
                            <label class="col-md-3 control-label">انتخاب همکاران:</label>
                            <div class="col-md-6">
                                <select name="agent[]" id="agent" class="select2" multiple>
                                    <option value="all">همه</option>
                                    <?php while ($agent_row = mysqli_fetch_assoc($agent_result)) { ?>
                                        <option value="<?php echo $agent_row['agent']; ?>" 
                                            <?php if(isset($user_row) && in_array($agent_row['agent'], explode(',', $user_row['agent']))) echo 'selected'; ?>>
                                            <?php echo $agent_row['name']; ?>
                                        </option>
                                    <?php } ?>
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

<script type="text/javascript">
function toggleConfigFields() {
    var configType = document.getElementById('config_type').value;
    
    if(configType == 'json') {
        document.getElementById('link_field').style.display = 'none';
        document.getElementById('json_field').style.display = 'block';
        document.getElementById('fragment_field').style.display = 'none';
    } else {
        document.getElementById('link_field').style.display = 'block';
        document.getElementById('json_field').style.display = 'none';
        document.getElementById('fragment_field').style.display = 'block';
    }
}

$(document).ready(function() {
    // Initialize Select2 for agent dropdown
    $('#agent').select2({
        placeholder: "نماینده را انتخاب کنید",
        width: '100%'
    });
    
    // Handle "all" selection
    $('#agent').on('change', function() {
        var values = $(this).val();
        
        if(values && values.includes('all')) {
            // If "all" is selected, deselect all other options
            $(this).val(['all']).trigger('change');
        }
    });
    
    // Initialize config fields visibility
    toggleConfigFields();
});
</script>

<?php include('includes/footer.php'); ?>