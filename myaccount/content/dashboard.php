<div class="row">
    <h3 id="welcome"></h3>
    <h5>This area gives you access to your purchase history and account details.</h5>
</div>
<script>
    $(function(){
       var name = WPOS.getUser().name;
       $("#welcome").text('Hi '+name+', Welcome to your '+WPOS.getConfigTable().general.bizname+' Account.');
       WPOS.util.hideLoader();
    });
</script>