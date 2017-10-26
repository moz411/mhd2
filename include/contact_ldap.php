<?php

class Contact_ldap extends db_ldap {
	
	var $table="contacts";
	var $title=null;
	var $pos=null;
	var $link=null;
	var $taskbar="yes";
	var $close=null;
	var $available_columns=array(
			"uid","firstname","lastname","customer","phone","email","fax","job"
			);
	var $alphabet=array("a","b","c","d","e","f","g","h","i","j","k","l","m","n",
											"o","p","q","r","s","t","u","v","w","x","y","z");

	function Contact_ldap () {
	
		global $Settings;
		$this->tree=$Settings->ldap_tree;
		$this->admin =$Settings->ldap_admin;
		$this->host=$Settings->ldap_host;
		$this->admin=$Settings->ldap_admin;
		$this->password=$Settings->ldap_password;
		$this->title = "[".$Settings->title."]";
	}
	
	function view () {
		global $Settings;
		if (array_search($this->pos, $this->alphabet) === false) $this->pos='a';
		//if (array_search($this->orderby, $this->available_columns) === false) $this->orderby="cn";
		if (array_search($this->direction, $this->available_directions) === false) $this->direction="DESC";
		if($this->orderby == "id") $this->orderby = "uid";
		if($this->direction=="ASC") $invertdirection="DESC";
		else $invertdirection="ASC";
		switch($this->orderby) {
				case "uid":
				$this->orderby="uid";
				break;
				case "firstname":
				$this->orderby="cn";
				break;
				case "lastname":
				$this->orderby="cn";
				break;
				case "customer":
				$this->orderby="o";
				break;
				case "phone":
				$this->orderby="telephonenumber";
				break;
				case "email":
				$this->orderby="mail";
				break;
				case "fax":
				$this->orderby="facsimiletelephonenumber";
				break;
				case "job":
				$this->orderby="employeetype";
				break;
			}
		
		$columns=array();
		foreach($this->available_columns as $column) {
			$column_text=str_replace("_"," ",$column);
			array_push($columns,array(
				"url" => "index.php?whattodo=viewcontacts&status=$this->status&orderby=$column&pos=$this->pos&direction=$invertdirection",
				"name" => _($column_text)));
		}

		$links=array();
		foreach($this->alphabet as $letter) {
		array_push($links,array(
			"title" => $letter,
			"link"	=> "index.php?whattodo=viewcontacts&status=$this->status&orderby=$this->orderby&pos=$letter&direction=$this->direction"
			));
		}
		$link_contact="index.php?whattodo=viewcontacts&status=$this->status&orderby=$this->orderby&pos=$this->pos";
			
		
		
		$this->request = $this->orderby."=".$this->pos."*";
		$this->query();
		$results_cache=$this->results;
	
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
			$this->request="uid=".$uid;
			$this->query();
			$name = removequote($this->record['customer']);
			$customer = new  Customer();
			$customer->sql = "SELECT id,name FROM customers WHERE name='$name'; ";
			$customer->query();
			$this->record['customer_name']=$customer->record['name'];
			$this->record['customer_id']=$customer->record['id'];
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
		
		$this->request="uid=".$uid;
		$this->query();
		$this->results[0]['customer_name']=&$this->results[0]['customer'];
		
		$name = removequote($this->record['customer']);

		$customer = new Customer();
		$customer->sql ="SELECT id, name FROM customers ";
		$customer->sql.="WHERE name='$name'; ";	
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
		
		$this->request ="uid=".$uid;
		$this->query();

		$customer = new Customer();
		$customer->sql ="SELECT name FROM customers ";
		$customer->sql.="WHERE id='".$source['cid']."'; ";	
		$customer->query();
		
		$source['customer']=$customer->record['name'];
		unset($source['cid']);
		
		foreach($source as $key => $val) {
			switch($key) {
				case "firstname":
					if(isset($source['firstname']) && $source['firstname'] != "")
					$source["cn"]=$source['firstname']." ".$source['lastname'];
					unset($source['firstname']);
					unset($source['lastname']);
				break;
				case "customer":
					if(isset($source['customer']) && $source['customer'] != "")
					$source["o"]=$source['customer'];
					unset($source['customer']);
				break;
				case "phone":
					if(isset($source['phone']) && $source['phone'] != "")
					$source["telephonenumber"]=$source['phone'];
					unset($source['phone']);
				break;
				case "email":
					if(isset($source['email']) && $source['email'] != "")
					$source["mail"]=$source['email'];
					unset($source['email']);
				break;
				case "address":
					if(isset($source['address']) && $source['address'] != "")
					$source["postaladdress"]=$source['address'];
					unset($source['address']);
				break;
				case "fax":
					if(isset($source['fax']) && $source['fax'] != "")
					$source["facsimiletelephonenumber"]=$source['fax'];
					unset($source['fax']);
				break;
				case "job":
					if(isset($source["job"]) && $source['job'] != "")
					$source['employeetype']=$source['job'];
					unset($source['job']);
				break;
			}
		}
		
		$diffs = array_diff_assoc($source,$this->results['0']);
		
		if(count($diffs) >0) {
			$update=new Contact_ldap();
			$update->updaterow($this->results['0']['dn'],$source);
			}
		}
		
		

	function getlist ($cid) {
			$customer = new Customer();
			$customer->sql = "SELECT id,name FROM customers WHERE id='$cid'; ";
			$customer->query();
			
			$this->request = "o=".$customer->record['name'];
			$this->query();
	}
	
	function search ($scope,$string) {
		global $Settings;

		switch($scope) {
				case "firstname":
				$this->orderby="cn";
				break;
				case "lastname":
				$this->orderby="cn";
				break;
				case "customers.name":
				$this->orderby="o";
				break;
				case "phone":
				$this->orderby="telephonenumber";
				break;
				case "email":
				$this->orderby="mail";
				break;
			}
		
		$columns=array();
		foreach($this->available_columns as $column) {
			$column_text=str_replace("_"," ",$column);
			array_push($columns,array(
				"url" => "index.php?whattodo=viewcontacts&status=$this->status&orderby=$column&pos=$this->pos&direction=$invertdirection",
				"name" => _($column_text)));
		}

		$this->request = $this->orderby."=".$string."*";
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
		
		echo $tproc->process($template);
		}	
	
}

?>
