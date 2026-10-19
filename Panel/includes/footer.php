   
    <footer class="app-footer">
        <div class="row">
        <div class="col-xs-12">
          <div class="footer-copyright">طراحی شده توسط <a target="_blank">ویزویز</a> با ❤️ + ☕️</div>
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
<script>
// var DEMO_MODE = true;

// if (DEMO_MODE) {
//     document.addEventListener("DOMContentLoaded", function () {

//         var elements = document.querySelectorAll("input, textarea, select, button");
//         for (var i = 0; i < elements.length; i++) {
//             elements[i].disabled = true;
//         }

//         var forms = document.querySelectorAll("form");
//         for (var j = 0; j < forms.length; j++) {
//             forms[j].addEventListener("submit", function(e){
//                 e.preventDefault();
//                 alert("این نسخه دمو است و امکان ثبت یا ویرایش وجود ندارد.");
//             });
//         }

//     });
// }
</script>
</body>
</html>