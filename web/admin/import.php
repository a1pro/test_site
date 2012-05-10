<?php				   
/*
*
*
*	 Author: Alex Scott
*	  Email: alex@cgi-central.net
*		Web: http://www.cgi-central.net
*	Details: Admin Payments
*	FileName $RCSfile: import.php,v $
*	Release: 3.1.9PRO ($Revision: 3944 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*																		  
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

include "../config.inc.php";
$t = new_smarty();
include "login.inc.php";
@set_time_limit(3600);
admin_check_permissions('import');

check_lite();

class Upload {
	var $FileName;
	var $FileSize;
	var $FileType;
	var $FSName;
	var $RelativePath;
	var $FullPath;
	var $Path;
	var $TmpName;
	
	var $RootDir;
	var $RenameIfExists;
	var $Error;
	
	var $AllowUploads;
	var $TmpDir;
	var $PostMaxSize;
	var $UploadMaxSize;
	var $MemoryLimit;
	var $UploadedFiles;
	
	function Upload($rootdir='')
	{
		global $config;
		if (!$rootdir)
			$this->RootDir = $config['root_dir']."/data";
		
		$this->RootDir = substr($rootdir,-1) == "/" ? substr($rootdir,0,-1) : $rootdir;
		if (!(@is_dir($this->RootDir)) || !(@file_exists($this->RootDir)) || !(@is_writable($this->RootDir)))
			$this->Error = "aMember 'data' folder doesn't exist or not writable";
		
		$this->AllowUploads = @ini_get('file_uploads');
		$this->TmpDir = @ini_get('upload_tmp_dir');
		$this->PostMaxSize = @ini_get('post_max_size');
		$this->UploadMaxSize = @ini_get('upload_max_filesize');
		$this->MemoryLimit = @ini_get('memory_limit');
		$this->RenameIfExists = 1;
	}

	function SetPath($relativepath='')
	{
		$this->RelativePath = substr($relativepath,-1) == "/" ? substr($relativepath,0,-1) : $relativepath;
		$this->Path = $this->RootDir . ($this->RelativePath ? "/".$this->RelativePath : "");
				
		if (!is_dir($this->Path) || !(@file_exists($this->Path)) || !(@is_writable($this->Path)))
			$this->Error = "aMember 'data' folder doesn't exist or not writable";
	}
	
	function SaveFile($fieldname='file')
	{
		if (!$this->Path) // trying to set path if not set
			$this->SetPath();
			
		if ($this->Error) return false;
		
		if (!$_FILES[$fieldname])
			$this->Error = "Please specify upload file for import";

		if ($this->Error) return false;
		
		settype($_FILES[$fieldname]['name'],"array");
		settype($_FILES[$fieldname]['size'],"array");
		settype($_FILES[$fieldname]['type'],"array");
		settype($_FILES[$fieldname]['tmp_name'],"array");
		
		foreach ((array)$_FILES[$fieldname]["error"] as $key => $error)
		{
			if ($error == 0 && $_FILES[$fieldname]['name'][$key])
			{
				$this->FileName = $_FILES[$fieldname]['name'][$key];
				$this->FileSize = $_FILES[$fieldname]['size'][$key];
				$this->FileType = $_FILES[$fieldname]['type'][$key];
				$this->TmpName = $_FILES[$fieldname]["tmp_name"][$key];
				if (!(@is_uploaded_file($this->TmpName)))
				{
					$this->Error = "Please specify upload file for import";
				}
				// change file name to good name
				$this->SetFSName();
				if ($this->Error) return false;
				$this->FullPath = $this->Path ."/". $this->FSName;
				if (@move_uploaded_file($this->TmpName, $this->FullPath))
				{
					@chmod($this->FullPath, 0666);
					$this->UploadedFiles[] = array('filename' => $this->FileName,
										'fullpath' => $this->FullPath,
									'relativepath' => $this->RelativePath,
											'path' => $this->Path,
										  'fsname' => $this->FSName,
											'size' => $this->FileSize,
											'type' => $this->FileType);
				} else {
					$this->Error = "Warning. Cannot move uploaded file!";
					return false;
				}
			} else {
				$this->Error = $error;
				return false;
				// add error handling
			}
		}
		return $true;
	}
	
	function SetFSName()
	{
		if ($this->Error)
			return false;
		if (!$this->FileName)
		{
			$this->Error = "File name is empty";
			return false;
		}
		if (!preg_match("/^[a-zA-Z0-9~`!@#$%^&()_\-+=;'.]+$/",$this->FileName)) // need new name
			$this->FSName = $this->GetFSName(1);
		else
			$this->FSName = $this->GetFSName();
	}
	
	function GetFSName($generate=0)
	{
		$extension = substr(strrchr($this->FileName, "."),1);
		
		if ($generate)
		{
			if (!$extension)
				$extension = "unq";
			$maxTries = 10;
			while(1)
			{
				$filename = substr(md5(uniqid(rand())),0,8).".".$extension;
				if (!(@file_exists($this->Path."/".$filename)))
					break;
				$maxTries--;
				if ($maxTries <= 0)
				{
					$this->Error = "File with such name aready exists, cannot generate file name";
					break;
				}
			}
		} else {
			if ($this->RenameIfExists)
			{
				if (!($name = substr($this->FileName,0,-strlen(strrchr($this->FileName, ".")))))
					$name = $this->FileName;

				$maxTries = 10;
				$i=1;
				while(1)
				{
					if (@file_exists($this->Path."/".$this->FileName))
							$this->FileName = $extension ? $name."($i).".$extension : $name."($i)";
					else
						break;
					$maxTries--;
					$i++;
					if ($maxTries <= 0)
					{
						$this->Error = "File with such name aready exists, cannot rename file";
						break;
					}
				}
				$filename = $this->FileName;
			} else {
				if (@file_exists($this->Path."/".$this->FileName))
				{
					$filename = $this->FileName;
					$this->Error = "File with such name aready exists";
				}
			}
		}
		return $filename;
	}
}

function import_upload()
{
	global $t, $db, $config, $vars;
	$title = "Import. Step 1 of 3 ";
	
	if (!$_SESSION['import_file_name'])
		$_SESSION['import_file_name'] = array();
	
	if (!$vars['use_uploaded'] && $vars['action'] == 'upload')
	{
		$upload = new Upload($config['root_dir']."/data");
		if (!$upload->Error)
		{
			$upload->SaveFile();
			if (!$upload->Error)
			{
				$file_for_import = $upload->UploadedFiles[0]['fullpath'];
				if (is_array($_SESSION['import_file_name'])
				&& !in_array($file_for_import, $_SESSION['import_file_name']))
					$_SESSION['import_file_name'][] = $file_for_import;
				$do_next = 1;
			} else {
				$err[] = $upload->Error;
			}
		} else {
			$err[] = $upload->Error;
		}
	} elseif ($vars['use_uploaded'] && $vars['action'] == 'upload') {
		if ($vars['use_uploaded'] == 'imp.csv' && @is_file($config['root_dir']."/admin/imp.csv") && @is_readable($config['root_dir']."/admin/imp.csv"))
		{
			$file_for_import = $config['root_dir']."/admin/imp.csv";
			if (is_array($_SESSION['import_file_name'])
			&& !in_array($file_for_import, $_SESSION['import_file_name']))
				$_SESSION['import_file_name'][] = $file_for_import;
			$do_next = 1;	
		} elseif (@is_file($config['root_dir']."/data/".basename($vars['use_uploaded']))) {
				$file_for_import = $config['root_dir']."/data/".basename($vars['use_uploaded']);
				if (is_array($_SESSION['import_file_name'])
				&& !in_array($file_for_import, $_SESSION['import_file_name']))
					$_SESSION['import_file_name'][] = $file_for_import;
				$do_next = 1;	
		} elseif (@is_file($config['root_dir']."/".basename($vars['use_uploaded']))) {
				$file_for_import = $config['root_dir']."/".basename($vars['use_uploaded']);
				if (is_array($_SESSION['import_file_name'])
				&& !in_array($file_for_import, $_SESSION['import_file_name']))
					$_SESSION['import_file_name'][] = $file_for_import;
				$do_next = 1;	
		} elseif (@is_file($vars['use_uploaded'])) {
				$file_for_import = $vars['use_uploaded'];
				if (is_array($_SESSION['import_file_name'])
				&& !in_array($file_for_import, $_SESSION['import_file_name']))
					$_SESSION['import_file_name'][] = $file_for_import;
				$do_next = 1;	
		} else {
			$err[] = "Import file not found.";
		}
	}

	if (!$vars['delim'] && $_SESSION['import_file_delim'])
		$vars['delim'] = $_SESSION['import_file_delim'];
	if ($vars['delim'])
		$_SESSION['import_file_delim'] = $vars['delim'];

	if (!$err && $do_next)
	{
		if (!($f = @fopen($file_for_import, 'r')))
		{
			$err[] = "Cannot open file '$file_for_import' for import";
			@fclose($f);
		} else {
			$line = fgets($f, 4096);
			if (preg_match("/\n/", $line) && preg_match("/\r/", $line))
			{
				$l = $line;
			} elseif (preg_match("/\r/", $line)) {
				$l = preg_split("/\r/", $line);
				$l = $l[0];
			} else {
				$l = $line;
			}
			$l = explode($vars['delim'], $l);
			@fclose($f);
			if (count($l) >= 2)
			{
				import_select_fields($file_for_import, $vars['delim'], $l);
			} else {
				$err[] = "Fields not found or number of fields < 2";
			}
		}
	}
	
	if ($err)
	{
		$t->assign('error', $err);
		$title .= "Error : ".$err[0];
	} else {
		$title .= ": Select import file";
	}
	
	$t->assign('delim', $vars['delim'] ? $vars['delim'] : $_SESSION['import_file_delim']);
	$t->assign('previous_files', $_SESSION['import_file_name']);
	if (count($_SESSION['import_file_name']) == 1)
		$t->assign('preselected_file', $_SESSION['import_file_name'][0]);
	$t->assign('title', $title);
	
	$t->display('admin/import_upload.html');	
}

function import_select_fields($file_for_import, $delim, $first_row)
{
	global $t, $db, $vars, $input_fields, $fixed_input_fields, $member_additional_fields;
	
	$check_fields_array = array_merge($input_fields, $fixed_input_fields);
	foreach ($check_fields_array as $field_name)
	{
		$predefined_fields[$field_name] = $_SESSION['predefined_fields'][$field_name];
	}
	
	$fields_max = count($first_row);
	for ($i=0;$i<$fields_max;$i++)
		$fields_list[ 'FIELD-' . $i ] = $first_row[$i];
	$t->assign('fields', $fields_list);
	$t->assign('fields_gen', array('GENERATE' => 'Generate','' => '-------------------') + $fields_list);
	$t->assign('fields_gen_fixed', array('GENERATE' => 'Generate', 'FIXED' => 'Fixed','' => '-------------------') + $fields_list);
	$t->assign('fields_emp', array('' => '-- Please select --') + $fields_list);
	$products = array();
	foreach ($db->get_products_list() as $p)
		$products[ $p['product_id'] ] = $p['title'];
	$t->assign('fields_prod', array("" => "-- Please select --", "EMPTY" => "Don't add subscription") + $products + array("-" => "-------------------") + $fields_list);
	$t->assign('fields_emp_fixed', array('' => '-- Please select --', 'FIXED' => 'Fixed - please enter','-' => '-------------------') + $fields_list);

	$ps_list = array();
	foreach (get_paysystems_list() as $k => $ps)
		$ps_list[ $ps['paysys_id'] ] = $ps['title'];
	$t->assign('fields_ps', array('' => '-- Please select --') + $ps_list + array('-' => '-------------------') + $fields_list);
	
	$title = "Import. Step 2 of 3 : Select fields";
	$t->assign('title', $title);
	$t->assign('delim', $delim);
	$t->assign('file_for_import', $file_for_import);
	$t->assign('predefined_fields', $predefined_fields);
	$t->assign('member_additional_fields',$member_additional_fields);
	$t->display('admin/import.html');	
	exit;
}

function import_csv($file_for_import='')
{
	global $t, $vars, $import_begin_time, $total_added, $csv_duplicate_logins, $amember_duplicate_logins, $input_fields, $fixed_input_fields, $member_additional_fields;
	
	$check_fields_array = array_merge($input_fields, $fixed_input_fields);
	foreach ($check_fields_array as $field_name)
	{
		$_SESSION['predefined_fields'][$field_name] = $vars[$field_name];
	}
	
	$delim = $vars['delim'];
	if (!$file_for_import)
		$file_for_import = $vars['file_for_import'];
	
	if (!(check_form()))
	{
		if ($f = @fopen($file_for_import, 'r'))
		{
			$line = fgets($f, 4096);
			if (preg_match("/\n/", $line) && preg_match("/\r/", $line))
			{
				$l = $line;
			} elseif (preg_match("/\r/", $line)) {
				$l = preg_split("/\r/", $line);
				$l = $l[0];
			} else {
				$l = $line;
			}
			$l = explode($delim, $l);
			@fclose($f);
			import_select_fields($file_for_import, $delim, $l);
			exit;
		} else {
			import_upload();
			exit;
		}
	}
	
	if ($f = @fopen($file_for_import, 'r'))
	{
		while ($line = fgets($f, 4096))
		{
			if (!$csv_new_line)
			{
				if (preg_match("/\n/", $line) && preg_match("/\r/", $line)) // normal \n file
				{
					$csv_new_line = 'n';
					$line_array = explode($delim, $line);
					import_import($line_array);
				} elseif (preg_match("/\r/", $line)) { // bad \r file
					$csv_new_line = 'r';
					$new_line_lastpos = strrpos($line, "\r");
					$good_line = substr($line, 0, $new_line_lastpos);
					$tmp_line = substr($line, $new_line_lastpos);
					if (substr($tmp_line, 0, 1) == "\r")
						$tmp_line = substr($tmp_line, 1);
					$tmp_line_array = preg_split("/\r/", $good_line);
					foreach ($tmp_line_array as $tmp_line_good)
					{
						$line_array = explode($delim, $tmp_line_good);
						import_import($line_array);
					}					
				} else {
					$line_array = explode($delim, $line);
					import_import($line_array);
				}
			} else {
				if ($csv_new_line == 'r')
				{
					if ($tmp_line)
					{
						$new_line_firstpos = strpos($line, "\r");
						$tmp_line .= substr($line, 0, $new_line_firstpos-1);
						$line_array = explode($delim, $tmp_line);
						import_import($line_array);
						$new_line_lastpos = strrpos($line, "\r");
						$good_line = substr($line, $new_line_firstpos, $new_line_lastpos-1);
						$tmp_line_array = preg_split("/\r/", $good_line);
						foreach ($tmp_line_array as $tmp_line_good)
						{
							$line_array = explode($delim, $tmp_line_good);
							import_import($line_array);
						}
						$tmp_line = substr($line, $new_line_lastpos);
						if (substr($tmp_line, 0, 1) == "\r")
							$tmp_line = substr($tmp_line, 1);
					}
				} else {
					$line_array = explode($delim, $line);
					import_import($line_array);
				}
			}
		}
		@fclose($f);
		if ($tmp_line)
		{
			$line_array = explode($delim, $tmp_line);
			import_import($line_array);
		}
		import_import(array(),1);
	} else {
		import_upload();
		exit;	
	}
	$title = "Import Finished";
	$import_took_time = time() - $import_begin_time;
	$t->assign('title', $title);
	$t->assign('total_added', $total_added);
	$t->assign('csv_duplicate_logins', $csv_duplicate_logins);
	$t->assign('amember_duplicate_logins', $amember_duplicate_logins);
	$t->assign('import_took_time',$import_took_time);
	$t->assign('file_for_import',$file_for_import);
	admin_log("Import users");
	$t->display('admin/import_finished.html');	
	exit;
}

function import_multiple_cached($record, $run_import=0)
{
	global $db, $total_added, $csv_duplicate_logins, $amember_duplicate_logins, $import_members_cache, $import_payment_cache;
	global $member_additional_fields, $import_member_fields, $import_payment_fields, $import_insert_member_fields, $import_insert_payment_fields;
	
	$cache_limit = 500;
	$mll = 32; //maximum login length - defautl 32
	$data = array();
	// CACHE RECORDS
	$record_login = substr($record['login'],0,$mll);
	if ($record && $record_login && (count($import_members_cache) < $cache_limit || $run_import))
	{
		if (!$import_members_cache[$record_login])
		{
			// additional_fields
			foreach ($member_additional_fields as $field)
			{
				$k = $field['name'];
				$default = ((!$field['display_signup']) && ($field['default'] != '') && !isset($record[$k])) ? $field['default'] : null;
				if (!is_null($default)) $record[$k] = $default;
				if (!$field['sql'] && isset($record[$k]))
					if(($field['type'] == "multi_select") || ($field['type'] == "checkbox"))
						$data[$k] = (array)split("\^",$record[$k]);
					else
						$data[$k] = $record[$k];
			}
			$data = & $db->encode_data($data);
			$data = $db->escape($data);
			
			$record_escaped = & $db->escape_array($record);
			
			// MEMBER FIELDS
			$insert_values = '';
			foreach ($import_member_fields as $field_name)
			{
				if ($record_escaped[$field_name])  //PREVENT 'ARRAY' in VALUE
					$insert_values .= "'".$record_escaped[$field_name]."', ";
				else
					$insert_values .= "'', ";
			}
			$insert_values .= "NOW(), '$_SERVER[REMOTE_ADDR]', '$data', '$record_escaped[aff_id]'";
			$import_members_cache[$record_login] = "(".$insert_values.")";
			
			// PAYMENT FIELDS
			$insert_values = '';
			foreach ($import_payment_fields as $field_name)
			{
				if ($field_name == 'member_id')
					continue;
				if ($field_name == 'amount')
				{
					$insert_values .= "'".($record_escaped[$field_name] ? $record_escaped[$field_name] : 0)."', ";
				} else {
					$insert_values .= "'".$record_escaped[$field_name]."', ";
				}
			}
			$insert_values .= "'', NOW(), NOW(), NOW()";
			$import_payment_cache[$record_login] = $insert_values.")";
		} else {
			// duplicate logins in CSV file!
			if (!in_array($record_login,$csv_duplicate_logins))
				$csv_duplicate_logins[] = $record_login;
		}
	}
	// INSERT RECORDS
	if (count($import_members_cache) >= $cache_limit || $run_import)
	{
		// check if cache exists
		if (count($import_members_cache) <= 0)
			return;
		if (!isset($import_insert_member_fields))
		{
			foreach ($import_member_fields as $field_name)
				$import_insert_member_fields .= $field_name.", ";
			$import_insert_member_fields .= "added, remote_addr, data, aff_id";
		}
		// check for duplicate logins in aMember database
		$duplicate_logins = array();
		$rs = $db->query($s = "SELECT UPPER(login) FROM {$db->config['prefix']}members WHERE login IN ('".implode("','", array_keys($import_members_cache))."')");
                while (list($duplicate_login) = mysql_fetch_row($rs))
		{
			$duplicate_logins[] = $duplicate_login;
		}
		mysql_free_result($rs);

                $count_records = 0;
		// REMOVE DUPLICATES
		if ($duplicate_logins)
		{
			foreach ($import_members_cache as $login => $record_data)
			{
				if (in_array(strtoupper($login), $duplicate_logins)) // duplicate logins in aMember database!
				{
					if (!in_array($login, $amember_duplicate_logins))
						$amember_duplicate_logins[] = $login;
					// REMOVE FROM CACHE  
					unset($import_members_cache[$login]);
					unset($import_payment_cache[$login]);
				} else {
					$count_records++;
				}
			}
		} else {
			$count_records = count($import_members_cache);
		}
		unset($duplicate_logins);
		// let's insert
		if ($count_records > 0)
		{
			$insert_query = "INSERT INTO {$db->config['prefix']}members ($import_insert_member_fields) VALUES ";
			$insert_query .= implode(",", $import_members_cache);
			$first_member_id = 0;
			// let's detect last member_id by auto_increment_id first
			$table_status = $db->query_first("SHOW TABLE STATUS FROM `".$db->config['db']."` LIKE '".$db->config['prefix']."members'");
			if ($table_status['Auto_increment'] > 0)
			{
				$first_member_id = $table_status['Auto_increment'];
			} else {
				// let's try to add one member and delete it after
				srand(time());
				$rand_login = "get_last_id_for_import".rand(100, 999);
				$db->query("INSERT INTO {$db->config['prefix']}members SET login = '$rand_login'");
				if ($first_member_id = mysql_insert_id($db->conn))
				{
					$db->query("DELETE FROM {$db->config['prefix']}members WHERE member_id='$first_member_id'");
					$first_member_id++;
				} else {
					if ($first_member_id = $db->query_one("SELECT member_id FROM {$db->config['prefix']}members WHERE login = '$rand_login'"))
					{
						$db->query("DELETE FROM {$db->config['prefix']}members WHERE member_id='$first_member_id'");
						$first_member_id++;
					} else {
						$first_member_id = 0;
					}
				}
			}
			if ($first_member_id)
			{
				// INSERT MEMBERS
				$db->query($insert_query);
				// CLEAR CACHE
				$import_members_cache = array();
				$insert_query = '';
				$total_added = $total_added + $count_records;
				// INSERT PAYMENT
				if (!isset($import_insert_payment_fields))
				{
					foreach ($import_payment_fields as $field_name)
						$import_insert_payment_fields .= $field_name.", ";
					$import_insert_payment_fields .= "data, time, tm_added, tm_completed";
				}
				$insert_query = "INSERT INTO {$db->config['prefix']}payments ($import_insert_payment_fields) VALUES ";
				foreach ($import_payment_cache as $login => $record_data)
				{
					$import_payment_cache[$login] = "('".$first_member_id."', ".$import_payment_cache[$login];
					$first_member_id++;
				}
				$insert_query .= implode(",", $import_payment_cache);
				// INSERT PAYMENT
				$db->query($insert_query);
				$insert_query='';
				// CLEAR CACHE
				$import_payment_cache = array();
			} else {
				print "<br /><strong>Import error. Cannot detect last member_id</strong><br />";
				exit;
			}
		}
	}
}

function import_import($line_array, $run_import=0)
{
	global $vars, $db, $t, $total_added, $lines_skipped, $records_count, $csv_total_records;

	// COUNT TOTAL RECORS FROM CSV FILE
	if ($line_array)
	{
		$csv_total_records++;
	} elseif (!$line_array && $run_import) { // THIS IS LAST RECORD - SO LET'S INSERT ALL CACHED
		import_multiple_cached($line_array, $run_import);
	}
	// SKIP RECORDS
	if (($vars['skip_lines'] && $lines_skipped < $vars['skip_lines']))
	{
		$lines_skipped++;
		return;
	}
	// LIMIT NUMBER OF RECORDS
	if ($vars['limit_records'] && $records_count >= $vars['limit_records'])
		return;
	
	// ADD RECORD TO CACHE
	$records_count++;
	$record = array_to_record($line_array);
	import_multiple_cached($record, $run_import);
}

function array_to_record($line_array)
{
	global $vars, $rev_fields, $db, $import_products_cache;

	if (!$rev_fields)
		$rev_fields = get_rev_fields($vars);

	$rec = array();
	foreach ($rev_fields as $fn => $nn)
		$rec[$fn] = trim($line_array[$nn]);
		
	if ($vars['login'] == 'GENERATE')
		$rec['login'] = generate_login();
	if ($vars['pass'] == 'FIXED')
		$rec['pass'] = $vars['pass_fixed'];
	if ($vars['pass'] == 'GENERATE')
		$rec['pass'] = generate_password();
	
	if ($rec['cc'])
	{
		$cc = preg_replace('/\D+/', '', $rec['cc']);
		$rec['cc-hidden'] = amember_crypt($cc);
		$rec['cc'] = get_visible_cc_number($cc);
		$cc='';
	}
	if ($rec['cc-expire'])
	{
		$rec['cc-expire'] = format_cc_expire($rec['cc-expire']);
	}
	if ($vars['product_id'] != 'EMPTY')
	{
		if (is_numeric($vars['product_id']))
		{
			$rec['product_id'] = $vars['product_id'];
		} else {
			if (!is_numeric($rec['product_id'])) // Sometimes import file contain Product Title - not ID
			{
				if (!isset($import_products_cache[$rec['product_id']]))
				{
					$product_title = $db->escape($rec['product_id']);
					if ($import_products_cache[$rec['product_id']] = $db->query_first("SELECT * FROM {$db->config['prefix']}products WHERE title = '$product_title'"))
						$rec['product_id'] = $import_products_cache[$rec['product_id']]['product_id'];
				} else {
					$rec['product_id'] = $import_products_cache[$rec['product_id']]['product_id'];
				}
			}
		}
		if ($vars['expire_date'] == 'FIXED')
			$rec['expire_date'] = $vars['expire_date_fixed'];
		if ($vars['begin_date'] == 'FIXED')
			$rec['begin_date'] = $vars['begin_date_fixed'];
		if ($vars['amount'] == 'FIXED')
			$rec['amount'] = $vars['amount_fixed'];
		if (!preg_match('/^FIELD-/', $vars['paysys_id']))
			$rec['paysys_id'] = $vars['paysys_id'];
		if ($vars['receipt_id'] == 'FIXED')
			$rec['receipt_id'] = $vars['receipt_id_fixed'];
		$rec['completed'] = intval($vars['is_completed']);
	}
	$rec['begin_date'] = convert_date($rec['begin_date']);
	$rec['expire_date'] = convert_date($rec['expire_date']);

	return $rec; 
}

function get_rev_fields($vars)
{
	//get vars, return $rev_fields
	global $input_fields;
	$fields = array();
	$max_num = 0;
	foreach ($input_fields as $k){
		if (preg_match('/^FIELD-(\d+)$/', $vars[$k], $regs))
		{
			$fields[$k] = $regs[1];
		}
	}
	return $fields;
}

function check_form()
{
	global $t, $vars;
	$err = array();
	if (!$vars['login'] || $vars['login'] == '-')
	{
		$err[] = "Please select something for Username field";
	}
	if (!$vars['pass'] || $vars['pass'] == '-')
	{
		$err[] = 'Please select something for Password field';
	} elseif (($vars['pass'] == 'FIXED') && (!$vars['pass_fixed'])) {
		$err[] = "Please enter fixed password";
	}
	if (!$vars['product_id'] || $vars['product_id'] == '-')
	{
		$err[] = "Please select something for Subscription Type";
	}
	if (($vars['product_id'] != 'EMPTY') && (!$vars['expire_date'] || $vars['expire_date'] == '-'))
	{
		$err[] = "Please select something for Expire Date";
	}
	if (($vars['begin_date'] == 'FIXED') && !$vars['begin_date_fixed'])
	{
		$err[] = "Please enter fixed Begin Date";
	}
	if (($vars['expire_date'] == 'FIXED') && !$vars['expire_date_fixed'])
	{
		$err[] = "Please enter fixed Expire Date";
	}
	if (!preg_match('/^(|\d\d\d\d\-\d\d\-\d\d)$/', $vars['expire_date_fixed']))
	{
		$err[] = "Incorrect Expire Date format. Use yyyy-mm-dd";
	}
	if (!preg_match('/^(|\d\d\d\d\-\d\d\-\d\d)$/', $vars['begin_date_fixed']))
	{
		$err[] = "Incorrect Begin Date format. Use yyyy-mm-dd";
	}
	if (($vars['amount'] == 'FIXED') && !$vars['amount_fixed'])
	{
		$err[] = "Please enter fixed Amount";
	}	
	if ($err)
	{
		$t->assign('error', $err);
		return 0;
	}
	return 1;
}

function convert_date($d)
{
	if ($d > 1000000) /// timestamp
		return date('Y-m-d', $d);
	else if (preg_match('/^\d{5}$/', $d)) //from mc_pro
		return date('Y-m-d', $d * 3600 * 24 + mktime(0,0,0,1,1,1970));
	else  // assume mysql yyyy-mm-dd
		return date('Y-m-d', strtotime($d));
}

function get_visible_cc_number($cc)
{
	$cc = preg_replace('/\D+/', '', $cc);
	return '**** **** **** '.substr($cc, -4);
}

function format_cc_expire($s)
{
	$s = preg_replace('/\D+/', '', $s);
	switch (strlen($s)){
		case 4: // mmyy 
			return $s;
		case 6: // mmyyyy
			return substr($s, 0, 2) . substr($s, 4, 2);
		default: return $s;
	}
}

/////////////// MAIN //////////////////////

$import_member_fields = array(
	'login', 'pass', 'email',
	'name_f', 'name_l',
	'street', 'city', 'state',
	'zip', 'country', 'is_male', 'is_affiliate',
	'aff_payout_type', 'unsubscribed',
	'email_verified'
);
$import_payment_fields = array(
	'member_id',
	'product_id', 'begin_date', 'expire_date',
	'paysys_id', 'receipt_id', 'amount',
	'completed'
);
$fixed_input_fields = array(
	'pass_fixed', 'begin_date_fixed', 'expire_date_fixed',
	'amount_fixed', 'receipt_id_fixed', 'skip_lines', 'limit_records'
);
$input_fields = array(
	'login', 'pass', 'email', 
	'name_f', 'name_l',
	'street', 'city',
	'state', 'zip', 
	'country', 'is_male',
	'product_id', 'expire_date', 'begin_date',
	'paysys_id', 'receipt_id',
	'amount'
);
foreach ($member_additional_fields as $field)
{
	if ($field['type'] != 'hidden')
		$input_fields[] = $field['name'];
	if ($field['sql'])
		$import_member_fields[] = $field['name'];
}

$csv_total_records = 0;
$total_added = 0;
$records_count = 0;
$import_product_cache = array();
$import_members_cache = array();
$import_payment_cache = array();
$csv_duplicate_logins = array();
$amember_duplicate_logins = array();
$vars = get_input_vars();
$import_begin_time = time();
switch ($vars['action'])
{
	case 'upload':
		check_demo();
		import_upload();
		break;
	case 'import':
		check_demo();
		import_csv();
		break;
	default:
		import_upload();
}


?>