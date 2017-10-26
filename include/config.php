<?php
/*
This class is used to keep settings as db login/pass or theme
*/
class Config extends generic_db {
	var $dbfile="include/config.inc.php";
	var $taskbar="yes";
	var $contact_db_type=array();
	
	function Config () {
		global $Settings;
		$this->table="config";
		$this->contact_db_type=array("sql","ldap");
		}

	
	function get () {	
		global $Settings;
		/* First we check config.inc.php to get an access to the database */
		if (is_file($this->dbfile)) {
			$f = file($this->dbfile);
			foreach ($f as $line) {
				$line = eregi_replace("#.+","",$line);
				if($regs = preg_split("[=]", $line, 2, PREG_SPLIT_NO_EMPTY))
					$regs[0] = trim($regs[0]); $regs[1] = trim($regs[1]);
					$Settings->$regs[0] = $regs[1];
				}
			/* then we get the rest of the parameters from the database */
			$this->sql="SELECT * FROM config;";
			$this->query();
			foreach($this->results[0] as $key => $val)
				if($val != "") $Settings->$key = $val;
		} else {
			echo "Database config not set, please edit $this->dbfile";
			}
		$this->userPrefs();
		}
	
	function userPrefs () {		
		global $Settings;
		if(isset($_COOKIE['MHD2_THEME']))
			$this->theme = $_COOKIE['MHD2_THEME'];
		if(isset($_COOKIE['MHD2_RANGE']))
			$this->range = $_COOKIE['MHD2_RANGE'];
		if(isset($_COOKIE['MHD2_LOCALE'])) {
			$this->locale = $_COOKIE['MHD2_LOCALE'];
		} else {
			$getloc = new detect_language();
			$this->locale = $getloc->getLanguage();
			}
		setlocale(LC_ALL, $this->locale);
		// Set the text domain as 'mydomain'
		$domain = 'mhd2';
		bindtextdomain("$domain", "./locale");
		textdomain("$domain");
		bind_textdomain_codeset($domain, $Settings->encoding);
		}
		
	function save_locale ($locale) {
		setcookie("MHD2_LOCALE", $locale, time()+1000000, "/", "", 0);
		}
	
	function create () {
		global $Settings;
		
		/* make a copy of Settings for editing */
		$editable=$Settings->results;
		
		/* list themes directory to create the select array and mark the current theme
		as selected */ 
		$editable[0]['theme']=array();
		$dir = "./themes";
		$handle=opendir($dir);
		while (false !== ($file = readdir($handle))) {
			if ($file != '.' && $file != '..' && $file == $Settings->theme) {
				array_push($editable[0]['theme'], array(
					"text" => $file,
					"value" => $file,
					"checked" => TRUE
					));
			} elseif ($file != '.' && $file != '..' ) {
				array_push($editable[0]['theme'], array(
					"text" => $file,
					"value" => $file
					));
			} else {
				array_push($editable[0]['theme'], array(
					"text" => "default",
					"value" => "default"
					));
				}
				
			}
		closedir($handle);
		
		/* create an array for the contact_db select widget */
		$editable[0]['contact_db']=array();
		foreach($this->contact_db_type as $val) {
			if($val == $Settings->contact_db) {
				array_push($editable[0]['contact_db'], array(
					"text" => $val,
					"value" => $val,
					"checked" => TRUE
					));
			} else {
				array_push($editable[0]['contact_db'], array(
					"text" => $val,
					"value" => $val
					));
				}
			}
		
		
		$manager = new TemplateManager();
		$template =& $manager->prepare("themes/".$Settings->theme."/config.html");
		$tproc = new TemplateProcessor();
		$tproc->set("title", $this->title." Configuration");
		$tproc->set("theme_url", $Settings->base_url."/themes/".$Settings->theme);
		$tproc->set("menu", $_SESSION['rights']);
		$tproc->set("Settings", $editable);
		if($this->taskbar!="no") $tproc->set("taskbar", $this->taskbar);
		echo $tproc->process($template);
		}
	
	function update ($datas) {
		global $Settings;
		if(!is_array($datas)) error("update config : this is not an array !");
		$source=array_clean($datas, "submit");
		$source=array_clean($source, "commit");
		
		$diffs = array_diff_assoc($source,$Settings->results['0']);
		if(count($diffs) >0) {
			$update=new Config();
			$update->sql="UPDATE $this->table SET ";
			foreach($diffs as $key=>$val)
				$update->sql.="$key='$val',";
			$update->sql=substr("$update->sql", 0, -1);
			$update->sql.=" ;";
			$update->updaterow();
			}
		$this->get();
		}
		
	
}

?>
