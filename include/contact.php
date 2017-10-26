<?php

class Contact extends generic_db {
	
	var $table="contacts";
	var $title=null;
	var $pos=0;
	var $link=null;
	var $taskbar="yes";
	var $close=null;
	var $view=null;
	var $available_columns=array(
			"uid", "firstname","lastname", "customer","phone","email","fax", "job"
			);
	var $scope=null;
	var $string=null;
	

	function Contact () {
		global $Settings;
		
		$this->title = "[".$Settings->title."]";
	}
		
	function getlist ($cid) {
		$this->sql = "SELECT * FROM contacts WHERE cid='$cid' ORDER BY firstname;";	
		$this->query();
		}

	function view () {
		global $Settings;

		if (array_search($this->orderby, $this->available_columns) === false) $this->orderby="id";
		if (array_search($this->direction, $this->available_directions) === false) $this->direction="DESC";
		
		if($this->direction=="ASC") $invertdirection="DESC";
		else $invertdirection="ASC";
		$this->limit=$this->pos.",".$Settings->range;
		
		$columns=array();
		foreach($this->available_columns as $column) {
			$column_text=str_replace("_"," ",$column);
			array_push($columns,array(
				"url" => "index.php?whattodo=viewcontacts&status=$this->status&orderby=$column&pos=$this->pos&direction=$invertdirection",
				"name" => _($column_text)));
		}

		$count_contacts=new Contact();
		$count_contacts->sql = "SELECT COUNT(uid) AS count FROM $this->table; ";
		$count_contacts->query();
		
		$total=$count_contacts->record['count'];
		if(0 > $this->pos) $this->pos=0;
		if($total <= $this->pos) $this->pos=$total;
		if(0 <= ($this->pos-$Settings->range)) $pos1=($this->pos-$Settings->range); else $pos1=0;
		if($total >= ($this->pos+$Settings->range)) $pos2=($this->pos+$Settings->range); else $pos2=$this->pos;
		
		
		$links=array(
			array("title" => "<","link"	=> "index.php?whattodo=viewcontacts&status=$this->status&orderby=$this->orderby&pos=$pos1&direction=$this->direction"),
			array("title" => $text_link,"link"	=> "index.php?whattodo=viewcontacts&status=$invertstatus&orderby=$this->orderby&pos=$this->pos&direction=$this->direction"),
			array("title" => ">","link"	=> "index.php?whattodo=viewcontacts&status=$this->status&orderby=$this->orderby&pos=$pos2&direction=$this->direction")
				);
		$link_contact="index.php?whattodo=viewcontacts&status=$this->status&orderby=$this->orderby&pos=$this->pos";
			
		if($this->orderby == "id") $this->orderby = "uid";
		$this->sql = "SELECT contacts.*, customers.name AS customer FROM $this->table ";
		$this->sql .= "LEFT JOIN customers ON customers.id=contacts.cid ";
		$this->sql .= "WHERE '1' $sqlstatus ORDER BY $this->orderby $this->direction LIMIT $this->limit; ";
		$this->query();
			
			
		$manager = new TemplateManager();
		$template =& $manager->prepare("themes/".$Settings->theme."/contact_view.html");
		$tproc = new TemplateProcessor();
		$tproc->set("title", $this->title." Contacts");
		$tproc->set("theme_url", $Settings->base_url."/themes/".$Settings->theme);
		$tproc->set("encoding", $Settings->encoding);
		$tproc->set("menu", $_SESSION['rights']);
		$tproc->set("columns", $columns);
		$tproc->set("contacts", $this->results);
		$tproc->set("link_contact", $link_contact);
		$tproc->set("links", $links);
		if($this->taskbar!="no") $tproc->set("taskbar", $this->taskbar);
		
		echo $tproc->process($template);
	}
		
	function create ($uid, $cid) {
		global $Settings;
	
		if(isset($uid) && $uid != "") {
			$this->sql ="SELECT contacts.*, customers.name AS customer_name, customers.id AS customer_id FROM $this->table ";
			$this->sql .= "LEFT JOIN customers ON customers.id=contacts.cid ";
			$this->sql .="WHERE uid='$uid';";
			$this->query();
			}
			
		if(isset($cid) && $cid != 0) {
			$customer = new Customer();
			$customer->sql = "SELECT id,name FROM customers WHERE id='$cid'; ";	
			$customer->query();
			$this->record['customer_name']=$customer->record['name'];
			$this->record['customer_id']=$customer->record['id'];
			}
		
		$link_contact="index.php?whattodo=viewcontacts&type=$this->type&orderby=$this->orderby&pos=$this->pos";
	
		
		$customerslist = new Customer();	
		$customerslist->getlist();
	
		$manager = new TemplateManager();
		$template =& $manager->prepare("themes/".$Settings->theme."/contact_addedit.html");
		$tproc = new TemplateProcessor();
		if(isset($uid) && $uid != 0) 
			$tproc->set("title", $this->title." Edit contact ".$uid);
		else 
			$tproc->set("title", $this->title." New contact");
		$tproc->set("theme_url", $Settings->base_url."/themes/".$Settings->theme);
		$tproc->set("encoding", $Settings->encoding);
		$tproc->set("menu", $_SESSION['rights']);
		$tproc->set("uid", $uid);
		$tproc->set("firstname", $this->record['firstname']);
		$tproc->set("lastname", $this->record['lastname']);
		$tproc->set("customer_name", $this->record['customer_name']);
		$tproc->set("customer_id", $this->record['customer_id']);
		$tproc->set("phone", $this->record['phone']);
		$tproc->set("email", $this->record['email']);
		$tproc->set("fax", $this->record['fax']);
		$tproc->set("address", $this->record['address']);
		$tproc->set("job", $this->record['job']);;
		$tproc->set("customerslist", $customerslist->results);
		$tproc->set("link_contact", $link_contact); 
		if($this->taskbar!="no") $tproc->set("taskbar", $this->taskbar);
		echo $tproc->process($template);
	}
	
	function show ($uid) {
		global $Settings;
		
		$this->sql ="SELECT contacts.*, customers.name AS customer_name FROM $this->table ";
		$this->sql.="LEFT JOIN customers ON customers.id=contacts.cid ";
		$this->sql .="WHERE uid='$uid' ";	
		$this->query();

		$customer = new Customer();
		$customer->sql ="SELECT customers.id AS id, customers.name AS name FROM contacts ";
		$customer->sql.="LEFT JOIN customers ON customers.id=contacts.cid ";
		$customer->sql.="WHERE contacts.uid='$uid'; ";	
		$customer->query();
		
		$manager = new TemplateManager();
		$template =& $manager->prepare("themes/".$Settings->theme."/contact_show.html");
		$tproc = new TemplateProcessor();
		
		$tproc->set("title", $this->title." contact ".$this->record['firstname']."".$this->record['lastname']);
		$tproc->set("theme_url", $Settings->base_url."/themes/".$Settings->theme);
		$tproc->set("encoding", $Settings->encoding);
		$tproc->set("menu", $_SESSION['rights']);
		$tproc->set("contact", $this->results);
		$tproc->set("cid", $customer->record['id']);
		$tproc->set("uid", $uid);
		if($this->taskbar!="no") $tproc->set("taskbar", $this->taskbar);
		if($this->close=="yes") $tproc->set("close", "yes");
		echo $tproc->process($template);
		}
		
	function update ($datas) {
		if(!is_array($datas)) error("update contact : this is not an array !");
		
		$source=array_clean($datas, "submit");
		$source=array_clean($source, "commit");
		
		$uid=$source['uid'];
		
		$this->sql ="SELECT * FROM $this->table WHERE uid='$uid';";
		$this->query();
		
		$diffs = array_diff_assoc($source,$this->results['0']);
		if(count($diffs) >0) {
			$update=new Contact();
			$update->sql="UPDATE $this->table SET ";
			foreach($diffs as $key=>$val)
				$update->sql.="$key='$val',";
			$update->sql=substr("$update->sql", 0, -1);
			$update->sql.=" WHERE uid='$uid';";
			$update->updaterow();
			}
		}
		
	function search ($scope,$string) {
	global $Settings;

		if($scope != $this->scope || $string != $this->string)
			{
		$this->scope=$scope;
		$this->string=$string;

		$columns=array();
		foreach($this->available_columns as $column) {
			$column_text=str_replace("_"," ",$column);
			array_push($columns,array(
				"url" => "index.php?whattodo=viewcontacts&status=$this->status&orderby=$column&pos=$this->pos&direction=$invertdirection",
				"name" => _($column_text)));
		}
		
		$links=array(
			array("title" => "<","link"	=> "index.php?whattodo=viewcontacts&status=$this->status&orderby=$this->orderby&pos=$pos1&direction=$this->direction"),
			array("title" => $text_link,"link"	=> "index.php?whattodo=viewcontacts&status=$invertstatus&orderby=$this->orderby&pos=$this->pos&direction=$this->direction"),
			array("title" => ">","link"	=> "index.php?whattodo=viewcontacts&status=$this->status&orderby=$this->orderby&pos=$pos2&direction=$this->direction")
				);
		$link_contact="index.php?whattodo=viewcontacts&status=$this->status&orderby=$this->orderby&pos=$this->pos";
			
		if($scope == "customers.name") {
			$this->sql = "SELECT contacts.*, customers.name AS customer FROM $this->table ";
			$this->sql .= "LEFT JOIN customers ON customers.id=contacts.cid ";
			$this->sql .= "WHERE $scope = '$string' GROUP BY contacts.uid ORDER BY contacts.uid DESC;";
		} else {
			$this->sql = "SELECT contacts.*, customers.name AS customer FROM $this->table ";
			$this->sql .= "LEFT JOIN customers ON customers.id=contacts.cid ";
			$this->sql .= "WHERE MATCH ($scope) AGAINST ('$string') GROUP BY contacts.uid ORDER BY contacts.uid DESC;";
			}
		$this->query();
			
			
		$manager = new TemplateManager();
		$template =& $manager->prepare("themes/".$Settings->theme."/contact_view.html");
		$tproc = new TemplateProcessor();
		$tproc->set("title", $this->title." Contacts");
		$tproc->set("theme_url", $Settings->base_url."/themes/".$Settings->theme);
		$tproc->set("encoding", $Settings->encoding);
		$tproc->set("columns", $columns);
		$tproc->set("contacts", $this->results);
		$tproc->set("link_contact", $link_contact);
		$tproc->set("links", $links);
		if($this->taskbar!="no") $tproc->set("taskbar", $this->taskbar);
		$this->view = $tproc->process($template);
		}
		echo $this->view;
		
	}
}
?>
