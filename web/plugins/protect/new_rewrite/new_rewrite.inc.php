<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
	die("Direct access to this location is not allowed");

function amember_nr_create_files($cookie)
{
	global $config;
	
	foreach ($_SESSION['_amember_product_ids'] as $pid)
	{
		$file_to_create = preg_replace('/\W+/', '', $cookie) . '-' . $pid;
		$f = @fopen("$config[root_dir]/data/new_rewrite/$file_to_create", 'w');
		if (!$f) {
			fatal_error("Cannot create session file: $file_to_create<br />
			Please chmod folder amember/data/new_rewrite/ to 777",1,1);
		}
		fclose($f);
	}
	if(defined("INCREMENTAL_CONTENT_PLUGIN"))
	foreach ($_SESSION['_amember_link_ids'] as $pid)
	{
		$file_to_create = preg_replace('/\W+/', '', $cookie) . '-l' . $pid;
		$f = @fopen("$config[root_dir]/data/new_rewrite/$file_to_create", 'w');
		if (!$f) {
			fatal_error("Cannot create session file: $file_to_create<br />
			Please chmod folder amember/data/new_rewrite/ to 777",1,1);
		}
		fclose($f);
	}
	
	if ($_SESSION['_amember_product_ids'])
	{  // if user is active
		$file_to_create = preg_replace('/\W+/', '', $cookie);
		$f = fopen("$config[root_dir]/data/new_rewrite/$file_to_create", 'w');
		if (!$f)
			fatal_error("Cannot create session file: $file_to_create<br />
			Please chmod folder amember/data/new_rewrite/ to 777",1,1);
		fclose($f);
	}
}

function amember_nr_set_cookie()
{
	if ($_COOKIE['amember_nr'] == '')
	{
		srand(time());
		$k = 'amember_nr';
		$v = md5(rand().$_SESSION['amember_login']);
		$tm = 0;
		$d = $_SERVER['HTTP_HOST'];
		if (preg_match('/([^\.]+)\.(org|com|net|biz|info)$/', $d, $regs))
		{
			setcookie($k,$v,$tm,"/",".{$regs[1]}.{$regs[2]}");
			if(defined("INCREMENTAL_CONTENT_PLUGIN"))
			setcookie('amember_ln',$v,$tm,"/",".{$regs[1]}.{$regs[2]}");
		} else {
			setcookie($k,$v,$tm,"/");
			if(defined("INCREMENTAL_CONTENT_PLUGIN"))
			setcookie('amember_ln',$v,$tm,"/");
		}
		$_COOKIE['amember_nr'] = $v;
		if(defined("INCREMENTAL_CONTENT_PLUGIN"))
		$_COOKIE['amember_ln'] = $v;
	}
}

function amember_nr_after_login()
{
	amember_nr_set_cookie();
	amember_nr_create_files($_COOKIE['amember_nr']);
}

function amember_nr_after_logout()
{
	if (($c = $_COOKIE['amember_nr']) == '') return;
	global $config;
	$d = opendir($dirname="$config[root_dir]/data/new_rewrite/");
	if (!$d) return;
	while ($f = readdir($d)){
		if ($f[0] == '.') continue;
		if (preg_match("/^$c/", $f)){
			@unlink("$dirname/$f");
		}			
	}
	closedir($d);
}

function amember_nr_cleanup_files()
{
	global $config;
	$d = opendir($dirname="$config[root_dir]/data/new_rewrite");
	if (!$d) return;
	while ($f = @readdir($d)){
		if ($f[0] == '.')		   continue;
		if ($f == '_vti_cnf')	continue;
		if ($f == 'CVS')	continue;
		if ($f == 'readme.txt')  continue;
		if ((time() - @filectime("$dirname/$f")) > 3 * 3600)
			@unlink("$dirname/$f");
	}
	closedir($d);
}

setup_plugin_hook('after_login', 'amember_nr_after_login');
setup_plugin_hook('after_logout', 'amember_nr_after_logout');
setup_plugin_hook('daily', 'amember_nr_cleanup_files');

?>