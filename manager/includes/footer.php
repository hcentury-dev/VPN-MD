<footer class="app-footer">
        <div class="row">
        <div class="col-xs-12">
            طراحی شده توسط <a href="tg:resolve?domain=mrlosdev" target="_blank" style="text-decoration: none; font-weight: bold;">ویزویز</a> با ❤️ + ☕
        </div>
      </div>
    </footer>
  </div>
</div>
<script type="text/javascript" src="assets/js/vendor.js"></script> 
<script type="text/javascript" src="assets/js/app.js"></script>
<script type="text/javascript" src="assets/bootstrap-tag/bootstrap-tagsinput.js"></script>
<script type="text/javascript">
	$("input").val()
</script>
<script>
	$("#checkall").click(function () {
		$("input:checkbox[name='wall_ids[]']").not(this).prop('checked', this.checked);
	});
</script>    
</body>
</html>