<?php
require_once __DIR__ . '/includes/session_boot.php';

include('includes/header.php');
include('includes/function.php');
include('language/language.php');

$image_dir = 'images/ads/';
$video_dir = 'video/ads/';

if (!is_dir($image_dir)) mkdir($image_dir, 0755, true);
if (!is_dir($video_dir)) mkdir($video_dir, 0755, true);

define('MAX_IMAGE_SIZE', 2048);
define('MAX_VIDEO_SIZE', 10240);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg', 'image/webp']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/quicktime', 'video/webm']);

$ad_row = null;
$is_edit = false;
$ads_id = isset($_GET['ads_id']) ? intval($_GET['ads_id']) : null;

$agent_result = mysqli_query($mysqli, "SELECT * FROM agents");

if ($ads_id) {
    $ad_result = mysqli_query($mysqli, "SELECT * FROM ads_items WHERE id='$ads_id'");
    if (mysqli_num_rows($ad_result) > 0) {
        $ad_row = mysqli_fetch_assoc($ad_result);
        $is_edit = true;
    } else {
        $_SESSION['msg'] = "80";
        header("Location: ad.php");
        exit;
    }
}

if (isset($_POST['submit'])) {
    if (empty($_POST['agent'])) {
        $_SESSION['msg'] = "81";
        header("Location: add_ad_full.php" . ($ads_id ? '?ads_id='.$ads_id : ''));
        exit;
    }

    $selected_agents = $_POST['agent'];
    if (in_array("all", $selected_agents)) {
        $all_agents_result = mysqli_query($mysqli, "SELECT agent FROM agents");
        $selected_agents = array_column(mysqli_fetch_all($all_agents_result, MYSQLI_ASSOC), 'agent');
    }
    $agent_str = implode(',', $selected_agents);
    
    $content_type = $_POST['content_type'] ?? '';
    if (empty($content_type)) {
        $_SESSION['msg'] = "84";
        header("Location: add_ad_full.php" . ($ads_id ? '?ads_id='.$ads_id : ''));
        exit;
    }

    $content = '';
    
    if ($content_type === 'image' || $content_type === 'video') {
        $file = $_FILES['content_file'] ?? null;
        $allowed_types = $content_type === 'image' ? ALLOWED_IMAGE_TYPES : ALLOWED_VIDEO_TYPES;
        $max_size = $content_type === 'image' ? MAX_IMAGE_SIZE : MAX_VIDEO_SIZE;
        $dir = $content_type === 'image' ? $image_dir : $video_dir;

        if ($file && !empty($file['name'])) {
            if (!in_array($file['type'], $allowed_types)) {
                $_SESSION['msg'] = $content_type === 'image' ? "82" : "89"; // نوع فایل نامعتبر
                header("Location: add_ad_full.php" . ($ads_id ? '?ads_id='.$ads_id : ''));
                exit;
            }

            if ($file['size'] > $max_size * 1024) {
                $_SESSION['msg'] = $content_type === 'image' ? "83" : "90";
                header("Location: add_ad_full.php" . ($ads_id ? '?ads_id='.$ads_id : ''));
                exit;
            }

            if ($content_type === 'image') {
                $img_info = @getimagesize($file['tmp_name']);
                if ($img_info === false) {
                    $_SESSION['msg'] = "82";
                    header("Location: add_ad_full.php" . ($ads_id ? '?ads_id='.$ads_id : ''));
                    exit;
                }
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = 'ads_' . uniqid() . '.' . $ext;
            $destination = $dir . $filename;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                $_SESSION['msg'] = "85";
                header("Location: add_ad_full.php" . ($ads_id ? '?ads_id='.$ads_id : ''));
                exit;
            }

            if ($is_edit && !empty($ad_row['content']) && $ad_row['content'] !== $destination) {
                if (file_exists($ad_row['content'])) {
                    @unlink($ad_row['content']);
                }
            }

            $content = $destination;
        } elseif (!empty($_POST['existing_file'])) {
            $content = mysqli_real_escape_string($mysqli, $_POST['existing_file']);
        } else {
            $_SESSION['msg'] = "84";
            header("Location: add_ad_full.php" . ($ads_id ? '?ads_id='.$ads_id : ''));
            exit;
        }
    } else {

        $content = trim($_POST['content_text'] ?? '');
        if ($content === '') {
            $_SESSION['msg'] = "84";
            header("Location: add_ad_full.php" . ($ads_id ? '?ads_id='.$ads_id : ''));
            exit;
        }
    }
    
    $expire_date = $_POST['expire'] ?? '';
    if (empty($expire_date)) {
        $_SESSION['msg'] = "57";
        header("Location: add_ad_full.php" . ($ads_id ? '?ads_id='.$ads_id : ''));
        exit;
    }

    if (strtotime($expire_date) < time()) {
        $_SESSION['msg'] = "94";
        header("Location: add_ad_full.php" . ($ads_id ? '?ads_id='.$ads_id : ''));
        exit;
    }

    $ad_data = [
        'content' => $content,
        'type' => $content_type,
        'agent' => $agent_str,
        'link' => mysqli_real_escape_string($mysqli, $_POST['link'] ?? ''),
        'timer' => intval($_POST['timer'] ?? 5),
        'Knum' => intval($_POST['Knum'] ?? 0),
        'Fnum' => intval($_POST['Fnum'] ?? 1000),
        'first' => isset($_POST['first']) ? 1 : 0,
        'status' => mysqli_real_escape_string($mysqli, $_POST['status'] ?? 'active'),
        'expire' => $expire_date,
        'created_at' => date('Y-m-d H:i'),
    ];

    if ($is_edit) {
        $sql = "UPDATE ads_items SET 
                content = '{$ad_data['content']}',
                type = '{$ad_data['type']}',
                agent = '{$ad_data['agent']}',
                link = '{$ad_data['link']}',
                timer = {$ad_data['timer']},
                Knum = {$ad_data['Knum']},
                Fnum = {$ad_data['Fnum']},
                first = {$ad_data['first']},
                status = '{$ad_data['status']}',
                expire = '{$ad_data['expire']}',
                created_at = '{$ad_data['created_at']}'
                WHERE id = $ads_id";
        
        if (mysqli_query($mysqli, $sql)) {
            $_SESSION['msg'] = "92";
        } else {
            $_SESSION['msg'] = "85";
        }
    } else {
        $sql = "INSERT INTO ads_items 
                (content, type, agent, link, timer, Knum, Fnum, first, status, expire, created_at)
                VALUES (
                    '{$ad_data['content']}',
                    '{$ad_data['type']}',
                    '{$ad_data['agent']}',
                    '{$ad_data['link']}',
                    {$ad_data['timer']},
                    {$ad_data['Knum']},
                    {$ad_data['Fnum']},
                    {$ad_data['first']},
                    '{$ad_data['status']}',
                    '{$ad_data['expire']}',
                    '{$ad_data['created_at']}'
                )";
        
        if (mysqli_query($mysqli, $sql)) {
            $_SESSION['msg'] = "91";
        } else {
            $_SESSION['msg'] = "85";
        }
    }

    header("Location: ad.php");
    exit;
}
?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="page_title_block">
                <div class="col-md-5 col-xs-12">
                    <div class="page_title"><?= $is_edit ? 'ویرایش' : 'اضافه کردن' ?> تبلیغات تمام صفحه</div>
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
                    <input type="hidden" name="ads_id" value="<?= $ads_id ?>" />
                    <input type="hidden" name="content_type" id="content_type" value="<?= $ad_row['type'] ?? 'image' ?>">
                    <input type="hidden" name="existing_file" id="existing_file" value="<?= $ad_row['content'] ?? '' ?>">
                    
                    <div class="row">
                        <!-- Right Column -->
                        <div class="col-md-6">
                            <div class="form-group row">
                                <label class="col-md-4 control-label">انتخاب نماینده:</label>
                                <div class="col-md-8">                                
                                    <select name="agent[]" id="agent" class="select2" multiple required>
                                        <option value="all">همه</option>
                                        <?php 
                                            mysqli_data_seek($agent_result, 0);
                                            while ($agent_row = mysqli_fetch_assoc($agent_result)) { 
                                                $selected = $is_edit && in_array($agent_row['agent'], explode(',', $ad_row['agent'])) ? 'selected' : '';
                                        ?>
                                            <option value="<?= $agent_row['agent'] ?>" <?= $selected ?>>
                                                <?= $agent_row['name'] ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                    <small class="text-muted">می‌توانید چند نماینده را انتخاب کنید</small>
                                </div>
                            </div>
                            
                            <!-- Ad Type Selection -->
                            <div class="form-group row">
                                <label class="col-md-4 control-label">نوع تبلیغ:</label>
                                <div class="col-md-8">
                                    <div class="d-flex justify-content-between">
                                        <div class="ad-type-option flex-fill me-2 <?= ($ad_row['type'] ?? 'image') == 'image' ? 'active' : '' ?>" 
                                             id="option-image" onclick="selectAdType('image')">
<!--                                            <img src="https://img.icons8.com/fluency/48/image.png" alt="عکس" class="d-block mx-auto mb-1">-->
                                            <div class="text-center">عکس</div>
                                        </div>
                                        <div class="ad-type-option flex-fill ms-2 <?= ($ad_row['type'] ?? '') == 'video' ? 'active' : '' ?>" 
                                             id="option-video" onclick="selectAdType('video')">
<!--                                            <img src="https://img.icons8.com/fluency/48/video.png" alt="ویدیو" class="d-block mx-auto mb-1">-->
                                            <div class="text-center">ویدیو</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- File Upload Section -->
                            <div class="form-group row">
                                <label class="col-md-4 control-label">فایل تبلیغ:</label>
                                <div class="col-md-8">
                                    <div class="file-upload-wrapper">
                                        <!-- Upload Button -->
                                        <div class="upload-area" id="uploadArea">
                                            <div class="upload-icon">
                                                <i class="fas fa-cloud-upload-alt"></i>
                                            </div>
                                            <div class="upload-text">
                                                <span id="uploadText">فایل را اینجا بکشید یا کلیک کنید</span>
                                                <small class="text-muted d-block">حداکثر حجم: 
                                                    <span id="maxSizeText"><?= ($ad_row['type'] ?? 'image') == 'image' ? '2MB' : '10MB' ?></span>
                                                </small>
                                            </div>
                                            <input type="file" name="content_file" id="content_file" class="d-none" accept="<?= ($ad_row['type'] ?? 'image') == 'image' ? 'image/*' : 'video/*' ?>">
                                        </div>
                                        
                                        <!-- Preview Section -->
                                        <div class="preview-container mt-3 <?= $is_edit && !empty($ad_row['content']) ? '' : 'd-none' ?>" id="previewContainer">
                                            <div class="preview-header d-flex justify-content-between">
                                                <span>پیش‌نمایش:</span>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="removeFile()">
                                                    <i class="fas fa-trash"></i> حذف
                                                </button>
                                            </div>
                                            <div class="preview-content mt-2" id="previewContent">
                                                <?php if($is_edit && !empty($ad_row['content'])): ?>
                                                    <?php if($ad_row['type'] == 'image'): ?>
                                                        <img src="<?= $ad_row['content'] ?>" class="img-fluid rounded" alt="پیش‌نمایش عکس">
                                                    <?php else: ?>
                                                        <video controls class="img-fluid rounded">
                                                            <source src="<?= $ad_row['content'] ?>" type="video/mp4">
                                                            مرورگر شما از نمایش ویدئو پشتیبانی نمی‌کند.
                                                        </video>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group row">
                                <label class="col-md-4 control-label">لینک تبلیغ:</label>
                                <div class="col-md-8">
                                    <input type="url" name="link" id="link" class="form-control" 
                                           value="<?= $ad_row['link'] ?? '' ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group row">
                                <label class="col-md-4 control-label">زمان نمایش (ثانیه):</label>
                                <div class="col-md-8">
                                    <input type="number" name="timer" id="timer" class="form-control" 
                                           value="<?= $ad_row['timer'] ?? 5 ?>" min="1" required>
                                </div>
                            </div>
                            
                            <div class="form-group row">
                                <label class="col-md-4 control-label">اولویت نمایش:</label>
                                <div class="col-md-8">
                                    <label class="material-switch">
                                        <input type="checkbox" name="first" id="first" value="1"
                                            <?= ($ad_row['first'] ?? 0) == 1 ? 'checked' : '' ?>>
                                        <span class="material-switch-slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Left Column -->
                        <div class="col-md-6">
                            <div class="form-group row">
                                <label class="col-md-4 control-label">تعداد بازدیدها:</label>
                                <div class="col-md-8">
                                    <input type="number" name="Knum" id="Knum" class="form-control" 
                                           value="<?= $ad_row['Knum'] ?? 0 ?>" min="0">
                                </div>
                            </div>
                            
                            <div class="form-group row">
                                <label class="col-md-4 control-label">بازدیدهای درخواستی:</label>
                                <div class="col-md-8">
                                    <input type="number" name="Fnum" id="Fnum" class="form-control" 
                                           value="<?= $ad_row['Fnum'] ?? 1000 ?>" min="1" required>
                                </div>
                            </div>
                            
                            <div class="form-group row">
                                <label class="col-md-4 control-label">تاریخ انقضا:</label>
                                <div class="col-md-8">
                                    <div class="datepicker-wrapper">
                                       <input type="text" name="expire" id="expire_datetime" class="form-control" 
                                              value="<?= $is_edit ? date('Y-m-d H:i', strtotime($ad_row['expire'])) : '' ?>" 
                                              placeholder="برای انتخاب تاریخ کلیک کنید" readonly>
                                       <i class="fas fa-calendar-alt icon" id="calendar-icon"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group row">
                                <label class="col-md-4 control-label">وضعیت:</label>
                                <div class="col-md-8">
                                    <select name="status" id="status" class="form-control" required>
                                        <option value="active" <?= ($ad_row['status'] ?? 'active') == 'active' ? 'selected' : '' ?>>فعال</option>
                                        <option value="deactive" <?= ($ad_row['status'] ?? '') == 'deactive' ? 'selected' : '' ?>>غیرفعال</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12 text-center">
                            <button type="submit" name="submit" class="btn btn-primary">ذخیره</button>
                            <a href="ad.php" class="btn btn-default">انصراف</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!--<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">-->
<!--<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">-->
<!--<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">-->
<!--<script src="https://cdn.jsdelivr.net/npm/jquery/dist/jquery.min.js"></script>-->
<!--<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>-->
<!--<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fa.js"></script>-->
<!--<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>-->
<!--<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">-->

<style>
    /* Ad Type Selection */
    .ad-type-option {
        cursor: pointer;
        border: 2px solid #ccc;
        border-radius: 10px;
        padding: 10px;
        transition: 0.3s;
        text-align: center;
        background-color: #f8f9fa;
    }

    .ad-type-option.active {
        border-color: #007bff;
        background-color: #e0f0ff;
    }

    .ad-type-option img {
        max-width: 40px;
        height: auto;
    }

    /* File Upload Area */
    .upload-area {
        border: 2px dashed #ccc;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        background-color: #f8f9fa;
    }

    .upload-area:hover {
        border-color: #007bff;
        background-color: #e0f0ff;
    }

    .upload-area.dragover {
        border-color: #28a745;
        background-color: #e0ffe0;
    }

    .upload-icon {
        font-size: 48px;
        color: #6c757d;
        margin-bottom: 10px;
    }

    /* Preview Section */
    .preview-container {
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 10px;
        background-color: #f8f9fa;
    }

    .preview-header {
        padding-bottom: 5px;
        border-bottom: 1px solid #ddd;
        margin-bottom: 10px;
    }

    /* Toggle Switch */
    .material-switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
        margin-top: 8px;
    }

    .material-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .material-switch-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 34px;
    }

    .material-switch-slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }

    input:checked + .material-switch-slider {
        background-color: #2196F3;
    }

    input:checked + .material-switch-slider:before {
        transform: translateX(26px);
    }

    input:focus + .material-switch-slider {
        box-shadow: 0 0 1px #2196F3;
    }

    /* Datepicker */
    .datepicker-wrapper {
        position: relative;
    }
    .datepicker-wrapper input {
        padding-left: 40px;
        background-color: #f8f9fa;
        cursor: pointer;
    }
    .datepicker-wrapper .icon {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 20px;
        color: #6c757d;
        cursor: pointer;
        z-index: 2;
    }
    .datepicker-wrapper input[readonly] {
        background-color: #f8f9fa;
    }
    
    /* Select2 Fixes */
    .select2-container--default .select2-selection--single {
        height: 38px;
        padding: 5px;
        border: 1px solid #ced4da;
        border-radius: 4px;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
    
    /* Form Group Alignment */
    .form-group.row {
        margin-bottom: 15px;
        align-items: center;
    }
    
    .form-group.row label {
        margin-bottom: 0;
    }
</style>

<script>
$(document).ready(function() {
    // Initialize datepicker
    const fpOptions = {
        enableTime: true,
        time_24hr: true,
        dateFormat: "Y-m-d H:i",
        minDate: "today",
        locale: "fa",
        defaultDate: "<?= $is_edit ? date('Y-m-d H:i', strtotime($ad_row['expire'])) : '' ?>"
    };

    const fp = flatpickr("#expire_datetime", fpOptions);

    $("#calendar-icon, #expire_datetime").on('click', function(e) {
        fp.open();
    });

    // Initialize select2
    $('#agent').select2({
        language: 'fa',
        placeholder: "انتخاب نماینده",
        width: '100%',
        dir: 'rtl'
    });

    // Set initial ad type
    const initialType = "<?= $ad_row['type'] ?? 'image' ?>";
    selectAdType(initialType);

    // File upload handling
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('content_file');
    const previewContainer = document.getElementById('previewContainer');
    const previewContent = document.getElementById('previewContent');
    const uploadText = document.getElementById('uploadText');

    uploadArea.addEventListener('click', () => fileInput.click());

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        uploadArea.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, unhighlight, false);
    });

    function highlight() {
        uploadArea.classList.add('dragover');
    }

    function unhighlight() {
        uploadArea.classList.remove('dragover');
    }

    uploadArea.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFiles(files);
    }

    fileInput.addEventListener('change', function() {
        handleFiles(this.files);
    });

    function handleFiles(files) {
        if (files.length === 0) return;
        
        const file = files[0];
        const fileType = document.getElementById('content_type').value;
        
        // Validate file type
        if (fileType === 'image' && !file.type.match('image.*')) {
            alert('لطفاً فقط فایل تصویر انتخاب کنید');
            return;
        }
        
        if (fileType === 'video' && !file.type.match('video.*')) {
            alert('لطفاً فقط فایل ویدئو انتخاب کنید');
            return;
        }
        
        // Show preview
        showPreview(file);
    }

    function showPreview(file) {
        const fileType = document.getElementById('content_type').value;
        const reader = new FileReader();
        
        reader.onload = function(e) {
            let previewHTML = '';
            
            if (fileType === 'image') {
                previewHTML = `<img src="${e.target.result}" class="img-fluid rounded" alt="پیش‌نمایش عکس">`;
            } else {
                previewHTML = `
                    <video controls class="img-fluid rounded">
                        <source src="${e.target.result}" type="${file.type}">
                        مرورگر شما از نمایش ویدئو پشتیبانی نمی‌کند.
                    </video>
                `;
            }
            
            previewContent.innerHTML = previewHTML;
            previewContainer.classList.remove('d-none');
            uploadText.textContent = file.name;
        };
        
        if (fileType === 'image') {
            reader.readAsDataURL(file);
        } else {
            previewContent.innerHTML = `
                <div class="alert alert-info">
                    <i class="fas fa-video"></i> فایل ویدئو برای آپلود انتخاب شد: ${file.name}
                </div>
            `;
            previewContainer.classList.remove('d-none');
            uploadText.textContent = file.name;
        }
    }

    // Form validation
    $('form[name="addeditad"]').submit(function(e) {
        let isValid = true;
        
        if ($('#agent').val() === null || $('#agent').val().length === 0) {
            alert('لطفاً حداقل یک نماینده انتخاب کنید');
            isValid = false;
        }
        
        const fileInput = document.getElementById('content_file');
        const existingFile = document.getElementById('existing_file').value;
        if (fileInput.files.length === 0 && existingFile === '') {
            alert('لطفاً یک فایل برای تبلیغ انتخاب کنید');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
        }
    });
});

function selectAdType(type) {
    document.getElementById("content_type").value = type;
    
    // Update UI
    document.getElementById("option-image").classList.remove("active");
    document.getElementById("option-video").classList.remove("active");
    
    if (type === "image") {
        document.getElementById("option-image").classList.add("active");
        document.getElementById("content_file").setAttribute('accept', 'image/*');
        document.getElementById("maxSizeText").textContent = '2MB';
    } else {
        document.getElementById("option-video").classList.add("active");
        document.getElementById("content_file").setAttribute('accept', 'video/*');
        document.getElementById("maxSizeText").textContent = '10MB';
    }
    
    // Reset file input if type changes
    const currentType = "<?= $ad_row['type'] ?? '' ?>";
    if (currentType !== type) {
        document.getElementById("content_file").value = '';
        document.getElementById("uploadText").textContent = 'فایل را اینجا بکشید یا کلیک کنید';
        document.getElementById("previewContainer").classList.add("d-none");
        document.getElementById("existing_file").value = '';
    }
}

function removeFile() {
    document.getElementById("content_file").value = '';
    document.getElementById("uploadText").textContent = 'فایل را اینجا بکشید یا کلیک کنید';
    document.getElementById("previewContainer").classList.add("d-none");
    document.getElementById("existing_file").value = '';
}
</script>

<?php include('includes/footer.php'); ?>