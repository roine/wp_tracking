<div class='wrap' style='margin:50px auto;width:20%'>
  <label for="uid">My UID</label>
  <input type="text" value='<?php echo get_option('jon_uid'); ?>' id='uid' >
</div>
<script>
window.onload = function(){
  input = document.getElementById('uid');
  input.focus().select();
}

</script>
