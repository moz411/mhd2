<?php

class Customer extends generic_db {

	var $table="customers";
	var $title=null;
	var $pos=0;
	var $type="external";
	var $orderby="id";
	var $direction="DESC";
	var $limit=0;
	var $sqlstatus=null;
	var $taskbar="yes";
	var $available_columns=array(
			"id","name","type","logo"
			);
	var $available_directions=array("DESC","ASC");
	var $available_types=array("internal","external","partner","all");
	var $view=null;
	var $scope=null;
	var $string=null;

	function Customer () {
		global $Settings;
		$this->title = "[".$Settings->title."]";
	}

	function view () {
		global $Settings;
		
		if( count($this->results) <= 0 
				|| (isset ($_GET['pos']) && $_GET['pos'] != $this->pos)
				|| (isset ($_GET['orderby']) && $_GET['orderby'] != $this->orderby)
				|| (isset ($_GET['direction']) && $_GET['direction'] != $this->direction)
				|| (isset ($_GET['type']) && $_GET['type'] != $this->type)
				|| $this->timeout == 0 )
			{
		
		if (isset ($_GET['pos'])) $this->pos=$_GET['pos'];
		if (isset ($_GET['status'])) $this->status=$_GET['status'];
		if (isset ($_GET['orderby'])) $this->orderby=$_GET['orderby'];
		if (isset ($_GET['direction'])) $this->direction=$_GET['direction'];
		
		if (array_search($this->orderby, $this->available_columns) === false) $this->orderby="id";
		if (array_search($this->direction, $this->available_directions) === false) $this->direction="DESC";
		if (array_search($this->type, $this->available_types) === false) $this->type="external";
		
		if($this->type=="external") {$invertstatus="all";$text_link=_("view all customers");}
		else {$invertstatus="external";$text_link=_("view only customers with contract");}
		if($this->direction=="ASC") $invertdirection="DESC";
		else $invertdirection="ASC";
		if($this->type=="external") $sqlstatus="AND type='external'";
		$this->limit=$this->pos.",".$Settings->range;
				
		$columns=array();
		foreach($this->available_columns as $column) {
			$column_text=str_replace("_"," ",$column);
			array_push($columns,array(
				"url" => "index.php?whattodo=viewcustomers&type=$this->type&orderby=$column&pos=$this->pos&direction=$invertdirection",
				"name" => _($column_text)));
		}
		
		$count=new Customer();
		$count->sql = "SELECT COUNT(id) AS count FROM $this->table; ";
		$count->query();
		
		$total=$count->record['count'];
		if(0 > $this->pos) $this->pos=0;
		if($total <= $this->pos) $this->pos=$total;
		if(0 <= ($this->pos-$Settings->range)) $pos1=($this->pos-$Settings->range); else $pos1=0;
		if($total >= ($this->pos+$Settings->range)) $pos2=($this->pos+$Settings->range); else $pos2=$this->pos;
		$lastpage = ($total-$Settings->range);

		$links=array(
			array("title" => "<<","link"	=> "index.php?whattodo=viewcustomers&type=$this->type&orderby=$this->orderby&pos=0&direction=$this->direction"),
			array("title" => "<","link"	=> "index.php?whattodo=viewcustomers&type=$this->type&orderby=$this->orderby&pos=$pos1&direction=$this->direction"),
			array("title" => $text_link,"link"	=> "index.php?whattodo=viewcustomers&type=$invertstatus&orderby=$this->orderby&pos=$this->pos&direction=$this->direction"),
			array("title" => ">","link"	=> "index.php?whattodo=viewcustomers&type=$this->type&orderby=$this->orderby&pos=$pos2&direction=$this->direction"),
			array("title" => ">>","link"	=> "index.php?whattodo=viewcustomers&type=$this->type&orderby=$this->orderby&pos=$lastpage&direction=$this->direction")
				);
		$link_customer="index.php?whattodo=viewcustomers&type=$this->type&orderby=$this->orderby&pos=$this->pos";
		$link_whattodo="viewcustomer&cid=";
				
		$this->sql = "SELECT * FROM $this->table ";
		$this->sql .= "WHERE '1' $sqlstatus ORDER BY $this->orderby $this->direction LIMIT $this->limit; ";
		$this->query();
				
		$manager = new TemplateManager();
		$template =& $manager->prepare("themes/".$Settings->theme."/customer_view.html");
		$tproc = new TemplateProcessor();
		$tproc->set("title", $this->title." Customers");
		$tproc->set("theme_url", $Settings->base_url."/themes/".$Settings->theme);
		$tproc->set("encoding", $Settings->encoding);
		$tproc->set("menu", $_SESSION['rights']);
		$tproc->set("columns", $columns);
		$tproc->set("customers", $this->results);
		$tproc->set("link_customer", $link_customer);
		$tproc->set("links", $links);
		$tproc->set("whattodo", $link_whattodo);
		if($this->taskbar!="no") $tproc->set("taskbar", $this->taskbar);
		$this->view = $tproc->process($template);
		}
		echo $this->view;
		}
		
	function create ($cid) {
		global $Settings;
	
		if(isset($cid) && $cid != 0) {
		$this->sql ="SELECT customers.* FROM $this->table WHERE id='$cid';";
		$this->query();
		
		if($Settings->contact_db=="ldap") {
			$administrative_contact = new Contact_ldap();
			$administrative_contact->request="uid=".$this->record['administrative_contact'];
		} else {
		$administrative_contact = new Contact();
		$administrative_contact->sql  = "SELECT contacts.uid AS uid,contacts.firstname AS firstname, ";
		$administrative_contact->sql .= "contacts.lastname AS lastname FROM customers ";
		$administrative_contact->sql.="LEFT JOIN contacts ON contacts.uid=customers.administrative_contact ";
		$administrative_contact->sql .="WHERE customers.id='$cid'; ";
		}
		$administrative_contact->query();
		
		if($Settings->contact_db=="ldap") {
			$technical_contact = new Contact_ldap();
			$technical_contact->request="uid=".$this->record['technical_contact'];
		} else {
		$technical_contact = new Contact();
		$technical_contact->sql  = "SELECT contacts.uid AS uid,contacts.firstname AS firstname, ";
		$technical_contact->sql .= "contacts.lastname AS lastname FROM customers ";
		$technical_contact->sql.="LEFT JOIN contacts ON contacts.uid=customers.technical_contact ";
		$technical_contact->sql .="WHERE customers.id='$cid'; ";
		}
		$technical_contact->query();
		
		if($Settings->contact_db=="ldap") {
			$IC = new Contact_ldap();
			$IC->request="uid=".$this->record['IC'];
		} else {
		$IC = new Contact();
		$IC->sql  = "SELECT contacts.uid AS uid,contacts.firstname AS firstname, ";
		$IC->sql .= "contacts.lastname AS lastname FROM customers ";
		$IC->sql.="LEFT JOIN contacts ON contacts.uid=customers.IC ";
		$IC->sql .="WHERE customers.id='$cid'; ";
		}
		$IC->query();	
		
		if($Settings->contact_db=="ldap") {
			$ITC = new Contact_ldap();
			$ITC->request="uid=".$this->record['ITC'];
		} else {
		$ITC = new Contact();
		$ITC->sql  = "SELECT contacts.uid AS uid,contacts.firstname AS firstname, ";
		$ITC->sql .= "contacts.lastname AS lastname FROM customers ";
		$ITC->sql.="LEFT JOIN contacts ON contacts.uid=customers.ITC ";
		$ITC->sql .="WHERE customers.id='$cid'; ";
		}
		$ITC->query();	
		
		if($Settings->contact_db=="ldap")
			$contactslist = new Contact_ldap();
		else
			$contactslist = new Contact();
		$contactslist->getlist($cid);
		
		if($Settings->contact_db=="ldap") {
			$itclist = new Contact_ldap();
			$itclist->request="employeetype=ITC";
		} else {
			$itclist = new Contact();
			$itclist->sql = "SELECT contacts.uid AS uid,contacts.firstname AS firstname, ";
			$itclist->sql .= "contacts.lastname AS lastname FROM customers ";
			$itclist->sql .="LEFT JOIN contacts ON contacts.cid=customers.id ";
			$itclist->sql .="WHERE customers.id='$cid' AND contacts.job='itc'; ";
			}
		$itclist->query();
		
		if($Settings->contact_db=="ldap") {
			$iclist = new Contact_ldap();
			$iclist->request="employeetype=IC";
		} else {
			$iclist = new Contact();
			$iclist->sql = "SELECT contacts.uid AS uid,contacts.firstname AS firstname, ";
			$iclist->sql .= "contacts.lastname AS lastname FROM customers ";
			$iclist->sql .="LEFT JOIN contacts ON contacts.cid=customers.id ";
			$iclist->sql .="WHERE customers.id='$cid' AND contacts.job='ic'; ";
			}
		$iclist->query();
		}
		
		$link_customer="index.php?whattodo=viewcustomers&type=$this->type&orderby=$this->orderby&pos=$this->pos";
	
	
		$manager = new TemplateManager();
		$template =& $manager->prepare("themes/".$Settings->theme."/customer_addedit.html");
		$tproc = new TemplateProcessor();
		if(isset($cid) && $cid != 0) 
			$tproc->set("title", $this->title." Edit customer N".$cid);
		else 
			$tproc->set("title", $this->title." New customer");
		$tproc->set("theme_url", $Settings->base_url."/themes/".$Settings->theme);
		$tproc->set("encoding", $Settings->encoding);
		$tproc->set("menu", $_SESSION['rights']);
		$tproc->set("cid", $cid);
		$tproc->set("name", $this->record['name']);
		$tproc->set("phone", $this->record['phone']);
		$tproc->set("fax", $this->record['fax']);
		$tproc->set("email", $this->record['email']);
		$tproc->set("address", $this->record['address']);
		$tproc->set("administrative_contact", $administrative_contact->record['uid']);
		$tproc->set("administrative_contact_firstname", $administrative_contact->record['firstname']);
		$tproc->set("administrative_contact_lastname", $administrative_contact->record['lastname']);
		$tproc->set("technical_contact", $technical_contact->record['uid']);
		$tproc->set("technical_contact_firstname", $technical_contact->record['firstname']);
		$tproc->set("technical_contact_lastname", $technical_contact->record['lastname']);
		$tproc->set("contactslist", $contactslist->results);
		$tproc->set("type", $this->record['type']);
		$tproc->set("IC", $IC->record['uid']);
		$tproc->set("IC_firstname", $IC->record['firstname']);
		$tproc->set("IC_lastname", $IC->record['lastname']);
		$tproc->set("iclist", $iclist->results);
		$tproc->set("ITC", $ITC->record['uid']);
		$tproc->set("ITC_firstname", $ITC->record['firstname']);
		$tproc->set("ITC_lastname", $ITC->record['lastname']);
		$tproc->set("itclist", $itclist->results);
		$tproc->set("comments", $this->record['comments']);
		$tproc->set("logo", $this->record['logo']);
		$tproc->set("link_customer", $link_customer);
		if($this->taskbar!="no") $tproc->set("taskbar", $this->taskbar);
		echo $tproc->process($template);
	}
	
	function show ($cid) {
		global $Settings;
		
		$this->sql ="SELECT customers.* FROM $this->table WHERE id='$cid' ";	
		$this->query();		
		
		if($Settings->contact_db=="ldap") {
			$administrative_contact = new Contact_ldap();
			$administrative_contact->request="uid=".$this->record['administrative_contact'];
		} else {
		$administrative_contact = new Contact();
		$administrative_contact->sql  = "SELECT contacts.uid AS uid,contacts.firstname AS firstname, ";
		$administrative_contact->sql .= "contacts.lastname AS lastname FROM customers ";
		$administrative_contact->sql.="LEFT JOIN contacts ON contacts.uid=customers.administrative_contact ";
		$administrative_contact->sql .="WHERE customers.id='$cid'; ";
		}
		$administrative_contact->query();
		
		if($Settings->contact_db=="ldap") {
			$technical_contact = new Contact_ldap();
			$technical_contact->request="uid=".$this->record['technical_contact'];
		} else {
		$technical_contact = new Contact();
		$technical_contact->sql  = "SELECT contacts.uid AS uid,contacts.firstname AS firstname, ";
		$technical_contact->sql .= "contacts.lastname AS lastname FROM customers ";
		$technical_contact->sql.="LEFT JOIN contacts ON contacts.uid=customers.technical_contact ";
		$technical_contact->sql .="WHERE customers.id='$cid'; ";
		}
		$technical_contact->query();
		
		if($Settings->contact_db=="ldap") {
			$IC = new Contact_ldap();
			$IC->request="uid=".$this->record['IC'];
		} else {
		$IC = new Contact();
		$IC->sql  = "SELECT contacts.uid AS uid,contacts.firstname AS firstname, ";
		$IC->sql .= "contacts.lastname AS lastname FROM customers ";
		$IC->sql.="LEFT JOIN contacts ON contacts.uid=customers.IC ";
		$IC->sql .="WHERE customers.id='$cid'; ";
		}
		$IC->query();	
		
		if($Settings->contact_db=="ldap") {
			$ITC = new Contact_ldap();
			$ITC->request="uid=".$this->record['ITC'];
		} else {
		$ITC = new Contact();
		$ITC->sql  = "SELECT contacts.uid AS uid,contacts.firstname AS firstname, ";
		$ITC->sql .= "contacts.lastname AS lastname FROM customers ";
		$ITC->sql.="LEFT JOIN contacts ON contacts.uid=customers.ITC ";
		$ITC->sql .="WHERE customers.id='$cid'; ";
		}
		$ITC->query();
		
		$contracts = new Contract;
		$contracts->getlist($cid);
		
		$file=new File;
		$file->dir=$Settings->repository."/customers/".$this->record['id'];
		$files=$file->view();
		
		//$mail=new Mail;
		//$mails=$mail->view("customers/".$this->record['id']);
		
		
		$manager = new TemplateManager();
		$template =& $manager->prepare("themes/".$Settings->theme."/customer_show.html");
		$tproc = new TemplateProcessor();
		
		
		$tproc->set("title", $this->title." Customer ".$this->record['name']."");
		$tproc->set("theme_url", $Settings->base_url."/themes/".$Settings->theme);
		$tproc->set("encoding", $Settings->encoding);
		$tproc->set("menu", $_SESSION['rights']);
		$tproc->set("customer", $this->results);
		$tproc->set("administrative_contact", $administrative_contact->record['uid']);
		$tproc->set("administrative_contact_firstname", $administrative_contact->record['firstname']);
		$tproc->set("administrative_contact_lastname", $administrative_contact->record['lastname']);
		$tproc->set("technical_contact", $technical_contact->record['uid']);
		$tproc->set("technical_contact_firstname", $technical_contact->record['firstname']);
		$tproc->set("technical_contact_lastname", $technical_contact->record['lastname']);
		$tproc->set("IC_firstname", $IC->record['firstname']);
		$tproc->set("IC_lastname", $IC->record['lastname']);
		$tproc->set("ITC", $ITC->record['uid']);
		$tproc->set("ITC_firstname", $ITC->record['firstname']);
		$tproc->set("ITC_lastname", $ITC->record['lastname']);
		$tproc->set("contracts", $contracts->results);
		$tproc->set("files", $files);
		//$tproc->set("mails", $mails);
		$tproc->set("max_file_size", $Settings->max_file_size);
		$tproc->set("cid", $cid);
		
		if($this->taskbar!="no") $tproc->set("taskbar", $this->taskbar);
		echo $tproc->process($template);
		}
		
	function update ($datas) {
		if(!is_array($datas)) error("update customer : this is not an array !");
		
		$source=array_clean($datas, "submit");
		$source=array_clean($source, "commit");
		
		$cid=$source['id'];
		
		$this->sql ="SELECT * FROM $this->table WHERE id='$cid';";
		$this->query();
		
		$diffs = array_diff_assoc($source,$this->results['0']);
		if(count($diffs) >0) {
			$update=new Customer();
			$update->sql="UPDATE $this->table SET ";
			foreach($diffs as $key=>$val) {
				$val=str_replace("'","\'",$val);
				$update->sql.="$key='$val',";}
			$update->sql=substr("$update->sql", 0, -1);
			$update->sql.=" WHERE id='$cid';";
			$update->updaterow();
			}
		}

	function getlist () {
		$this->sql = "SELECT id, name FROM $this->table WHERE '1' ORDER BY name;";	
		$this->query();
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
				"url" => "index.php?whattodo=viewcustomers&type=$this->type&orderby=$column&pos=$this->pos&direction=$invertdirection",
				"name" => _($column_text)));
		}
		
		$this->sql = "SELECT DISTINCT * FROM customers 
									WHERE MATCH ($scope) AGAINST ('$string') GROUP BY customers.id ORDER BY customers.id DESC;";
		$this->query();
				
		$manager = new TemplateManager();
		$template =& $manager->prepare("themes/".$Settings->theme."/customer_view.html");
		$tproc = new TemplateProcessor();
		$tproc->set("title", $this->title." Customers");
		$tproc->set("theme_url", $Settings->base_url."/themes/".$Settings->theme);
		$tproc->set("encoding", $Settings->encoding);
		$tproc->set("columns", $columns);
		$tproc->set("customers", $this->results);
		$tproc->set("link_customer", $link_customer);
		$tproc->set("links", $links);
		$tproc->set("whattodo", $link_whattodo);
		if($this->taskbar!="no") $tproc->set("taskbar", $this->taskbar);
		$this->view = $tproc->process($template);
		}
		echo $this->view;
		}
	
}
		
?>
