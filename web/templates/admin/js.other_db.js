<script type="text/javascript">
$(document).ready(function(){
    $("select[@name$=.other_db]").change(function(){
		if(this.value=="-1")
			__hide();
		else
			__show();
    });
	__onload_test();
});
function __hide()
{
	$("input[@name$=.host]:text,input[@name$=.pass]:text,input[@name$=.user]:text").val("");
	$("input[@name$=.host]:text,input[@name$=.pass]:text,input[@name$=.user]:text").parent().parent().hide();
}
function __show()
{
	$("input[@name$=.host]:text,input[@name$=.pass]:text,input[@name$=.user]:text").parent().parent().show();
}
function __onload_test()
{
	var odb = $("select[@name$=.other_db]").val();
	if (odb && (odb=="-1"))
        __hide();
};
</script>