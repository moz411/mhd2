<?php
/*
header.php :  include all config and libraries and initialize session
*/
// load libs and functions

include_once("libraries/language.php");
include_once("libraries/db.php");
include_once("libraries/db_ldap.php");
include_once("libraries/htmltmpl.php");
include_once("libraries/class_webdav_client.php");

include_once("include/functions.php");
include_once("include/user.php");
include_once("include/group.php");
include_once("include/login.php");
include_once("include/ticket.php");
include_once("include/customer.php");
include_once("include/contract.php");
include_once("include/file.php");;
include_once("include/mail.php");
include_once("include/doc.php");
include_once("include/stats.php");
include_once("include/search.php");
include_once("include/print.php");


// check config file or goto setup
include_once("include/config.php");

// start session and load config
session_name('MHD2_SESSID');
session_start();
ini_set('unserialize_callback_func','mhd2_callback');
if (!isset($_SESSION['Settings'])) $_SESSION['Settings'] = new Config();
$Settings = &$_SESSION['Settings'];
$Settings->get();
if(isset($_REQUEST['locale']) && $_REQUEST['locale'] != $Settings->locale)
	$Settings->save_locale($_REQUEST['locale']);


?>
