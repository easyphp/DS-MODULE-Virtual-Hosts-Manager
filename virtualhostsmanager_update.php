<?php
/**
 * Virtual Hosts Manager for DEVSERVER
 * @version  1.8
 * @author   Laurent Abbal <laurent@abbal.com>
 * @link     https://www.easyphp.org
 */ 


include_once('virtualhostsmanager_functions.php');

//== ADD or MODIFY VITRTUAL HOST AND HOST NAME =========================================
if ((isset($_POST['to'])) AND ($_POST['to'] == "add_vhost")) {
	
	$get_vhost_modal = '&vhost_modal=' . $_POST['vhost_modal'];
	$get_vhost_name = '&add_vhost_name=' . htmlspecialchars(trim($_POST['vhost_name']));
	$get_vhost_link = '&add_vhost_link=' . urlencode(trim($_POST['vhost_link']));
	
	if ($_POST['vhost_name'] == "") {
		header("Location: " . 'http://' . $_SERVER["HTTP_HOST"] . '/index.php?to=add_vhost_error' . $get_vhost_modal . $get_vhost_name . $get_vhost_link . '&add_vhost_error=1');
		exit;
	}
	elseif ($_POST['vhost_link'] == "") {
		header("Location: " . 'http://' . $_SERVER["HTTP_HOST"] . '/index.php?to=add_vhost_error' . $get_vhost_modal . $get_vhost_name . $get_vhost_link . '&add_vhost_error=2');
		exit;
	}
	elseif (($_POST['vhost_link'] != "") && (!is_dir($_POST['vhost_link']))) {
		header("Location: " . 'http://' . $_SERVER["HTTP_HOST"] . '/index.php?to=add_vhost_error' . $get_vhost_modal . $get_vhost_name . $get_vhost_link . '&add_vhost_error=3');
		exit;
	}
	elseif (!preg_match('/^[-a-zA-Z0-9_.]+$/i', trim($_POST['vhost_name']))) {
		header("Location: " . 'http://' . $_SERVER["HTTP_HOST"] . '/index.php?to=add_vhost_error' . $get_vhost_modal . $get_vhost_name . $get_vhost_link . '&add_vhost_error=4');
		exit;
	}	
	
	$vhosts_serialized = file_get_contents('store_vhosts.php');
	if ($vhosts_serialized == '') $vhosts_serialized = "a:0:{}";
	$vhosts_array = unserialize($vhosts_serialized);

	
	// Test name unique
	$same_name = false;
	if ((isset($_POST['vhost_modal_status'])) and ($_POST['vhost_modal_status'] == "modify_vhost")) {
		if ($vhosts_array[$_POST['vhost_modal_nb']]['vhost_name'] == trim($_POST['vhost_name'])) {
			$same_name = true;
		}
	}
	
	if (!$same_name) {
		
		$vhosts_names = array_column($vhosts_array, 'vhost_name');
		if (in_array(trim($_POST['vhost_name']), $vhosts_names)) {
			header("Location: " . 'http://' . $_SERVER["HTTP_HOST"] . '/index.php?to=add_vhost_error' . $get_vhost_modal . $get_vhost_name . $get_vhost_link . '&add_vhost_error=5');
			exit;
		}		
		
	}
		
	// Cleaning
	$new_vhost[0]['vhost_status'] = 1;
	$new_vhost[0]['vhost_name'] = trim($_POST['vhost_name']);
	$new_vhost[0]['vhost_link'] = str_replace("\\","/", $_POST['vhost_link']);
	$new_vhost[0]['vhost_link'] = trim(str_replace("//","/", $new_vhost[0]['vhost_link']));
	if (substr($new_vhost[0]['vhost_link'], -1) == "/"){$new_vhost[0]['vhost_link'] = substr($new_vhost[0]['vhost_link'],0,strlen($new_vhost[0]['vhost_link'])-1);}
	$new_vhost[0]['vhost_link'] = urlencode($new_vhost[0]['vhost_link']);
	$new_vhost[0]['vhost_directory'] = trim($_POST['vhost_directory']);
	
	
	// NEW VHOST
	if ($_POST['vhost_modal_status'] == 'new_vhost') {
		
		$new_vhosts_array = array_merge($vhosts_array, $new_vhost);
		
		// Write store_vhosts.php
		file_put_contents('store_vhosts.php',serialize($new_vhosts_array));
		
		
		// WINDOWS HOSTS FILE
			// Create new hosts file
			$hostsfile_array = read_hostsfile('file');
			$new_hostfile_content = '';
			$vhost_exists = false;
			
			foreach ($hostsfile_array as $line) {
				if (stristr($line,trim($_POST['vhost_name'])) AND (stristr($line,"127.0.0.1"))) {
					$new_hostfile_content = $new_hostfile_content . "\n" . "127.0.0.1  " . trim($_POST['vhost_name']);
					$vhost_exists = true;
				} else {
					$new_hostfile_content = $new_hostfile_content . "\n" . $line;
				}
			}
			
			if (!$vhost_exists) {
				$new_hostfile_content = $new_hostfile_content . "\n" . "127.0.0.1  " . trim($_POST['vhost_name']);
			}
			
			// Save new hosts file
			file_put_contents(get_hostsfile_dir() . '\hosts', trim($new_hostfile_content));	

		// Restart http server if http server running
		if ($eds_httpserver_running == 1) include('../eds-binaries/httpserver/' . $conf_httpserver['httpserver_folder'] . '/eds-app-restart.php');
		
		header("Location: index.php#anchor_virtualhostsmanager");  
		exit;
		
	} 
	
	// MODIFY VHOST
	if ($_POST['vhost_modal_status'] == 'modify_vhost') {
			
		$old_vhost_name = $vhosts_array[$_POST['vhost_modal_nb']]['vhost_name'];
		$new_vhost_name = trim($_POST['vhost_name']);

		$vhosts_array[$_POST['vhost_modal_nb']] = $new_vhost[0];
		
		// Write store_vhosts.php
		file_put_contents('store_vhosts.php',serialize($vhosts_array));
		

		// WINDOWS HOSTS FILE
			// Create new hosts file
			$hostsfile_array = read_hostsfile('file');
			$new_hostfile_content = '';

			
			foreach ($hostsfile_array as $line) {
				if (stristr($line,$old_vhost_name) AND (stristr($line,"127.0.0.1"))) {
					$new_hostfile_content = $new_hostfile_content . "\n" . "127.0.0.1  " . $new_vhost_name;

				} else {
					$new_hostfile_content = $new_hostfile_content . "\n" . $line;
				}
			}
			
			// Save new hosts file
			file_put_contents(get_hostsfile_dir() . '\hosts', trim($new_hostfile_content));	

		// Restart http server if http server running
		if ($eds_httpserver_running == 1) include('../eds-binaries/httpserver/' . $conf_httpserver['httpserver_folder'] . '/eds-app-restart.php');
		
		header("Location: index.php#anchor_virtualhostsmanager");  
		exit;

	}
}
//============================================================================


//== ACTIVATE OR DESACTIVATE HOST NAME =======================================
if (isset($_GET['vhost_nb'])) {
	
	$vhosts_serialized = file_get_contents('store_vhosts.php');
	$vhosts_array = unserialize($vhosts_serialized);
	$hostsfile_array = read_hostsfile('file');	
	
	// Hosts file - change status
		$new_hostfile_content = '';
		foreach ($hostsfile_array as $line) {
			if ((stripos($line,"127.0.0.1") === false) OR (stripos($line,$vhosts_array[$_GET['vhost_nb']]['vhost_name']) === false)) {
				$new_hostfile_content .= $line . "\n";
			} else {
				if ($vhosts_array[$_GET['vhost_nb']]['vhost_status'] == 1) {
					$new_hostfile_content .= "#127.0.0.1  " . $vhosts_array[$_GET['vhost_nb']]['vhost_name'] . "\n";
				} else {
					$new_hostfile_content .= "127.0.0.1  " . $vhosts_array[$_GET['vhost_nb']]['vhost_name'] . "\n";
				}
			}
		}	

		// Save new hosts file
		file_put_contents(get_hostsfile_dir() . '\hosts', trim($new_hostfile_content));	
		
		
	// store_vhosts.php - change status

		$vhosts_array[$_GET['vhost_nb']]['vhost_status'] = ($vhosts_array[$_GET['vhost_nb']]['vhost_status'] == 1) ? 0 : 1;

		// Save store_vhosts.php
		file_put_contents('store_vhosts.php',serialize($vhosts_array));

		
	// Restart http server if http server running
	if ($eds_httpserver_running == 1) include('../eds-binaries/httpserver/' . $conf_httpserver['httpserver_folder'] . '/eds-app-restart.php');	
		
	header("Location: index.php#anchor_virtualhostsmanager");  
	exit;	
}
//============================================================================


//== DELETE VIRTUAL HOST AND HOST NAME =======================================
if (isset($_GET['vhost_delete'])) {
	
	$vhosts_serialized = file_get_contents('store_vhosts.php');
	$vhosts_array = unserialize($vhosts_serialized);	
	$hostsfile_array = read_hostsfile('file');	
	
	// Delete host in hosts file
	
		$new_hostfile_content = '';
		foreach ($hostsfile_array as $line) {
			if(((stripos($line,"127.0.0.1") === false) OR (stripos($line,$vhosts_array[$_GET['vhost_delete']]['vhost_name']) === false)) AND (strlen($line) != 0)) {
				$new_hostfile_content .= trim($line) . "\n";
			}
		}
		
		// Save new hosts file
		file_put_contents(get_hostsfile_dir() . '\hosts', trim($new_hostfile_content));	
	
	// Delete host in store_vhosts.php
	
		unset($vhosts_array[$_GET['vhost_delete']]);

		// Save store_vhosts.php
		file_put_contents('store_vhosts.php',serialize($vhosts_array));

	// Restart http server if http server running
	if ($eds_httpserver_running == 1) include('../eds-binaries/httpserver/' . $conf_httpserver['httpserver_folder'] . '/eds-app-restart.php');	
		
	header("Location: index.php#anchor_virtualhostsmanager");  
	exit;
}
//============================================================================
?>