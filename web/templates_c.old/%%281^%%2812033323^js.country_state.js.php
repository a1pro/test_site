<?php /* Smarty version 2.6.2, created on 2010-11-16 06:22:52
         compiled from js.country_state.js */ ?>
<?php require_once(_SMARTY_DIR . 'core' . DIRECTORY_SEPARATOR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('function', 'root_url', 'js.country_state.js', 29, false),)), $this); ?>
<!--<?php echo ' --><script type="text/javascript">

';  
	require_once INC_DIR . '/pear/Services/JSON.php';
    $j = & new Services_JSON();
    echo "var statesCache = {};\n";
    foreach (array('US', 'CA') as $c)
        echo "statesCache.".$c." = " . $j->encodeUnsafe(db_getStatesForCountry($c)) . ";\n";
  echo '


function changeStates(obj) {

        var country = obj.options[obj.selectedIndex].value;
        var nm = (obj.name == \'cc_country\') ? \'#f_cc_state\' : \'#f_state\';
        $(nm).removeOption(/.|^$/).
        addOption(\'\', \'#_TPL_COMMON_SELECT_STATE#\');
        
        if (statesCache[country]){
            $(nm).addOption(statesCache[country]).selectOptions(\'\', true);
            onStatesLoaded();
        } else {
            onStatesLoaded();
            $(nm).attr(\'selectedIndex\', -1);
            $(nm).ajaxAddOption("';  echo smarty_function_root_url(array(), $this); echo 'ajax.php", 
                {"do" : "get_states", "country" : country}, false, onStatesLoaded);
        }
}

//select states in drop down list
function selectStates(obj) {
        var nm = (obj.name == \'cc_country\') ? \'#f_cc_state\' : \'#f_state\';
        var nmt = (obj.name == \'cc_country\') ? \'#t_cc_state\' : \'#t_state\';
        var selected=$(nmt)[0].value;
        if (selected!=\'\') {       
            tmp=nm+" > option[@value=\'"+selected+"\']";
            $(tmp).attr("selected", "selected");
        }
}

$(document).ready(function(){
    $("#f_country, #f_cc_country").change(function(){
        changeStates(this);
    });
       
    onStatesLoaded();
    
    $("#f_country, #f_cc_country").each(function(){
        changeStates(this);
    });
});


function onStatesLoaded(){
    // this function called after completion of Ajax or after changing 
    // state list options
    // we will display text box instead of selectBox if no states found
    selObj = $("#f_state")[0];
    if (selObj){
        if (selObj.options.length <= 1){
            $("#f_state").hide().attr("disabled", true).attr(\'_required\', false);
            $("#t_state").show().attr("disabled", false).attr(\'_required\', true);
        } else {
            $("#f_state").show().attr("disabled", false).attr(\'_required\', true);;
            $("#t_state").hide().attr("disabled", true).attr(\'_required\', false);;
        }
    }
    selObj = $("#f_cc_state")[0];
    if (selObj){
        if (selObj.options.length <= 1){
            $("#f_cc_state").hide().attr("disabled", true);
            $("#t_cc_state").show().attr("disabled", false);
        } else {
            $("#f_cc_state").show().attr("disabled", false);
            $("#t_cc_state").hide().attr("disabled", true);
        }
    }  
    
    $("#f_country, #f_cc_country").each(function(){
        selectStates(this);
    });     
}
</script><!--{literal} '; ?>
-->