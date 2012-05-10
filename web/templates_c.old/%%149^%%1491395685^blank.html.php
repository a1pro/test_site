<?php /* Smarty version 2.6.2, created on 2010-08-09 20:46:58
         compiled from admin/blank.html */ ?>
<?php require_once(_SMARTY_DIR . 'core' . DIRECTORY_SEPARATOR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'date_format', 'admin/blank.html', 113, false),array('modifier', 'replace', 'admin/blank.html', 132, false),array('modifier', 'default', 'admin/blank.html', 140, false),array('modifier', 'number_format', 'admin/blank.html', 156, false),array('modifier', 'escape', 'admin/blank.html', 227, false),)), $this); ?>
<?php $this->assign('title', 'Welcome to aMember Pro Control Panel'); ?>

<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "admin/header.inc.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
<!--<?php echo ' --><style type="text/css">
#template_url_error, #root_url_error, #root_surl_error {
    display: none;
    position: absolute;
    margin-left: 10%;
    margin-right: 10%;
    padding: 10px 10px 10px 10px;
    border: solid 5px red;
    background-color: #fdd;
}
#root_url_error {
    z-index: 100;
}
#root_surl_error {
    z-index: 90;
}
}
.small_link {
    font-size: 8pt; 
    font-family: Arial;
}
xmp {
    padding-bottom: 0px;
}
</style><!--{literal} '; ?>
-->

<div id="msg" style="width:100%; height: 3em; position: absolute; 
 	left: 0px; top: 1em; padding-left: 20%; padding-top: 1em;
 	font-weight: bold; font-family: helvetica;
    font-size: 10pt;
 	display: none;
	background-color: #D1FF5F;"></div>


<div id="root_url_error" ></div>
<div id="root_surl_error" ></div>
<div id="template_url_error">
<h3>-title- Configuration Problem</h3>
Looks like <b>-title-</b> is configured incorrectly at 
<a href="setup.php">aMember CP->Setup</a>. Current value
is 
<xmp>-current_url-</xmp>
which does not seem to be valid. At least accessing the login URL
(<a href="-current_url-/login.php" target=_blank class="small_link">try it</a>)
<xmp>-current_url-/login.php</xmp> 
results to HTTP Error <span style='color: red'>(-error_code-/-error_text-)</span>. 
<p>As we can guess correct -title- is 
<xmp>-guess_url-</xmp> 
<a href="#" onclick="setRootUrl('-id-', '-title-', '-guess_url-')">Click here</a> 
to set -title- into this value in the config records.
</p>
<br />
<a href="#" onclick='$("#-id-_error").hide();' class="small_link">Close this warning</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="#" onclick='urlCheckDisable()' class="small_link">Disable Root URL checking completely</a>
</div>



<h3><br /><br /><?php echo $this->_tpl_vars['title']; ?>
</h3>
<center>
<br />



<?php if ($this->_tpl_vars['warnings']): ?>
<table bgcolor=red border=0 cellpadding=4 width=90%><tr><td align="left">
<?php if (count($_from = (array)$this->_tpl_vars['warnings'])):
    foreach ($_from as $this->_tpl_vars['e']):
?>
<li><b><?php echo $this->_tpl_vars['e']; ?>
</b>
<?php endforeach; unset($_from); endif; ?>
</td></tr></table>
<?php endif; ?>

<table bgcolor=#CCCCFF border=0 cellpadding=4 width=90% 
    id="version_table" style="display: none;">
<tr><td align="left">
<div id="version_check"></div>
</td></tr></table>

<table width=90% align=center cellpadding=4>
<tr>
    <td width=50% valign=top>
<div class=smallheader>Software version info</div>
<table class=infotbl border=1>
<tr>
    <td align=right>&nbsp;aMember </td>
    <td> <b><?php echo $this->_tpl_vars['config']['version']; ?>
</b></td>
</tr>
<tr>
    <td align=right>&nbsp;PHP </td>
    <td> <b><?php print phpversion() . " (".php_sapi_name().")";  ?></b></td>
</tr>
<tr>
    <td align=right>OS </td>
    <td> <b><?php $u=substr(php_uname(),0,28);if (strlen($u)==28) $u="$u...";print $u; ?></b></td>
</tr>
<tr>
    <td align=right>&nbsp;MySQL </td>
    <td> <b><?php global $db; $q=$db->query("SELECT VERSION();");$x=mysql_fetch_row($q);print $x[0]; ?></b></td>
</tr>
<tr>
    <td align=right>Root Folder </td>
    <td> <b><?php echo $this->_tpl_vars['config']['root_dir']; ?>
</b></td>
</tr>
</td></tr></table>
    </td>
    <td width=50% valign=top>
    <div class=smalltext>Hello, <b><?php echo $_SESSION['amember_admin']['login']; ?>
</b>! You last
    logged in from <font color=red><?php echo $_SESSION['amember_admin']['last_ip']; ?>
 at <?php echo ((is_array($_tmp=$_SESSION['amember_admin']['last_login'])) ? $this->_run_mod_handler('date_format', true, $_tmp, $this->_tpl_vars['config']['time_format']) : smarty_modifier_date_format($_tmp, $this->_tpl_vars['config']['time_format'])); ?>
.</font> 
    </div>
    </td>
</tr>              
<tr><td colspan=2><br /></td></tr>
<tr><td width=50% valign=top>

<?php if ($_SESSION['amember_admin']['perms']['report'] || $_SESSION['amember_admin']['super_user']): ?>

<div class=smallheader>Payments for last 7 days</div>
<table class=infotbl width=95%>
<tr>
<th>Date</th>
<th colspan=2>Added</th>
<th colspan=2>Paid</th>
<th>&nbsp;</th>
</tr>
<?php if (count($_from = (array)$this->_tpl_vars['income'])):
    foreach ($_from as $this->_tpl_vars['i']):
?>
<?php $this->assign('i_added_count', ($this->_tpl_vars['i_added_count']+$this->_tpl_vars['i']['added_count'])); ?>
<?php $this->assign('tmp_i_added_amount', ((is_array($_tmp=$this->_tpl_vars['i']['added_amount'])) ? $this->_run_mod_handler('replace', true, $_tmp, ',', '') : smarty_modifier_replace($_tmp, ',', ''))); ?>
<?php $this->assign('i_added_amount', ($this->_tpl_vars['i_added_amount']+$this->_tpl_vars['tmp_i_added_amount'])); ?>
<?php $this->assign('i_completed_count', ($this->_tpl_vars['i_completed_count']+$this->_tpl_vars['i']['completed_count'])); ?>
<?php $this->assign('tmp_i_completed_amount', ((is_array($_tmp=$this->_tpl_vars['i']['completed_amount'])) ? $this->_run_mod_handler('replace', true, $_tmp, ',', '') : smarty_modifier_replace($_tmp, ',', ''))); ?>
<?php $this->assign('i_completed_amount', ($this->_tpl_vars['i_completed_amount']+$this->_tpl_vars['tmp_i_completed_amount'])); ?>
<tr>
<td align=right><a href="payments.php?beg_date=<?php echo $this->_tpl_vars['i']['date']; ?>
&end_date=<?php echo $this->_tpl_vars['i']['date']; ?>
&list_by=complete"><?php echo $this->_tpl_vars['i']['date_print']; ?>
</a></td>
<td id="infotblnum" width=10%><?php echo $this->_tpl_vars['i']['added_count']; ?>
</td>
<td id="infotblnum" width=15%><?php echo ((is_array($_tmp=@$this->_tpl_vars['config']['currency'])) ? $this->_run_mod_handler('default', true, $_tmp, "$") : smarty_modifier_default($_tmp, "$"));  echo $this->_tpl_vars['i']['added_amount']; ?>
</td>
<td id="infotblnum" width=10%><b><?php echo $this->_tpl_vars['i']['completed_count']; ?>
</b></td>
<td id="infotblnum" width=15%><b><?php echo ((is_array($_tmp=@$this->_tpl_vars['config']['currency'])) ? $this->_run_mod_handler('default', true, $_tmp, "$") : smarty_modifier_default($_tmp, "$"));  echo $this->_tpl_vars['i']['completed_amount']; ?>
</b></td>
<td nowrap>
<?php if ($this->_tpl_vars['i']['percent']): ?>
<table border=0 cellpadding=0 cellspacing=0>
<tr><td width=<?php echo $this->_tpl_vars['i']['percent']; ?>
 height=5 style='font-size:6px; background-color: #f0f000;'>&nbsp;</td>
<td style='font-size:8px; color: black;'><?php echo $this->_tpl_vars['i']['percent']; ?>
%</td>
</tr></table>
<?php endif; ?>
</td>
</tr>
<?php endforeach; unset($_from); endif; ?>
<tr>
<th align=right>Totals</th>
<th id="infotblnum"><?php echo $this->_tpl_vars['i_added_count']; ?>
</th>
<th id="infotblnum"><?php echo ((is_array($_tmp=@$this->_tpl_vars['config']['currency'])) ? $this->_run_mod_handler('default', true, $_tmp, "$") : smarty_modifier_default($_tmp, "$"));  echo number_format($this->_tpl_vars['i_added_amount'], 2, ".", ","); ?>
</th>
<th id="infotblnum"><?php echo $this->_tpl_vars['i_completed_count']; ?>
</th>
<th id="infotblnum"><?php echo ((is_array($_tmp=@$this->_tpl_vars['config']['currency'])) ? $this->_run_mod_handler('default', true, $_tmp, "$") : smarty_modifier_default($_tmp, "$"));  echo number_format($this->_tpl_vars['i_completed_amount'], 2, ".", ","); ?>
</th>
<th>&nbsp;</th>
</table> 

</td><td width=50% valign=top>

<div class=smallheader>Users total</div>
<table class=infotbl width=40% border=0>
<?php if (count($_from = (array)$this->_tpl_vars['users'])):
    foreach ($_from as $this->_tpl_vars['i']):
?>
<tr>
    <td align=right> <?php echo $this->_tpl_vars['i']['title']; ?>
</td>
    <td align=right><?php echo $this->_tpl_vars['i']['count']; ?>
 </td>
</tr>
<?php endforeach; unset($_from); endif; ?>
</table> 
<br />
<div class=smalltext>Error/debug log messages today: <a 
href="error_log.php"><?php echo $this->_tpl_vars['errors']; ?>
</a></div>
<div class=smalltext>Access log records today: <a 
href="access_log.php"><?php echo $this->_tpl_vars['access']; ?>
</a></div>

<?php endif; ?>

</td></tr></table>

<script language=JavaScript src="../includes/jquery/jquery.js" smarty="1"></script><!--<?php echo ' '; ?>
-->
<?php if (! $this->_tpl_vars['config']['disable_url_checking']): ?>
<!-- validate root_url and root_surl -->
<!--<?php echo ' --><script language="JavaScript">
    function urlCheckDisable(){
        if (!confirm(
            "aMember will for sure works incorrectly\\n" +
            "if Root URLs are configured wrong.\\n" +
            "Are you sure you want to disable\\n" +
            "automatic Root URL checking?"))
             return false;
        var opts = { \'do\' : \'ajax\'};
        opts[\'disable_url_checking\'] = 1;
        $.post(\'index.php\', opts);
        $("#root_url_error").hide();
        $("#root_surl_error").hide();
		$("#msg").append("Automatic URL checking has been disabled<br />").show();
    }
    function setRootUrl(tp, txtp, url){
        if (!confirm(\'Are you sure you want to change \' + txtp + "to new value\\n"
          + url + "?"))
             return false;
        var opts = { \'do\' : \'ajax\'};
        opts[tp] = url;
        $.post(\'index.php\', opts);
        $("#"+tp+"_error").hide();
		$("#msg").append(txtp + " has been changed to " + url + "<br />").show();
        if (tp == \'root_surl\'){
            w = window;
            if (w.parent) w = w.parent;
            w.location = url + \'/admin/\';
        }
    }
    var tested = {};
    ';  
    $u  = $_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://';
    $u .= $_SERVER['HTTP_HOST'];
    $u .= preg_replace('|^(.+)/admin.*$|', '\\1', $_SERVER['PHP_SELF']);
    $u = preg_replace('|[^a-zA-Z0-9/:.,_+-]|', '', $u);
    print "var guess_url = '$u'";
      echo '
    function urlError(txtp, tp, req, status, error){
        console.log(tp, req, status, error);
        var current_url = tp == \'root_surl\' ? 
            "';  echo ((is_array($_tmp=$this->_tpl_vars['config']['root_surl'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'javascript') : smarty_modifier_escape($_tmp, 'javascript'));  echo '" : 
            "';  echo ((is_array($_tmp=$this->_tpl_vars['config']['root_url'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'javascript') : smarty_modifier_escape($_tmp, 'javascript'));  echo '" ;
        html = $("#template_url_error").html();
        html = html.replace(/-title-/g, txtp)
        .replace(/-current_url-/g, current_url)
        .replace(/-guess_url-/g, guess_url)
        .replace(/-error_code-/g, req.status)
        .replace(/-error_text-/g, req.statusText)
        .replace(/-id-/g, tp)
        ;
        $("#" + tp + "_error").html(html);
        $("#" + tp + "_error").show();
    }
    function urlTestCalled(data){
        if (data == \'file\') {
            $.ajax({\'url\' :\'';  echo ((is_array($_tmp=$this->_tpl_vars['config']['root_url'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'javascript') : smarty_modifier_escape($_tmp, 'javascript'));  echo '/login.php?_test_=root_url\',
                \'dataType\' : \'text\',
                \'error\' : function(req, status, error){
                    urlError(\'Root URL\', \'root_url\', req, status, error);
                }
            });
            $.ajax({\'url\' :\'';  echo ((is_array($_tmp=$this->_tpl_vars['config']['root_surl'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'javascript') : smarty_modifier_escape($_tmp, 'javascript'));  echo '/login.php?_test_=root_surl\',
                \'dataType\' : \'text\',
                \'error\' : function(req, status, error){
                    urlError(\'Secure Root URL\', \'root_surl\', req, status, error);
                }
            });
        }
    }
    $(document).ready(function() {
        $.get("../login.php?_test_=file", urlTestCalled);
    });
</script><!--{literal} '; ?>
-->
<?php endif; ?>

<?php if ($this->_tpl_vars['config']['dont_check_updates'] != '1'): ?>
<script language=JavaScript src="https://www.amember.com/version.php?v=#$config.version#&pv=<?php echo @constant('PHP_VERSION'); ?>
&pi=<?php echo $this->_tpl_vars['pi']; ?>
" smarty=1></script><!--<?php echo ' '; ?>
-->

<?php if (defined ( 'AMEMBER_LICENSE_EXPIRES_SOON' )): ?>
<!--<?php echo ' --><script language="JavaScript">
function handleNewLicense(json){
    if (json.license){        
        $.post(\'setup.php\', { license : json.license, notebook: \'License\', save: 1});
		$("#msg").append("Your temporary license key has been automatically replaced to lifetime license. \\
		<a href=\'#\' onclick=\'window.location.reload();\' style=\'font-size: 8pt;\'>Hide this message</a>").show(); 
    }
}
</script><!--{literal} '; ?>
-->
<script language=JavaScript src="https://www.amember.com/amember/get_lifetime_license.php?v=#$config.version#&license=<?php echo ((is_array($_tmp=$this->_tpl_vars['config']['license'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'url') : smarty_modifier_escape($_tmp, 'url')); ?>
" smarty=1></script><!--<?php echo ' '; ?>
-->
<?php endif; ?>
<?php endif; ?>


<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "admin/footer.inc.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>