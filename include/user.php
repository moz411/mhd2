<?php

class User extends generic_db {
	var $rights=array();
	var $taskbar="yes";


	function User () {
		$this->table = "users";
		$this->rights = array("addjob","viewjobs","printjob","exportcsv","addcustomer","viewcustomers",
				"addcontract","viewcontracts","additem","printcontract","addcontact","viewcontacts",
				"addfile", "addevent", "showfiles", "showmails", "booking",
				"viewdocs","showdoc", "stats","config","users","groups","search","logout");
	}
		
	function getlist () {

		$this->sql = "SELECT id, firstname, lastname FROM $this->table ORDER BY firstname;";	
		$this->query();
		}
		
	function validate ($user) {
		$this->sql="SELECT id,passwd,firstname,lastname FROM $this->table WHERE id='$user'";	
		$this->query();
		}
		
	function getrights ($user) {
		foreach($this->rights as $key=>$val)
				$rights_temp .= "$val,";
		$rights_temp=substr("$rights_temp", 0, -1);
		
		$this->sql = "SELECT $rights_temp FROM $this->table WHERE id='$user';";	
		$this->query();
		$user_rights = $this->results;
		
		$this->sql = "SELECT groupe FROM $this->table WHERE id='$user';";	
		$this->query();
		$groupe = $this->record['groupe'];
		
		$this->sql = "SELECT $rights_temp FROM groups WHERE name='$groupe';";
		$this->query();
		$group_rights = $this->results;
		
		foreach($this->rights as $key) {
			if($group_rights[0][$key] == 'Y' || $user_rights[0][$key]  == 'Y')
			 $rights[0][$key] = 'Y';
			}
		
		return $rights;
		}
	
	function create () {
		global $Settings;
		
		/* select existing users */
		$this->sql = "SELECT * FROM users;";	
		$this->query();
		
		/*get the list of groups */
		$groups_list=new User();
		$groups_list->sql = "SELECT name FROM groups;";
		$groups_list->query();
		
		$groups=array();
		
		/* create an array for the "group select" widget */
		foreach($this->results as $key => $val) {
			$selected_group=$this->results[$key]['groupe'];
			$this->results[$key]['groupe'] = array();
			foreach($groups_list->results as $key2 => $val2) {
				if($selected_group == $groups_list->results[$key2]['name']) {
					array_push($this->results[$key]['groupe'] , array(
						"text" => $groups_list->results[$key2]['name'],
						"value" => $groups_list->results[$key2]['name'],
						"checked" => TRUE
						));
				} else {
					array_push($this->results[$key]['groupe'] , array(
						"text" => $groups_list->results[$key2]['name'],
						"value" => $groups_list->results[$key2]['name']
						));
					}
				array_push($groups , array(
					"text" => $groups_list->results[$key2]['name'],
					"value" => $groups_list->results[$key2]['name']
					));
				}
			}
		
		$manager = new TemplateManager();
		$template =& $manager->prepare("themes/".$Settings->theme."/users.html");
		$tproc = new TemplateProcessor();
		$tproc->set("title", $this->title." Users");
		$tproc->set("theme_url", $Settings->base_url."/themes/".$Settings->theme);
		$tproc->set("encoding", $Settings->encoding);
		$tproc->set("menu", $_SESSION['rights']);
		$tproc->set("Users", $this->results);
		$tproc->set("groups", $groups);
		if($this->taskbar!="no") $tproc->set("taskbar", $this->taskbar);
		echo $tproc->process($template);
		
		}
		
	function update ($datas) {
		global $Settings;
		if(!is_array($datas)) error("update users : this is not an array !");
		$source=array_clean($datas, "submit");
		$source=array_clean($source, "commit");
		
		$userid=$source['id'];
		
		/* get users configs */
		$this->sql = "SELECT * FROM users WHERE id='$userid';";	
		$this->query();
		
		/* compute differences */
		$diffs = array_diff_assoc($source,$this->results['0']);
		if(count($diffs) >0) {
			if(key($diffs) == 'passwd') $diffs['passwd'] = md5($diffs['passwd']);
			$update=new User();
			$update->sql="UPDATE $this->table SET ";
			foreach($diffs as $key=>$val)
				$update->sql.="$key='$val',";
			$update->sql=substr("$update->sql", 0, -1);
			$update->sql.=" WHERE id='$userid';";
			$update->updaterow();
			}
		}
}
?>
