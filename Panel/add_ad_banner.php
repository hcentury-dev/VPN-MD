<?php
// شروع session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('includes/header.php');
include('includes/function.php');
include('language/language.php');

// تنظیمات آپلود فایل
define('MAX_IMAGE_SIZE', 2048);
define('MAX_GIF_SIZE', 5120);
define('IMAGE_WIDTH', 1200);
define('IMAGE_HEIGHT', 628);
define('GIF_WIDTH', 800);
define('GIF_HEIGHT', 400);

// دریافت اطلاعات نمایندگان
$agent_result = mysqli_query($mysqli, "SELECT * FROM agents");

if(isset($_GET['ad_id']) && is_numeric($_GET['ad_id'])) {
    $ad_id = mysqli_real_escape_string($mysqli, $_GET['ad_id']);
    $ad_result = mysqli_query($mysqli, "SELECT * FROM AD WHERE id='$ad_id'");
    if(mysqli_num_rows($ad_result) > 0) {
        $ad_row = mysqli_fetch_assoc($ad_result);
        $items_result = mysqli_query($mysqli, "SELECT * FROM ad_items WHERE ad_id='$ad_id' ORDER BY sort_order");
        $items = [];
        while($item = mysqli_fetch_assoc($items_result)) {
            $items[] = $item;
        }
    } else {
        $_SESSION['msg'] = "80";
        header("Location: ad.php");
        exit;
    }
}

if(isset($_POST['agent']) && !isset($_GET['ad_id'])) {
    $selected_agents = $_POST['agent'];
    if(in_array("all", $selected_agents)) {
        $all_agents_result = mysqli_query($mysqli, "SELECT agent FROM agents");
        $selected_agents = array_column(mysqli_fetch_all($all_agents_result, MYSQLI_ASSOC), 'agent');
    }
    $conditions = array_map(function($agent) use ($mysqli) {
        return "FIND_IN_SET('" . mysqli_real_escape_string($mysqli, $agent) . "', agent)";
    }, $selected_agents);

    $check_result = mysqli_query($mysqli, "SELECT id FROM AD WHERE (".implode(" OR ", $conditions).") AND status='active'");
    if(mysqli_num_rows($check_result) > 0) {
        $existing_ad = mysqli_fetch_assoc($check_result);
        $_SESSION['msg'] = "80";
        header("Location: add_ad_banner.php?ad_id=".$existing_ad['id']);
        exit;
    }
}

if(isset($_POST['submit'])) {
    if(empty($_POST['agent'])) {
        $_SESSION['msg'] = "81";
        header("Location: add_ad_banner.php".(isset($_GET['ad_id']) ? '?ad_id='.$_GET['ad_id'] : '?add'));
        exit;
    }

    $selected_agents = $_POST['agent'];
    if(in_array("all", $selected_agents)) {
        $all_agents_result = mysqli_query($mysqli, "SELECT agent FROM agents");
        $selected_agents = array_column(mysqli_fetch_all($all_agents_result, MYSQLI_ASSOC), 'agent');
    }
    $agent_str = implode(',', $selected_agents);

    $item_count = count($_POST['content_type']);
    $items_to_insert = [];

    for ($i = 0; $i < $item_count; $i++) {
        $content_type = $_POST['content_type'][$i];
        $content = '';

        if ($content_type === 'text') {
            $content = mysqli_real_escape_string($mysqli, $_POST['content_text'][$i]);

        } elseif ($content_type === 'image') {
            if (!empty($_FILES['content_image']['name'][$i])) {
                $file = [
                    'name' => $_FILES['content_image']['name'][$i],
                    'type' => $_FILES['content_image']['type'][$i],
                    'tmp_name' => $_FILES['content_image']['tmp_name'][$i],
                    'error' => $_FILES['content_image']['error'][$i],
                    'size' => $_FILES['content_image']['size'][$i]
                ];

                $allowed_image_types = ['image/jpeg', 'image/png', 'image/jpg'];
                if (!in_array($file['type'], $allowed_image_types)) {
                    $_SESSION['msg'] = "82";
                    header("Location: add_ad_banner.php" . (isset($_GET['ad_id']) ? '?ad_id=' . $_GET['ad_id'] : ''));
                    exit;
                }
                if ($file['size'] > MAX_IMAGE_SIZE * 1024) {
                    $_SESSION['msg'] = "83";
                    header("Location: add_ad_banner.php" . (isset($_GET['ad_id']) ? '?ad_id=' . $_GET['ad_id'] : ''));
                    exit;
                }
                list($width, $height) = getimagesize($file['tmp_name']);
                if ($width != IMAGE_WIDTH || $height != IMAGE_HEIGHT) {
                    $_SESSION['msg'] = "85";
                    header("Location: add_ad_banner.php" . (isset($_GET['ad_id']) ? '?ad_id=' . $_GET['ad_id'] : ''));
                    exit;
                }
                $content = uploadFile($file, 'images/ads/');
            } elseif (!empty($_POST['existing_image'][$i])) {
                $content = mysqli_real_escape_string($mysqli, $_POST['existing_image'][$i]);
            }

        } elseif ($content_type === 'gif') {
            if (!empty($_FILES['content_gif']['name'][$i])) {
                $file = [
                    'name' => $_FILES['content_gif']['name'][$i],
                    'type' => $_FILES['content_gif']['type'][$i],
                    'tmp_name' => $_FILES['content_gif']['tmp_name'][$i],
                    'error' => $_FILES['content_gif']['error'][$i],
                    'size' => $_FILES['content_gif']['size'][$i]
                ];

                if ($file['type'] != 'image/gif') {
                    $_SESSION['msg'] = "87";
                    header("Location: add_ad_banner.php" . (isset($_GET['ad_id']) ? '?ad_id=' . $_GET['ad_id'] : ''));
                    exit;
                }
                if ($file['size'] > MAX_GIF_SIZE * 1024) {
                    $_SESSION['msg'] = "88";
                    header("Location: add_ad_banner.php" . (isset($_GET['ad_id']) ? '?ad_id=' . $_GET['ad_id'] : ''));
                    exit;
                }
                list($width, $height) = getimagesize($file['tmp_name']);
                if ($width > GIF_WIDTH || $height > GIF_HEIGHT) {
                    $_SESSION['msg'] = "89";
                    header("Location: add_ad_banner.php" . (isset($_GET['ad_id']) ? '?ad_id=' . $_GET['ad_id'] : ''));
                    exit;
                }
                $content = uploadFile($file, 'images/ads/');
            } elseif (!empty($_POST['existing_gif'][$i])) {
                $content = mysqli_real_escape_string($mysqli, $_POST['existing_gif'][$i]);
            }
        }

        if (!empty($content)) {
            $items_to_insert[] = [
                'content' => $content,
                'type' => $content_type,
                'sort_order' => $i
            ];
        }
    }

    // اگر در حال ویرایش هستیم
    if(isset($_GET['ad_id'])) {
        $ad_id = mysqli_real_escape_string($mysqli, $_GET['ad_id']);
        Update('AD', ['status' => $_POST['status'], 'agent' => $agent_str], "WHERE id = '$ad_id'");
        mysqli_query($mysqli, "DELETE FROM ad_items WHERE ad_id = '$ad_id'");
    } else {
        $ad_id = Insert('AD', ['status' => $_POST['status'], 'agent' => $agent_str]);
    }

    // اگر آیدی تبلیغ معتبر بود و آیتمی برای درج وجود دارد
    if ($ad_id && count($items_to_insert)) {
        foreach ($items_to_insert as $item) {
            Insert('ad_items', array_merge(['ad_id' => $ad_id], $item));
        }
        $_SESSION['msg'] = isset($_GET['ad_id']) ? "11" : "10";
        header("Location: ad.php");
        exit;
    }

    // اگر مشکلی در درج بود
    $_SESSION['msg'] = "80";
    header("Location: add_ad_banner.php" . (isset($_GET['ad_id']) ? '?ad_id=' . $_GET['ad_id'] : ''));
    exit;
}
?>


<meta charset="utf8mb4">
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="page_title_block">
                <div class="col-md-5 col-xs-12">
                    <div class="page_title"><?= isset($_GET['ad_id']) ? 'ویرایش' : 'اضافه کردن' ?> تبلیغات</div>
                </div>
            </div>
            <div class="clearfix"></div>
            <div class="row mrg-top">
                <div class="col-md-12">
                    <div class="col-md-12 col-sm-12">
                        <?php if(isset($_SESSION['msg'])) { ?>
                        <div class="alert alert-<?= $_SESSION['msg'] == 'ad_exists' ? 'warning' : 'danger' ?> alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                            <?= $client_lang[$_SESSION['msg']] ?>
                        </div>
                        <?php unset($_SESSION['msg']); } ?>
                    </div>
                </div>
            </div>
            <div class="card-body mrg_bottom"> 
                <form action="" name="addeditad" method="post" class="form form-horizontal" enctype="multipart/form-data">
                    <input type="hidden" name="ad_id" value="<?= $_GET['ad_id'] ?? '' ?>" />
                    
                    <div class="section">
                        <div class="section-body">
                            <div class="form-group">
                                <label class="col-md-3 control-label">انتخاب نماینده:</label>
                                <div class="col-md-6">
                                    <select name="agent[]" id="agent" class="select2" multiple required>
                                        <option value="all">همه</option>
                                        <?php 
                                            mysqli_data_seek($agent_result, 0); // Reset pointer
                                            while ($agent_row = mysqli_fetch_assoc($agent_result)) { 
                                        ?>
                                            <option value="<?php echo $agent_row['agent']; ?>" 
                                                <?php if(isset($ad_row) && in_array($agent_row['agent'], explode(',', $ad_row['agent']))) echo 'selected'; ?>>
                                                <?php echo $agent_row['name']; ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                    <small class="text-muted">می‌توانید چند نماینده را انتخاب کنید</small>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <div class="col-md-6 col-md-offset-3">
                                    <div class="alert alert-info">
                                        <strong>راهنما:</strong>
                                        <ul>
                                            <li>برای عکس‌ها: فرمت JPG/PNG، حجم حداکثر <?= MAX_IMAGE_SIZE ?>KB، ابعاد دقیق <?= IMAGE_WIDTH ?>x<?= IMAGE_HEIGHT ?> پیکسل</li>
                                            <li>برای گیف‌ها: فرمت GIF، حجم حداکثر <?= MAX_GIF_SIZE ?>KB، ابعاد حداکثر <?= GIF_WIDTH ?>x<?= GIF_HEIGHT ?> پیکسل</li>
                                            <li>می‌توانید چندین آیتم با ترتیب مشخص ایجاد کنید</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="items-container">
                                <?php if(isset($items) && count($items) > 0): ?>
                                    <?php foreach($items as $index => $item): ?>
                                        <div class="item-group" data-index="<?= $index ?>">
                                            <div class="form-group">
                                                <label class="col-md-3 control-label">نوع محتوا:</label>
                                                <div class="col-md-6">
                                                    <select name="content_type[]" class="form-control content-type" onchange="toggleContentFields(this)" required>
                                                        <option value="text" <?= $item['type'] == 'text' ? 'selected' : '' ?>>متن</option>
                                                        <option value="image" <?= $item['type'] == 'image' ? 'selected' : '' ?>>عکس</option>
                                                        <option value="gif" <?= $item['type'] == 'gif' ? 'selected' : '' ?>>گیف</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-1">
                                                    <button type="button" class="btn btn-danger btn-sm remove-item" onclick="removeItem(this)">حذف</button>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group text-field" style="<?= $item['type'] != 'text' ? 'display:none;' : '' ?>">
                                                <label class="col-md-3 control-label">متن تبلیغ:</label>
                                                <div class="col-md-6">
                                                    <textarea name="content_text[]" class="form-control" <?= $item['type'] == 'text' ? 'required' : '' ?>><?= $item['type'] == 'text' ? htmlspecialchars($item['content']) : '' ?></textarea>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group image-field" style="<?= $item['type'] != 'image' ? 'display:none;' : '' ?>">
                                                <label class="col-md-3 control-label">عکس تبلیغ:</label>
                                                <div class="col-md-6">
                                                    <input type="file" name="content_image[]" class="form-control" accept="image/jpeg,image/png,image/jpg" <?= $item['type'] == 'image' && empty($item['content']) ? 'required' : '' ?>>
                                                    <?php if($item['type'] == 'image' && !empty($item['content'])): ?>
                                                        <input type="hidden" name="existing_image[]" value="<?= $item['content'] ?>">
                                                        <img src="<?= $item['content'] ?>" width="100" style="margin-top:10px;">
                                                    <?php else: ?>
                                                        <input type="hidden" name="existing_image[]" value="">
                                                    <?php endif; ?>
                                                    <small class="text-muted">فرمت: JPG/PNG، حجم: حداکثر <?= MAX_IMAGE_SIZE ?>KB، ابعاد: <?= IMAGE_WIDTH ?>x<?= IMAGE_HEIGHT ?> پیکسل</small>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group gif-field" style="<?= $item['type'] != 'gif' ? 'display:none;' : '' ?>">
                                                <label class="col-md-3 control-label">گیف تبلیغ:</label>
                                                <div class="col-md-6">
                                                    <input type="file" name="content_gif[]" class="form-control" accept="image/gif" <?= $item['type'] == 'gif' && empty($item['content']) ? 'required' : '' ?>>
                                                    <?php if($item['type'] == 'gif' && !empty($item['content'])): ?>
                                                        <input type="hidden" name="existing_gif[]" value="<?= $item['content'] ?>">
                                                        <img src="<?= $item['content'] ?>" width="100" style="margin-top:10px;">
                                                    <?php else: ?>
                                                        <input type="hidden" name="existing_gif[]" value="">
                                                    <?php endif; ?>
                                                    <small class="text-muted">فرمت: GIF، حجم: حداکثر <?= MAX_GIF_SIZE ?>KB، ابعاد: حداکثر <?= GIF_WIDTH ?>x<?= GIF_HEIGHT ?> پیکسل</small>
                                                </div>
                                            </div>
                                            <hr>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="item-group" data-index="0">
                                        <div class="form-group">
                                            <label class="col-md-3 control-label">نوع محتوا:</label>
                                            <div class="col-md-6">
                                                <select name="content_type[]" class="form-control content-type" onchange="toggleContentFields(this)" required>
                                                    <option value="text">متن</option>
                                                    <option value="image">عکس</option>
                                                    <option value="gif">گیف</option>
                                                </select>
                                            </div>
                                            <div class="col-md-1">
                                                <button type="button" class="btn btn-danger btn-sm remove-item" onclick="removeItem(this)">حذف</button>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group text-field">
                                            <label class="col-md-3 control-label">متن تبلیغ:</label>
                                            <div class="col-md-6">
                                                <textarea name="content_text[]" class="form-control"></textarea>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group image-field" style="display:none;">
                                            <label class="col-md-3 control-label">عکس تبلیغ:</label>
                                            <div class="col-md-6">
                                                <input type="file" name="content_image[]" class="form-control" accept="image/jpeg,image/png,image/jpg">
                                                <input type="hidden" name="existing_image[]" value="">
                                                <small class="text-muted">فرمت: JPG/PNG، حجم: حداکثر <?= MAX_IMAGE_SIZE ?>KB، ابعاد: <?= IMAGE_WIDTH ?>x<?= IMAGE_HEIGHT ?> پیکسل</small>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group gif-field" style="display:none;">
                                            <label class="col-md-3 control-label">گیف تبلیغ:</label>
                                            <div class="col-md-6">
                                                <input type="file" name="content_gif[]" class="form-control" accept="image/gif">
                                                <input type="hidden" name="existing_gif[]" value="">
                                                <small class="text-muted">فرمت: GIF، حجم: حداکثر <?= MAX_GIF_SIZE ?>KB، ابعاد: حداکثر <?= GIF_WIDTH ?>x<?= GIF_HEIGHT ?> پیکسل</small>
                                            </div>
                                        </div>
                                        <hr>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <div class="col-md-9 col-md-offset-3">
                                    <button type="button" class="btn btn-success" onclick="addItem()">افزودن آیتم جدید</button>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label">وضعیت:</label>
                                <div class="col-md-6">
                                    <select name="status" class="form-control" required>
                                        <option value="active" <?= isset($ad_row) && $ad_row['status'] == 'active' ? 'selected' : '' ?>>فعال</option>
                                        <option value="deactive" <?= isset($ad_row) && $ad_row['status'] == 'deactive' ? 'selected' : '' ?>>غیرفعال</option>
                                    </select>
                                </div>
                            </div>
                            <p></p>
                            <div class="form-group">
                                <div class="col-md-9 col-md-offset-3">
                                    <button type="submit" name="submit" class="btn btn-primary">ذخیره</button>
                                    <a href="ad.php" class="btn btn-default">انصراف</a>
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
function toggleContentFields(selectElement) {
    const itemGroup = selectElement.closest('.item-group');
    const contentType = selectElement.value;
    
    itemGroup.querySelector('.text-field').style.display = 'none';
    itemGroup.querySelector('.image-field').style.display = 'none';
    itemGroup.querySelector('.gif-field').style.display = 'none';
    
    // Remove required attributes first
    itemGroup.querySelector('textarea[name="content_text[]"]').removeAttribute('required');
    itemGroup.querySelector('input[name="content_image[]"]').removeAttribute('required');
    itemGroup.querySelector('input[name="content_gif[]"]').removeAttribute('required');
    
    if (contentType === 'text') {
        itemGroup.querySelector('.text-field').style.display = 'block';
        itemGroup.querySelector('textarea[name="content_text[]"]').setAttribute('required', 'required');
    } else if (contentType === 'image') {
        itemGroup.querySelector('.image-field').style.display = 'block';
        itemGroup.querySelector('input[name="content_image[]"]').setAttribute('required', 'required');
    } else if (contentType === 'gif') {
        itemGroup.querySelector('.gif-field').style.display = 'block';
        itemGroup.querySelector('input[name="content_gif[]"]').setAttribute('required', 'required');
    }
}

function addItem() {
    const container = document.getElementById('items-container');
    const items = container.querySelectorAll('.item-group');
    const newIndex = items.length;
    
    const newItem = document.createElement('div');
    newItem.className = 'item-group';
    newItem.dataset.index = newIndex;
    
    newItem.innerHTML = `
        <div class="form-group">
            <label class="col-md-3 control-label">نوع محتوا:</label>
            <div class="col-md-6">
                <select name="content_type[]" class="form-control content-type" onchange="toggleContentFields(this)" required>
                    <option value="text">متن</option>
                    <option value="image">عکس</option>
                    <option value="gif">گیف</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger btn-sm remove-item" onclick="removeItem(this)">حذف</button>
            </div>
        </div>
        
        <div class="form-group text-field">
            <label class="col-md-3 control-label">متن تبلیغ:</label>
            <div class="col-md-6">
                <textarea name="content_text[]" class="form-control" required></textarea>
            </div>
        </div>
        
        <div class="form-group image-field" style="display:none;">
            <label class="col-md-3 control-label">عکس تبلیغ:</label>
            <div class="col-md-6">
                <input type="file" name="content_image[]" class="form-control" accept="image/jpeg,image/png,image/jpg">
                <input type="hidden" name="existing_image[]" value="">
                <small class="text-muted">فرمت: JPG/PNG، حجم: حداکثر <?= MAX_IMAGE_SIZE ?>KB، ابعاد: <?= IMAGE_WIDTH ?>x<?= IMAGE_HEIGHT ?> پیکسل</small>
            </div>
        </div>
        
        <div class="form-group gif-field" style="display:none;">
            <label class="col-md-3 control-label">گیف تبلیغ:</label>
            <div class="col-md-6">
                <input type="file" name="content_gif[]" class="form-control" accept="image/gif">
                <input type="hidden" name="existing_gif[]" value="">
                <small class="text-muted">فرمت: GIF، حجم: حداکثر <?= MAX_GIF_SIZE ?>KB، ابعاد: حداکثر <?= GIF_WIDTH ?>x<?= GIF_HEIGHT ?> پیکسل</small>
            </div>
        </div>
        <hr>
    `;
    
    container.appendChild(newItem);
}

function removeItem(button) {
    const itemGroup = button.closest('.item-group');
    const container = document.getElementById('items-container');
    const items = container.querySelectorAll('.item-group');
    
    if (items.length > 1) {
        if(confirm('آیا از حذف این آیتم مطمئن هستید؟')) {
            itemGroup.remove();
            
            // Update indexes
            container.querySelectorAll('.item-group').forEach((group, index) => {
                group.dataset.index = index;
            });
        }
    } else {
        alert('حداقل یک آیتم باید وجود داشته باشد');
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.content-type').forEach(select => {
        toggleContentFields(select);
    });
    
    // اعتبارسنجی فرم قبل از ارسال
    document.querySelector('form[name="addeditad"]').addEventListener('submit', function(e) {
        let isValid = true;
        
        // بررسی نمایندگان انتخاب شده
        const agentSelect = document.getElementById('agent');
        if(agentSelect.selectedOptions.length === 0) {
            alert('لطفاً حداقل یک نماینده انتخاب کنید');
            isValid = false;
        }
        
        // بررسی آیتم‌ها
        const itemGroups = document.querySelectorAll('.item-group');
        itemGroups.forEach(group => {
            const contentType = group.querySelector('.content-type').value;
            
            if(contentType === 'text') {
                const textContent = group.querySelector('textarea[name="content_text[]"]').value.trim();
                if(textContent === '') {
                    alert('لطفاً متن تبلیغ را وارد کنید');
                    isValid = false;
                }
            } else if(contentType === 'image') {
                const fileInput = group.querySelector('input[name="content_image[]"]');
                const existingImage = group.querySelector('input[name="existing_image[]"]').value;
                
                if(fileInput.files.length === 0 && existingImage === '') {
                    alert('لطفاً یک عکس انتخاب کنید');
                    isValid = false;
                }
            } else if(contentType === 'gif') {
                const fileInput = group.querySelector('input[name="content_gif[]"]');
                const existingGif = group.querySelector('input[name="existing_gif[]"]').value;
                
                if(fileInput.files.length === 0 && existingGif === '') {
                    alert('لطفاً یک فایل GIF انتخاب کنید');
                    isValid = false;
                }
            }
        });
        
        if(!isValid) {
            e.preventDefault();
        }
    });
});
</script>

<?php include('includes/footer.php'); ?>