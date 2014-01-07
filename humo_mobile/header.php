<?php
session_cache_limiter ('private, must-revalidate'); 
session_start();
define("CMS_ROOTPATH", "../");

if (isset($_GET['log_off'])){
	session_unset(); // *** Clear all variables ***
	session_destroy(); // *** Remove session ***
	session_write_close();
	session_start();
}

include_once(CMS_ROOTPATH."include/db_login.php"); //Login database.

mysql_query("SET NAMES 'utf8'", $db) or die(mysql_error());
 
include_once("function.php");

include_once(CMS_ROOTPATH."include/safe.php");
include_once(CMS_ROOTPATH."include/settings_global.php"); //Variables
include_once(CMS_ROOTPATH."include/settings_user.php"); // USER variables
include_once(CMS_ROOTPATH."include/database_name.php");
include_once(CMS_ROOTPATH."include/person_cls.php");
include_once(CMS_ROOTPATH."include/marriage_cls.php"); 
include_once(CMS_ROOTPATH."include/date_place.php"); 
include_once(CMS_ROOTPATH."include/language_date.php"); 

$bot_visit=preg_match('/bot|spider|crawler|curl|Yahoo|Google|^$/i', $_SERVER['HTTP_USER_AGENT']);

$language_folder=opendir('../languages/'); 
while (false!==($file = readdir($language_folder))) {
	if (strlen($file)<6 AND $file!='.' AND $file!='..'){	
		$language_file[]=$file;

		// *** Save choice of language ***
		$language_choice='';
		if (isset($_POST["language"])){ $language_choice=$_POST["language"]; }
		if ($language_choice!=''){
			// Check if file exists (IMPORTANT DO NOT REMOVE THESE LINES)
			// ONLY save an existing language file.
			if ($language_choice==$file){ $_SESSION['language'] = $file;}
		}
	}
}
closedir($language_folder);
 
// *** Language processing after header("..") lines. *** 
include_once("../languages/language.php");

// *** Log in ***
if (isset($_POST["username"]) && isset($_POST["password"])){
	$query = "SELECT * FROM humo_users
		WHERE user_name='" . safe_text($_POST["username"]) ."'
		AND user_password='".MD5(safe_text($_POST["password"]))."'";
	$result = mysql_query($query) or die("FAULT : " . mysql_error());
	if (mysql_num_rows($result) > 0){
		@$resultDb=mysql_fetch_object($result);
		$_SESSION['user_name'] = safe_text($_POST["username"]);
		$_SESSION['user_id'] = $resultDb->user_id;
		$_SESSION['user_group_id'] = $resultDb->user_group_id;

		// *** Save log! ***
		$sql="INSERT INTO humo_user_log SET
			log_date='".date("Y-m-d H:i")."',
			log_username='".safe_text($_POST["username"])."',
			log_ip_address='".$_SERVER['REMOTE_ADDR']."',
			log_user_admin='user'";
		@mysql_query($sql, $db);

		// *** Send to secured page ***
			header("Location: index.php");
		exit();
	}
	else{
		// *** No valid user found ***
		$fault=true;
	}
}


// *** Family tree choice ***
global $database;
$database='';
if (isset($urlpart[0]) AND $urlpart[0]!='standaard'){ $database=$urlpart[0]; } // *** url_rewrite ***
if (isset($_GET["database"])){ $database=$_GET["database"]; }
if (isset($_POST["database"])){ $database=$_POST["database"]; }
if (isset($database) AND $database){
	// *** Check if family tree really exists ***
	$datasql = mysql_query("SELECT * FROM humo_trees WHERE tree_prefix='".safe_text($database)."'",$db);
	if (@mysql_num_rows($datasql)==1) { $_SESSION['tree_prefix']=$database; }
}
// *** No family tree selected yet ***
if (!isset($_SESSION["tree_prefix"]) OR $_SESSION['tree_prefix']=='' ){
	$_SESSION['tree_prefix']=''; // *** If all trees are blocked then session is empty ***

	$datasql = mysql_query("SELECT * FROM humo_trees ORDER BY tree_order",$db);

	// *** Find first family tree that's not blocked for this usergroup ***
	while(@$dataDb=mysql_fetch_object($datasql)){
		// *** Check is family tree is showed or hidden for user group ***
		$hide_tree_array=explode(";",$user['group_hide_trees']);
		$hide_tree=false;
		for ($x=0; $x<=count($hide_tree_array)-1; $x++){
			if ($hide_tree_array[$x]==$dataDb->tree_id){ $hide_tree=true; }
		}
		if ($hide_tree==false){	
			$_SESSION['tree_prefix']=$dataDb->tree_prefix;
			break;
		}
	}
}

// *** Check if tree is allowed for visitor and Google etc. ***
$datasql = mysql_query("SELECT * FROM humo_trees WHERE tree_prefix='".$_SESSION['tree_prefix']."'",$db);
@$dataDb=mysql_fetch_object($datasql);
$hide_tree_array=explode(";",$user['group_hide_trees']);
$hide_tree=false;
for ($x=0; $x<=count($hide_tree_array)-1; $x++){
	if ($hide_tree_array[$x]==@$dataDb->tree_id){ $hide_tree=true; }
}
if ($hide_tree){ $_SESSION['tree_prefix']=''; }
?>