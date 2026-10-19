<?php include("includes/header.php");

   if( isset($_SERVER['HTTPS'] ) ) {  

    $file_path = 'https://'.$_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']).'/api.php';
  }
  else
  {
    $file_path = 'http://'.$_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']).'/api.php';
  }
?>
<div class="row">
  <div class="col-sm-12 col-xs-12">
    <div class="card">
        <div class="card-header">
          API URLS
          </div>
            <div class="card-body no-padding">
           <pre>
            <code class="html">
              <br><b>API URL</b>&nbsp; <?php echo $file_path;?>    

              <br><b>Server List</b> (Method: get_server) (Parameters: "")
              <br><b>Users List</b> (Method: get_users) (Parameters: "")
              <br><b>Server List</b> (Method: get_server) (Parameters: "")
              <br><b>Server List</b> (Method: get_server) (Parameters: "")

            </code> 
            </pre>
          </div>
        </div>
    </div>
  </div>
<br/>
<div class="clearfix"></div>
        
<?php include("includes/footer.php");?>