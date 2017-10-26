<?php

class Search {
	var $tables = array(
	"tickets" =>
		array("customers.name","detail","summary","events.description"),
	"customers" =>
		array("name","address","IC","ITC","comments"),
	"contacts" =>
		array("firstname","lastname","phone","email","customers.name"),
	"contracts" =>
		array("number","customers.name","start_date ",
	"facturation","payment","location","comments"));
	var $taskbar="yes";
	var $tables_select=array();
	var $columns_select=array();
	var $table_scope=null;
	var $column_scope=null;
	var $string=null;
	
	function Search () {
		global $Settings;
		$this->title = "[".$Settings->title."]";
		
		foreach($this->tables as $key => $var) {
			array_push($this->tables_select, array("scope" => $key,"name" => _($key)));
			}
		
	}
	
	function view () {
		global $Settings;
		
		if(isset($this->table_scope)) {
			foreach($this->tables[$this->table_scope] as $var)
				array_push($this->columns_select, array("scope" => $var,"name" => _($var)));
			}
		if(isset($this->column_scope) && $this->column_scope=="customers.name") {
			$customerslist = new Customer();	
			$customerslist->getlist();
		}
		
		
		$manager = new TemplateManager();
		$template =& $manager->prepare("themes/".$Settings->theme."/search.html");
		$tproc = new TemplateProcessor();
		$tproc->set("title", $this->title." Search");
		$tproc->set("theme_url", $Settings->base_url."/themes/".$Settings->theme);
		$tproc->set("encoding", $Settings->encoding);
		$tproc->set("table_name", _($this->table_scope));
		$tproc->set("table_scope", $this->table_scope);
		$tproc->set("column_name", _($this->column_scope));
		$tproc->set("column_scope", $this->column_scope);
		$tproc->set("tables_select", $this->tables_select);
		$tproc->set("columns_select", $this->columns_select);
		$tproc->set("string", $this->string);
		$tproc->set("customerslist", $customerslist->results);
		if($this->taskbar!="no") $tproc->set("taskbar", $this->taskbar);
		$tproc->set("menu", $_SESSION['rights']);
		echo $tproc->process($template);
		
	if(isset($this->table_scope) && isset($this->column_scope) && isset($this->string)) {	
		switch ($this->table_scope) {
			case "tickets":
				if(!isset($_SESSION['ticket'])) {$_SESSION['ticket'] = new Ticket;}
					$ticket=&$_SESSION['ticket'];
				$ticket->search($this->column_scope,$this->string);
			break;
			
			case "customers":
				if(!isset($_SESSION['customer'])) {$_SESSION['customer'] = new Customer;}
					$customer=&$_SESSION['customer'];
				$customer->search($this->column_scope,$this->string);
			break;
			
			case "contacts":
				if(!isset($_SESSION['contact'])) {
					if($Settings->contact_db=="ldap") $_SESSION['contact'] = new Contact_ldap();
					else $_SESSION['contact'] = new Contact;
					}
					$contact=&$_SESSION['contact'];
				$contact->search($this->column_scope,$this->string);
			break;
			
			case "contracts":
				if(!isset($_SESSION['contract'])) {$_SESSION['contract'] = new Contract;}
					$contract=&$_SESSION['contract'];
				$contract->search($this->column_scope,$this->string);
			break;
			}
		}
	}
}
