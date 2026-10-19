<?php 

    include('includes/header.php');
    include('includes/function.php');
	include('language/language.php'); 
	include('includes/jdf.php'); 

    $today = date('Y-m-d');
    $agent_qry = "SELECT * FROM agents";
    $agent_result = mysqli_query($mysqli, $agent_qry);

	if (isset($_POST['submit']) and isset($_GET['add']))
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
        
            $agent_str = implode(',', $selected_agents);
        }
        
        $data = array(
            'title_fa' => $_POST['title_fa'],
            'title_en' => $_POST['title_en'],
            'text_fa'  => strip_tags($_POST['text_fa']),
            'text_en'  => strip_tags($_POST['text_en']),
            'main'     => $_POST['main'],
            'status'   => $_POST['status'],
            'date'     => $today,
            'agent'    => $agent_str
        );
        $qry = Insert('news', $data);
			$_SESSION['msg']="10";
			header("location:news.php");	 
			exit;
		}
	
	if(isset($_GET['news_id']))
	{
			 
			$user_qry="SELECT * FROM news where id='".$_GET['news_id']."'";
			$user_result=mysqli_query($mysqli,$user_qry);
			$user_row=mysqli_fetch_assoc($user_result);
			
		
	}
	
	if(isset($_POST['submit']) and isset($_POST['news_id']))
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
        
            $agent_str = implode(',', $selected_agents);
        }
        
        $data = array(
            'title_fa' => $_POST['title_fa'],
            'title_en' => $_POST['title_en'],
            'text_fa'  => strip_tags($_POST['text_fa']),
            'text_en'  => strip_tags($_POST['text_en']),
            'main'     => $_POST['main'],
            'status'   => $_POST['status'],
            'agent'    => $agent_str
        );
        $news_edit = Update('news', $data, "WHERE id = '".$_POST['news_id']."'");
			if ($news_edit > 0){
				$_SESSION['msg']="11";
				header("Location:news.php?id=".$_POST['news_id']);
				exit;
			} 	
	}
?>
 	
<meta charset="utf8mb4">
 <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="page_title_block">
            <div class="col-md-5 col-xs-12">
              <div class="page_title"><?php if(isset($_GET['news_id'])){?>ویرایش<?php }else{?>اضافه کردن<?php }?> خبر</div>
            </div>
          </div>
          <div class="clearfix"></div>
          <div class="row mrg-top">
            <div class="col-md-12">
              <div class="col-md-12 col-sm-12">
                <?php if(isset($_SESSION['msg'])){?> 
               	 <div class="alert alert-danger alert-dismissible" role="alert"> <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                	<?php echo $client_lang[$_SESSION['msg']] ; ?></a> </div>
                <?php unset($_SESSION['msg']);}?>	
              </div>
            </div>
          </div>
          <div class="card-body mrg_bottom"> 
            <form action="" name="addedituser" method="post" class="form form-horizontal" enctype="multipart/form-data" >
            	<input  type="hidden" name="news_id" value="<?php echo $_GET['news_id'];?>" />
              <div class="section">
                <div class="section-body">
                <div class="form-group">
                    <label class="col-md-3 control-label">انتخاب نماینده:</label>
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
                <label class="col-md-3 control-label">عنوان خبر فارسی :</label>
                <div class="col-md-6">
                  <input type="text" name="title_fa" id="title_fa" value="<?php if(isset($_GET['news_id'])){echo $user_row['title_fa'];}?>" class="form-control" required>
                </div>
                </div>
                <div class="form-group">
                <label class="col-md-3 control-label">عنوان خبر لاتین :</label>
                <div class="col-md-6">
                  <input type="text" name="title_en" id="title_en" value="<?php if(isset($_GET['news_id'])){echo $user_row['title_en'];}?>" class="form-control" required>
                </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">متن خبر فارسی :</label>
                    <div class="col-md-6">
                        <textarea name="text_fa" id="text_fa" class="form-control" required><?php if(isset($_GET['news_id'])){echo str_replace('<br />', "\n", $user_row['text_fa']);}?></textarea>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-md-3 control-label">متن خبر لاتین :</label>
                    <div class="col-md-6">
                        <textarea name="text_en" id="text_en" class="form-control" required><?php if(isset($_GET['news_id'])){echo str_replace('<br />', "\n", $user_row['text_en']);}?></textarea>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">خبر مهم است؟</label>
                        <div class="col-md-6">                    
                        <select name="main" id="main" class="select2">
                          <option value="1" <?php if($user_row['main']=='1'){?>selected<?php }?>>بله</option>
                          <option value="0" <?php if($user_row['main']=='0'){?>selected<?php }?>>خیر</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">خبر فعال باشد؟</label>
                        <div class="col-md-6">                    
                        <select name="status" id="status" class="select2">
                          <option value="active" <?php if($user_row['status']=='active'){?>selected<?php }?>>بله</option>
                          <option value="deactive" <?php if($user_row['status']=='deactive'){?>selected<?php }?>>خیر</option>
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


<?php include('includes/footer.php');?>                  