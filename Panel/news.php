<?php 
    include('includes/header.php'); 
    include('includes/function.php');
    include('language/language.php');  

// 	ini_set('display_errors', 1);
//     ini_set('display_startup_errors', 1);
//     error_reporting(E_ALL);

    if(isset($_POST['news_search'])) {
        $user_qry = "SELECT news.*, agents.name AS agent_name 
             FROM news 
             LEFT JOIN agents ON agents.agent = news.agent 
             WHERE news.title LIKE '%".addslashes($_POST['search_value'])."%' 
             OR news.text LIKE '%".addslashes($_POST['search_value'])."%'
             ORDER BY news.id DESC";

        $users_result = mysqli_query($mysqli, $user_qry);
    } else {
        $tableName = "news";        
        $targetpage = "news.php";     
        $limit = 100; 
        
        $query = "SELECT COUNT(*) as num FROM $tableName";
        $total_pages = mysqli_fetch_array(mysqli_query($mysqli, $query));
        $total_pages = $total_pages['num'];
        
        $stages = 3;
        $page = 0;
        if(isset($_GET['page'])){
            $page = mysqli_real_escape_string($mysqli, $_GET['page']);
        }
        if($page){
            $start = ($page - 1) * $limit; 
        } else {
            $start = 0;    
        }    
            
        $users_qry = "SELECT news.*, GROUP_CONCAT(agents.name SEPARATOR ', ') AS agent_name FROM news LEFT JOIN agents ON FIND_IN_SET(agents.agent, news.agent) > 0 GROUP BY news.id ORDER BY news.id DESC 
              LIMIT $start, $limit";

        $users_result = mysqli_query($mysqli, $users_qry);
    }
     
    if(isset($_GET['news_id'])) {
        Delete('news', 'id=' . $_GET['news_id']);
        $_SESSION['msg'] = "12";
        header("Location: news.php");
        exit;
    }
?>

<meta charset="utf8mb4">
<div class="row">
    <div class="col-xs-12">
        <div class="card mrg_bottom">
            <div class="page_title_block">
                <div class="col-md-5 col-xs-12">
                    <div class="page_title">مدیریت اخبار</div>
                </div>
                <div class="col-md-7 col-xs-12">              
                    <div class="search_list">
                        <div class="search_block">
                            <form method="post" action="">
                                <input class="form-control input-sm" placeholder="جستجو ..." type="search" name="search_value" required>
                                <button type="submit" name="news_search" class="btn-search"><i class="fa fa-search"></i></button>
                            </form>  
                        </div>
                        <div class="add_btn_primary"><a href="add_news.php?add">اضافه کردن خبر</a></div>
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
            <div class="row mrg-top">
                <div class="col-md-12">
                    <div class="col-md-12 col-sm-12">
                        <?php if(isset($_SESSION['msg'])) { ?> 
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert"><span>×</span></button>
                            <?php echo $client_lang[$_SESSION['msg']]; ?>
                        </div>
                        <?php unset($_SESSION['msg']); } ?>    
                    </div>
                </div>
            </div>
            <div class="col-md-12 mrg-top table-responsive">
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th class="text-center">عنوان خبر فارسی</th>                         
                            <th class="text-center">عنوان خبر لاتین</th>                         
                            <th class="text-center">متن خبر فارسی</th>
                            <th class="text-center">متن خبر لاتین</th>
                            <th class="text-center">نماینده</th>
                            <th class="text-center">خبر مهم</th>
                            <th class="text-center">وضعیت</th>                
                            <th class="cat_action_list text-center">گزینه‌ها</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while($users_row = mysqli_fetch_array($users_result)) { ?>
                        <tr>
                            <td class="text-center"><?php echo $users_row['title_fa']; ?></td>
                            <td class="text-center"><?php echo $users_row['title_en']; ?></td>
                            <td class="text-center"><?php echo $users_row['text_fa']; ?></td>
                            <td class="text-center"><?php echo $users_row['text_en']; ?></td>                            
                            <td class="text-center">
                                <code><?php echo (!empty($users_row['agent_name'])) ? $users_row['agent_name'] : '<span class="badge badge-warning">نامشخص</span>'; ?></code>
                            </td>
                            <td class="text-center">
                                <?php if($users_row['main'] != "0") { ?>
                                    <span class="badge badge-success"><i class="fa fa-check"></i> بله</span>
                                <?php } else { ?>
                                    <span class="badge badge-danger"><i class="fa fa-times"></i> خیر</span>
                                <?php } ?>
                            </td>
                            <td class="text-center">
                                <?php if($users_row['status'] != "deactive") { ?>
                                    <span class="badge badge-success"><i class="fa fa-check"></i> فعال</span>
                                <?php } else { ?>
                                    <span class="badge badge-danger"><i class="fa fa-times"></i> غیرفعال</span>
                                <?php } ?>
                            </td>
                            <td class="text-center">
                                <a href="add_news.php?news_id=<?php echo $users_row['id']; ?>" class="btn btn-primary btn_edit"><i class="fa fa-edit"></i></a>
                                <a href="news.php?news_id=<?php echo $users_row['id']; ?>" class="btn btn-danger btn_delete" onclick="return confirm('آیا می‌خواهید این خبر را حذف کنید؟')"><i class="fa fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
            <div class="col-md-12 col-xs-12">
                <div class="pagination_item_block">
                    <nav>
                        <?php if(!isset($_POST["news_search"])) { include("pagination.php"); } ?>                 
                    </nav>
                </div>
            </div>
            <div class="clearfix"></div>
        </div>
    </div>
</div>     

<?php include('includes/footer.php'); ?>  
