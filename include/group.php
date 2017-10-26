<?php

class Group extends generic_db {
	var $rights=array();
	var $taskbar="yes";


	function Group () {
		$this->table = "groups";
		$this->rights = array("addjob","viewjobs","printjob","exportcsv","addcustomer","viewcustomers",
				"addcontract","viewcontracts","additem","printcontract","addcontact","viewcontacts",
				"addfile", "addevent", "showfiles", "showmails", "booking",
				"viewdocs","showdoc", "stats","config","users","groups","search","logout");
	}
	
	
		
	function getlist () {

		$this->sql = "SELECT id, name FROM $this->table ORDER BY name;";	
		$this->query();
		}
		
	function getrights ($group) {
		foreach($this->rights as $key=>$val)
				$rights_temp .= "$val,";
		$rights_temp=substr("$rights_temp", 0, -1);
		
		$this->sql = "SELECT $rights_temp FROM $this->table WHERE name='$group';";	
		$this->query();
		
		return $this->results[0];
		}
		
	function create () {
		global $Settings;
		
		/*get the group list */
		$this->getlist();
		$groups=array();
		
		/* create an array for the group select widget */
		foreach($this->results as $key => $val) {
			array_push($groups, array(
				"text" => $this->results[$key]['name'],
				"value" => $this->results[$key]['name']
				));
			}
		
		/* get each group selected_rights and create array for javascript */
		$selected_rights=array();
		$not_selected_rights=array();
		foreach($this->results as $key => $val) {
			$name=$this->results[$key]['name'];
			$group_temp=new Group();
			$group_temp->getrights($name);
			foreach($group_temp->results[0] as $key2 => $val2) {
				if($val2 == 'Y') {
					array_push($selected_rights, array(
						"group" => $name,
						"right" => $key2
						));
				} else {
					array_push($not_selected_rights, array(
						"group" => $name,
						"right" => $key2
						));
					}
				}
			}
		
		/* create array for checkboxes */
		$check_rights=array();
		foreach($this->rights as $key => $val)
			array_push($check_rights, array("text" => $val,  "value" => $val ));
				
		$manager = new TemplateManager();
		$template =& $manager->prepare("themes/".$Settings->theme."/groups.html");
		$tproc = new TemplateProcessor();
		$tproc->set("title", $this->title." Groups");
		$tproc->set("theme_url", $Settings->base_url."/themes/".$Settings->theme);
		$tproc->set("encoding", $Settings->encoding);
		$tproc->set("menu", $_SESSION['rights']);
		$tproc->set("name", $groups);
		$tproc->set("selected_rights", $selected_rights);
		$tproc->set("not_selected_rights", $not_selected_rights);
		$tproc->set("check_rights[]", $check_rights);
		if($this->taskbar!="no") $tproc->set("taskbar", $this->taskbar);
		echo $tproc->process($template);
		}
		
	function update ($datas) {
		global $Settings;
		if(!is_array($datas)) error("update groups : this is not an array !");
		$source=array_clean($datas, "submit");
		$source=array_clean($source, "commit");
		$name=$source['name'];
		
		foreach($this->rights as $key=>$val)
				$rights_temp .= "$val,";
		$rights_temp=substr("$rights_temp", 0, -1);
		
		/* get groups configs */
		$this->sql = "SELECT name,$rights_temp FROM $this->table WHERE name='$name';";	
		$this->query();
		
		$updated_rights=array();
		$updated_rights['name']=$name;
		if(0<count($datas['selected_rights'])) {
			foreach($datas['selected_rights'] as $key => $var)
				$updated_rights[$var] = 'Y';
			}
		if(0<count($datas['not_selected_rights'])) {
			foreach($datas['not_selected_rights'] as $key => $var)
				$updated_rights[$var] = 'N';
			}
			
		$diffs = array_diff_assoc($updated_rights,$this->results['0']);
		if(count($diffs) >0) {
			$update=new Group();
			$update->sql="UPDATE $this->table SET ";
			foreach($diffs as $key=>$val)
				$update->sql.="$key='$val',";
			$update->sql=substr("$update->sql", 0, -1);
			$update->sql.=" WHERE name='$name';";
			$update->updaterow();
			}
		
		}
		
}
