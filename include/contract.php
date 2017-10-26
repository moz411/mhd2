<?php

global $Settings;

require_once("customer.php");
require_once("contact.php");
require_once("contact_ldap.php");
require_once("item.php");


class Contract extends generic_db {
	var $table="contracts";
	var $title=null;
	var $status="open";
	var $sqlstatus=null;
	var $link=null;
	var $view=null;
	var $taskbar="yes";
	var $available_columns=array(
			"id","customer", "number","start_date","end_date","low_cost","high_cost","margin"
			);
	var $available_status=array("open","all");
	var $scope=null;
	var $string=null;
	
	function Contract () {
		global $Settings;
		$this->title = "[".$Settings->title."]";
		}
		
	function view () {
		global $Settings;

		if (array_search($this->orderby, $this->available_columns) === false) $this->orderby="id";
		if (array_search($this->direction, $this->available_directions) === false) $this->direction="DESC";
		if (array_search($this->status, $this->available_status) === false) $this->status="open";
		
		if($this->status=="open") {$invertstatus="all";$text_link=_("view all contracts");$this->pos=0;}
		else {$invertstatus="open";$text_link=_("view only opened contracts");}
		if($this->direction=="ASC") $invertdirection="DESC";
		else $invertdirection="ASC";
		//if($this->status=="open") $sqlstatus="AND contracts.end_date>'NOW()'";
		$this->limit=$this->pos.",".$Settings->range;
		if($this->status == "open") $this->sqlstatus = "(MAX(items.renewal+ INTERVAL items.duration MONTH)) >= CURDATE()";
		else $this->sqlstatus = "1";
		
		$columns=array();
		foreach($this->available_columns as $column) {
			$column_text=str_replace("_"," ",$column);
			array_push($columns,array(
				"url" => "index.php?whattodo=viewcontracts&status=$this->status&orderby=$column&pos=$this->pos&direction=$invertdirection",
				"name" => _($column_text)));
		}
		
		$count_contracts=new Ticket();
		$count_contracts->sql = "SELECT COUNT(id) AS count FROM $this->table; ";
		//$count_contracts->sql .= "WHERE '1' $sqlstatus GROUP BY contracts.id LIMIT $this->limit; ";
		$count_contracts->query();
		
		$total=$count_contracts->record['count'];
		if(0 > $this->pos) $this->pos=0;
		if($total <= $this->pos) $this->pos=$total;
		if(0 <= ($this->pos-$Settings->range)) $pos1=($this->pos-$Settings->range); else $pos1=0;
		if($total >= ($this->pos+$Settings->range)) $pos2=($this->pos+$Settings->range); else $pos2=$this->pos;
		$lastpage = ($total-$Settings->range);
		
		$links=array(
			array("title" => "<<","link"	=> "index.php?whattodo=viewcontracts&status=$this->status&orderby=$this->orderby&pos=0&direction=$this->direction"),
			array("title" => "<","link"	=> "index.php?whattodo=viewcontracts&status=$this->status&orderby=$this->orderby&pos=$pos1&direction=$this->direction"),
			array("title" => $text_link,"link"	=> "index.php?whattodo=viewcontracts&status=$invertstatus&orderby=$this->orderby&pos=$this->pos&direction=$this->direction"),
			array("title" => ">","link"	=> "index.php?whattodo=viewcontracts&status=$this->status&orderby=$this->orderby&pos=$pos2&direction=$this->direction"),
			array("title" => ">>","link"	=> "index.php?whattodo=viewcontracts&status=$this->status&orderby=$this->orderby&pos=$lastpage&direction=$this->direction")
				);
		$link_contract="index.php?whattodo=viewcontracts&status=$this->status&orderby=$this->orderby&pos=$this->pos";
		
			$this->sql = "SELECT contracts.id,contracts.number,UNIX_TIMESTAMP(contracts.start_date) AS start_date, ";
			$this->sql .= "UNIX_TIMESTAMP(MAX(items.renewal+ INTERVAL items.duration MONTH)) AS end_date, ";
			$this->sql .= "SUM(items.low_cost*items.quantity*items.duration) AS total_low_cost_contract, ";
			$this->sql .= "SUM(items.high_cost*items.quantity*items.duration) AS total_high_cost_contract, ";
			$this->sql .= "customers.name AS customer FROM $this->table ";
			$this->sql .= "LEFT JOIN items ON items.contractid=contracts.id ";
			$this->sql .= "LEFT JOIN customers ON customers.id=contracts.cid ";
			$this->sql .= "WHERE '1' GROUP BY contracts.id HAVING $this->sqlstatus ORDER BY $this->orderby $this->direction LIMIT $this->limit; ";
			$this->query();
			
		foreach($this->results as $key => $var) {
			$contractid=$this->results[$key]['id'];
			$bargain=_("bargain");
			$items = new Item;
			$items->sql = "SELECT high_cost FROM items ";
			$items->sql .= "WHERE contractid='$contractid' AND pn='$bargain'; ";
			$items->query();
			$this->results[$key]['total_high_cost_contract'] -= $items->record['high_cost'];
		
			$this->results[$key]['margin']=
				money_format('%.2n', 
					$this->results[$key]['total_high_cost_contract']-$this->results[$key]['total_low_cost_contract']
				);
			$this->results[$key]['total_high_cost_contract'] = money_format('%.2n',$this->results[$key]['total_high_cost_contract']); 
			$this->results[$key]['total_low_cost_contract'] = money_format('%.2n',$this->results[$key]['total_low_cost_contract']);
			}
					
		$manager = new TemplateManager();
		$template =& $manager->prepare("themes/".$Settings->theme."/contract_view.html");
		$tproc = new TemplateProcessor();
		$tproc->set("title", $this->title." contracts");
		$tproc->set("theme_url", $Settings->base_url."/themes/".$Settings->theme);
		$tproc->set("encoding", $Settings->encoding);
		$tproc->set("menu", $_SESSION['rights']);
		$tproc->set("columns", $columns);
		$tproc->set("contracts", $this->results);
		$tproc->set("link_contract", $link_contract);
		$tproc->set("links", $links);
		if($this->taskbar!="no") $tproc->set("taskbar", $this->taskbar);
		echo $tproc->process($template);
	}
	
	function create ($contractid, $cid) {
		global $Settings;
	
		if(isset($contractid) && $contractid != 0) {
		$this->sql ="SELECT contracts.*, UNIX_TIMESTAMP(contracts.start_date) AS start_date FROM $this->table WHERE id='$contractid';";
		$this->query();
		
		$cid=$this->record['cid'];
		
		if($Settings->contact_db=="ldap") {
			$administrative_contact = new Contact_ldap();
			$administrative_contact->request="uid=".$this->record['administrative_contact'];
		} else {
		$administrative_contact = new Contact();
		$administrative_contact->sql  = "SELECT contacts.uid AS uid,contacts.firstname AS firstname, ";
		$administrative_contact->sql .= "contacts.lastname AS lastname FROM contracts ";
		$administrative_contact->sql.="LEFT JOIN contacts ON contacts.uid=contracts.administrative_contact ";
		$administrative_contact->sql .="WHERE contracts.id='$contractid'; ";
		}
		$administrative_contact->query();
		
		if($Settings->contact_db=="ldap") {
			$technical_contact = new Contact_ldap();
			$technical_contact->request="uid=".$this->record['technical_contact'];
		} else {
		$technical_contact = new Contact();
		$technical_contact->sql  = "SELECT contacts.uid AS uid,contacts.firstname AS firstname, ";
		$technical_contact->sql .= "contacts.lastname AS lastname FROM contracts ";
		$technical_contact->sql.="LEFT JOIN contacts ON contacts.uid=contracts.technical_contact ";
		$technical_contact->sql .="WHERE contracts.id='$contractid'; ";
		}
		$technical_contact->query();
		
		if($Settings->contact_db=="ldap") {
			$IC = new Contact_ldap();
			$IC->request="uid=".$this->record['IC'];
		} else {
		$IC = new Contact();
		$IC->sql  = "SELECT contacts.uid AS uid,contacts.firstname AS firstname, ";
		$IC->sql .= "contacts.lastname AS lastname FROM contracts ";
		$IC->sql.="LEFT JOIN contacts ON contacts.uid=contracts.IC ";
		$IC->sql .="WHERE contracts.id='$contractid'; ";
		}
		$IC->query();	
		
		if($Settings->contact_db=="ldap") {
			$ITC = new Contact_ldap();
			$ITC->request="uid=".$this->record['ITC'];
		} else {
			$ITC = new Contact();
			$ITC->sql  = "SELECT contacts.uid AS uid,contacts.firstname AS firstname, ";
			$ITC->sql .= "contacts.lastname AS lastname FROM contracts ";
			$ITC->sql.="LEFT JOIN contacts ON contacts.uid=contracts.ITC ";
			$ITC->sql .="WHERE contracts.id='$contractid'; ";
			}
		$ITC->query();
		
		$this->results[0]['theme_url'] = $Settings->base_url."/themes/".$Settings->theme;
		$this->results[0]['start_date'] = date('d/m/Y',$this->results[0]['start_date']);
		}
		
		if($Settings->contact_db=="ldap")
			$contactslist = new Contact_ldap();
		else
			$contactslist = new Contact();
		$contactslist->getlist($contractid);
		
		if($Settings->contact_db=="ldap") {
			$itclist = new Contact_ldap();
			$itclist->request="employeetype=ITC";
		} else {
			$itclist = new Contact();
			$itclist->sql = "SELECT contacts.uid AS uid,contacts.firstname AS firstname, ";
			$itclist->sql .= "contacts.lastname AS lastname FROM contracts ";
			$itclist->sql .="LEFT JOIN contacts ON contacts.cid=contracts.id ";
			$itclist->sql .="WHERE contracts.id='$contractid' AND contacts.job='itc'; ";
			}
		$itclist->query();
		
		if($Settings->contact_db=="ldap") {
			$iclist = new Contact_ldap();
			$iclist->request="employeetype=IC";
		} else {
			$iclist = new Contact();
			$iclist->sql = "SELECT contacts.uid AS uid,contacts.firstname AS firstname, ";
			$iclist->sql .= "contacts.lastname AS lastname FROM contracts ";
			$iclist->sql .="LEFT JOIN contacts ON contacts.cid=contracts.id ";
			$iclist->sql .="WHERE contracts.id='$contractid' AND contacts.job='ic'; ";
			}
		$iclist->query();
		
		if(isset($cid) && $cid != 0) {
			$customer = new Customer();
			$customer->sql = "SELECT id,name FROM customers WHERE id='$cid'; ";	
			$customer->query();
		
		if($Settings->contact_db=="ldap") {
			$contactslist = new Contact_ldap();
			$contactslist->request="o=".$customer->record['name'];
			$contactslist->query();
		} else {
			$contactslist = new Contact();
			$contactslist->getlist($cid);
			}

			$itemslist = new Item();
			$itemslist->getlist($cid);
			}
			
		$customerslist = new Customer();	
		$customerslist->getlist();
		
		$agency = new Customer();	
		$agency->sql="SELECT id,name FROM customers WHERE id='".$this->record['agency']."';";
		$agency->query();
			
		$agencylist = new Customer();
		$agencylist->sql = "SELECT * FROM customers WHERE type='internal';";	
		$agencylist->query();
		
		$link_contract="index.php?whattodo=viewcontracts&type=$this->type&orderby=$this->orderby&pos=$this->pos";
	
	
		$manager = new TemplateManager();
		$template =& $manager->prepare("themes/".$Settings->theme."/contract_addedit.html");
		$tproc = new TemplateProcessor();
		if(isset($contractid) && $contractid != 0) 
			$tproc->set("title", $this->title." Edit contract ".$this->record['number']);
		else 
			$tproc->set("title", $this->title." New contract");
		$tproc->set("theme_url", $Settings->base_url."/themes/".$Settings->theme);
		$tproc->set("encoding", $Settings->encoding);
		$tproc->set("menu", $_SESSION['rights']);
		$tproc->set("contractid", $contractid);
		$tproc->set("cid", $cid);
		$tproc->set("contract", $this->results);
		$tproc->set("administrative_contact", $administrative_contact->record['uid']);
		$tproc->set("administrative_contact_firstname", $administrative_contact->record['firstname']);
		$tproc->set("administrative_contact_lastname", $administrative_contact->record['lastname']);
		$tproc->set("technical_contact", $technical_contact->record['uid']);
		$tproc->set("technical_contact_firstname", $technical_contact->record['firstname']);
		$tproc->set("technical_contact_lastname", $technical_contact->record['lastname']);
		$tproc->set("contactslist", $contactslist->results);
		$tproc->set("IC", $IC->record['uid']);
		$tproc->set("IC_firstname", $IC->record['firstname']);
		$tproc->set("IC_lastname", $IC->record['lastname']);
		$tproc->set("iclist", $iclist->results);
		$tproc->set("ITC", $ITC->record['uid']);
		$tproc->set("ITC_firstname", $ITC->record['firstname']);
		$tproc->set("ITC_lastname", $ITC->record['lastname']);
		$tproc->set("itclist", $itclist->results);
		$tproc->set("customer_name", $customer->record['name']);
		$tproc->set("customerslist", $customerslist->results);
		$tproc->set("agency_id", $agency->record['id']);
		$tproc->set("agency_name", $agency->record['name']);
		$tproc->set("agencylist", $agencylist->results);
		$tproc->set("link_contract", $link_contract);
		if($this->taskbar!="no") $tproc->set("taskbar", $this->taskbar);
		echo $tproc->process($template);
	}
	
	function show ($contractid) {
		global $Settings;

		$this->sql ="SELECT contracts.*,UNIX_TIMESTAMP(contracts.start_date) AS start_date, ";
		$this->sql.="customers.name AS customer FROM contracts ";
		$this->sql.="LEFT JOIN customers ON customers.id=contracts.cid ";
		$this->sql.="WHERE contracts.id='$contractid'; ";	
		$this->query();

		$customer = new Customer();
		$customer->sql ="SELECT customers.id AS cid, customers.name AS name FROM contracts ";
		$customer->sql.="LEFT JOIN customers ON customers.id=contracts.cid ";
		$customer->sql.="WHERE contracts.id='$contractid'; ";	
		$customer->query();

				
		if($Settings->contact_db=="ldap") {
			$administrative_contact = new Contact_ldap();
			$administrative_contact->request="uid=".$this->record['administrative_contact'];
		} else {
		$administrative_contact = new Contact();
		$administrative_contact->sql  = "SELECT contacts.uid AS uid,contacts.firstname AS firstname, ";
		$administrative_contact->sql .= "contacts.lastname AS lastname FROM contracts ";
		$administrative_contact->sql.="LEFT JOIN contacts ON contacts.uid=contracts.administrative_contact ";
		$administrative_contact->sql .="WHERE contracts.id='$contractid'; ";
		}
		$administrative_contact->query();
		
		if($Settings->contact_db=="ldap") {
			$technical_contact = new Contact_ldap();
			$technical_contact->request="uid=".$this->record['technical_contact'];
		} else {
		$technical_contact = new Contact();
		$technical_contact->sql  = "SELECT contacts.uid AS uid,contacts.firstname AS firstname, ";
		$technical_contact->sql .= "contacts.lastname AS lastname FROM contracts ";
		$technical_contact->sql.="LEFT JOIN contacts ON contacts.uid=contracts.technical_contact ";
		$technical_contact->sql .="WHERE contracts.id='$contractid'; ";
		}
		$technical_contact->query();
		
		if($Settings->contact_db=="ldap") {
			$IC = new Contact_ldap();
			$IC->request="uid=".$this->record['IC'];
		} else {
		$IC = new Contact();
		$IC->sql  = "SELECT contacts.uid AS uid,contacts.firstname AS firstname, ";
		$IC->sql .= "contacts.lastname AS lastname FROM contracts ";
		$IC->sql.="LEFT JOIN contacts ON contacts.uid=contracts.IC ";
		$IC->sql .="WHERE contracts.id='$contractid'; ";
		}
		$IC->query();	
		
		if($Settings->contact_db=="ldap") {
			$ITC = new Contact_ldap();
			$ITC->request="uid=".$this->record['ITC'];
		} else {
		$ITC = new Contact();
		$ITC->sql  = "SELECT contacts.uid AS uid,contacts.firstname AS firstname, ";
		$ITC->sql .= "contacts.lastname AS lastname FROM contracts ";
		$ITC->sql.="LEFT JOIN contacts ON contacts.uid=contracts.ITC ";
		$ITC->sql .="WHERE contracts.id='$contractid'; ";
		}
		$ITC->query();
	
		$agency = new Customer();	
		$agency->sql="SELECT id,name FROM customers WHERE id='".$this->record['agency']."';";
		$agency->query();
	
		$items_soft = new Item;
		$items_soft->sql = "SELECT items.*, UNIX_TIMESTAMP(renewal) AS renewal FROM items ";
		$items_soft->sql .= "WHERE contractid='$contractid' AND type='software' ORDER BY 'type'; ";
		$items_soft->query();
		
		$total_low_cost_software=0;
		$total_high_cost_software=0;
		foreach($items_soft->results as $key => $var) {
			if($items_soft->results[$key]['pn'] == _("bargain")) {
				$total_high_cost_software -= $items_soft->results[$key]['high_cost'];
			} else {
				$total_low_cost_software += ($items_soft->results[$key]['low_cost']*$items_soft->results[$key]['quantity']*$items_soft->results[$key]['duration']);
				$total_high_cost_software += ($items_soft->results[$key]['high_cost']*$items_soft->results[$key]['quantity']*$items_soft->results[$key]['duration']);
				}
			}
	
		$items_hard = new Item;
		$items_hard->sql = "SELECT items.*, UNIX_TIMESTAMP(renewal) AS renewal FROM items ";
		$items_hard->sql .= "WHERE contractid='$contractid' AND type='hardware' ORDER BY 'type'; ";
		$items_hard->query();
		
		$total_low_cost_hardware=0;
		$total_high_cost_hardware=0;
		foreach($items_hard->results as $key => $var) {
			if($items_hard->results[$key]['pn'] == _("bargain")) {
				$total_high_cost_hardware -= $items_hard->results[$key]['high_cost'];
			} else {
				$total_low_cost_hardware += ($items_hard->results[$key]['low_cost']*$items_hard->results[$key]['quantity']*$items_hard->results[$key]['duration']);
				$total_high_cost_hardware += ($items_hard->results[$key]['high_cost']*$items_hard->results[$key]['quantity']*$items_hard->results[$key]['duration']);
				}
			}
			
		$total_low_cost_contract=$total_low_cost_software+$total_low_cost_hardware;
		$total_high_cost_contract=$total_high_cost_software+$total_high_cost_hardware;
		$margin=$total_high_cost_contract-$total_low_cost_contract;
		
		$file=new File;
		$file->dir="contract/".$this->record['id'];
		$files=$file->view();
		
		//$mail=new Mail;
		//$mails=$mail->view("contract/".$this->record['id']);
			
		$link_contract="index.php?whattodo=viewcontracts&status=$this->status&orderby=$this->orderby&pos=$this->pos";
		
		$manager = new TemplateManager();
		$template =& $manager->prepare("themes/".$Settings->theme."/contract_show.html");
		
		$tproc = new TemplateProcessor();
		$tproc->set("title", $this->title." Contract ".$this->record['number']."");
		$tproc->set("theme_url", $Settings->base_url."/themes/".$Settings->theme);
		$tproc->set("encoding", $Settings->encoding);
		$tproc->set("menu", $_SESSION['rights']);
		$tproc->set("contract", $this->results);
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
		$tproc->set("agency_id", $agency->record['id']);
		$tproc->set("agency_name", $agency->record['name']);
		$tproc->set("items_soft", $items_soft->results);
		$tproc->set("items_hard", $items_hard->results);
		$tproc->set("total_low_cost_software", money_format('%.2n', $total_low_cost_software));
		$tproc->set("total_high_cost_software",  money_format('%.2n', $total_high_cost_software));
		$tproc->set("total_low_cost_hardware",  money_format('%.2n', $total_low_cost_hardware));
		$tproc->set("total_high_cost_hardware",  money_format('%.2n', $total_high_cost_hardware));
		$tproc->set("total_low_cost_contract",  money_format('%.2n', $total_low_cost_contract));
		$tproc->set("total_high_cost_contract",  money_format('%.2n', $total_high_cost_contract));
		$tproc->set("margin",  money_format('%.2n', $margin));
		$tproc->set("files", $files);
		//$tproc->set("mails", $mails);
		$tproc->set("max_file_size", $Settings->max_file_size);
		$tproc->set("link_contract", $link_contract);
		$tproc->set("contractid", $contractid);;
		if($this->taskbar!="no") $tproc->set("taskbar", $this->taskbar);
		echo $tproc->process($template);
	}
	
	function update ($datas) {
		if(!is_array($datas)) error("update contract : this is not an array !");
		
		$source=array_clean($datas, "submit");
		$source=array_clean($source, "commit");
		
		$contractid=$source['id'];
		
		$this->sql ="SELECT * FROM contracts WHERE id='$contractid';";
		$this->query();
		
		$diffs = array_diff_assoc($source,$this->results['0']);
		
		if(count($diffs) >0) {
			$update=new Contract();
			$update->sql="UPDATE $this->table SET ";
			foreach($diffs as $key=>$val)
				$update->sql.="$key='$val',";
			$update->sql=substr("$update->sql", 0, -1);
			$update->sql.=" WHERE id='$contractid';";
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
				"url" => "index.php?whattodo=viewcontracts&status=$this->status&orderby=$column&pos=$this->pos&direction=$invertdirection",
				"name" => _($column_text)));
		}
			if($scope != "customers.name") $scope = "contracts.".$scope;
			$this->sql = "SELECT contracts.id,contracts.number,UNIX_TIMESTAMP(contracts.start_date) AS start_date, ";
			$this->sql .= "UNIX_TIMESTAMP(MAX(items.renewal)) AS end_date, ";
			$this->sql .= "customers.name AS customer FROM $this->table ";
			$this->sql .= "LEFT JOIN items ON items.contractid=contracts.id ";
			$this->sql .= "LEFT JOIN customers ON customers.id=contracts.cid ";
			$this->sql .= "WHERE MATCH ($scope) AGAINST ('$string') GROUP BY contracts.id ORDER BY contracts.id DESC;";
			$this->query();
			
			
		$manager = new TemplateManager();
		$template =& $manager->prepare("themes/".$Settings->theme."/contract_view.html");
		$tproc = new TemplateProcessor();
		$tproc->set("title", $this->title." contracts");
		$tproc->set("theme_url", $Settings->base_url."/themes/".$Settings->theme);
		$tproc->set("encoding", $Settings->encoding);
		$tproc->set("columns", $columns);
		$tproc->set("contracts", $this->results);
		$tproc->set("link_contract", $link_contract);
		$tproc->set("links", $links);
		if($this->taskbar!="no") $tproc->set("taskbar", $this->taskbar);
		$this->view = $tproc->process($template);
		}
		echo $this->view;
	}
	
	function getlist ($cid) {
		$this->sql = "SELECT * FROM $this->table WHERE cid = '$cid' ORDER BY id;";	
		$this->query();
		}
	
	function export_csv () {
		$itemslist = new Item;
		$itemslist->sql = "SELECT * FROM items WHERE contractid = '".$this->record['id']."' ORDER BY id;";	
		$itemslist->query();
		//print_r_html($this);
		$csv='';
		foreach($itemslist->results as $key => $val) {
				$csv .= "\"{$itemslist->results[$key]['id']}\";";
				$csv .= "\"{$itemslist->results[$key]['contractid']}\";";
				$csv .= "\"{$itemslist->results[$key]['type']}\";";
				$csv .= "\"{$itemslist->results[$key]['pn']}\";";
				$csv .= "\"{$itemslist->results[$key]['sn']}\";";
				$csv .= "\"{$itemslist->results[$key]['hostid']}\";";
				$csv .= "\"{$itemslist->results[$key]['designation']}\";";
				$csv .= "\"{$itemslist->results[$key]['renewal']}\";";
				$csv .= "\"{$itemslist->results[$key]['duration']}\";";
				$csv .= "\"{$itemslist->results[$key]['quantity']}\";";
				$csv .= "\"{$itemslist->results[$key]['high_cost']}\";";
				$csv .= "\"{$itemslist->results[$key]['low_cost']}\";";
				$csv .= "\"{$itemslist->results[$key]['answer_time']}\";";
				$csv .= "\"{$itemslist->results[$key]['answer_type']}\";";
				$csv .= "\"{$itemslist->results[$key]['comments']}\"\n";
						}
		if(ob_get_contents())
				error('Some data has already been output, can\'t send CSV file');
			if(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'],'MSIE'))
				header('Content-Type: application/force-download');
			else
				header('Content-Type: application/octet-stream');
			if(headers_sent())
				error('Some data has already been output to browser, can\'t send PDF file');
			header('Content-Length: '.strlen($csv));
			header('Content-disposition: attachment; filename="Contract_'.$this->record['number'].'.csv"');
			echo $csv;
		}
	
	function print_contract () {
		global $Settings;

		$contract_vars=new Item();
		$contract_vars->sql = "SELECT UNIX_TIMESTAMP(MAX(renewal+ INTERVAL duration MONTH)) AS end_date, ";
		$contract_vars->sql .= "SUM(low_cost*quantity*duration) AS total_low_cost_contract, ";
		$contract_vars->sql .= "SUM(high_cost*quantity*duration) AS total_high_cost_contract ";
		$contract_vars->sql .= "FROM items WHERE contractid=".$this->record['id'].";";
		$contract_vars->query();
		
		$items=new Item();
		$items->sql = "SELECT pn,sn,hostid,designation,renewal,duration,quantity,high_cost ";
		$items->sql .="FROM items WHERE contractid=".$this->record['id']." ORDER BY id;";
		$items->query();
		
		$customer = new Customer();
		$customer->sql ="SELECT customers.* FROM contracts ";
		$customer->sql.="LEFT JOIN customers ON customers.id=contracts.cid ";
		$customer->sql.="WHERE contracts.id='".$this->record['id']."'; ";	
		$customer->query();
		
		if($Settings->contact_db=="ldap") {
			$administrative_contact = new Contact_ldap();
			$administrative_contact->request="uid=".$this->record['administrative_contact'];
		} else {
			$administrative_contact = new Contact();
			$administrative_contact->sql ="SELECT contacts.uid AS uid, contacts.firstname AS firstname, contacts.lastname AS lastname, ";
			$administrative_contact->sql.="contacts.phone AS phone, contacts.email AS email FROM contacts ";
			$administrative_contact->sql.="WHERE contacts.uid='".$this->record['administrative_contact']."'; ";
		}
		$administrative_contact->query();
		
		if($Settings->contact_db=="ldap") {
			$technical_contact = new Contact_ldap();
			$technical_contact->request="uid=".$this->record['technical_contact'];
		} else {
			$technical_contact = new Contact();
			$technical_contact->sql ="SELECT contacts.uid AS uid, contacts.firstname AS firstname, contacts.lastname AS lastname, ";
			$technical_contact->sql.="contacts.phone AS phone, contacts.email AS email FROM contacts ";
			$technical_contact->sql.="WHERE contacts.uid='".$this->record['technical_contact']."'; ";
		}
		$technical_contact->query();
		
		$agency=new Customer();	
		$agency->sql="SELECT * FROM customers WHERE id='".$this->record['agency']."';";
		$agency->query();
		
		$corp=new Customer();	
		$corp->sql="SELECT * FROM customers WHERE comments='Corporate business';";
		$corp->query();
		

		$agency=array(
			"name" => "APX Ile de France",
			"address" => "Bâtiment Maersk - 35 ter avenue André Morizet - 92100 Boulogne Billancourt",
			"url" => "http://www.apx.fr",
			"phone" => "Tel.: 01 41 31 92 05",
			"fax" => "01 41 31 04 94",
			"corp_name" => "APX Computer Siège Social",
			"corp_address" => "Europarc - 8 allée Joliot Curie - 69 791 Saint Priest",
			"corp_phone" => "Tel.: +33 (0)4-72-47-83-83",
			"corp_siret" => "N° SIRET: 348 358 888 000 41",
			"corp_siren" => "N° SIREN: B348 358 888 au RCS Lyon",
			"corp_sa" => "S.A. au capital de 3.009.272 euros",
			"director" => "Bruno Lampe",
			"director_job" => "Directeur Général"
		);


		$pdf=new Pdf_contract();
		$pdf->AliasNbPages();
		
		//page 1
		$pdf->AddPage('P');
		$pdf->Image('themes/'.$Settings->theme.'/images/header.jpg',0,0);
		$pdf->SetY(100);
    $pdf->SetFont('Arial','B',15);
		$pdf->SetDrawColor(0,0,255);
		$pdf->SetTextColor(0,0,255);
		$pdf->SetLineWidth(1);
    $pdf->Cell(0,10,"Contrat de maintenance",1,0,'C');
    $pdf->Ln(20);
		$pdf->SetFont('Arial','',10);
		$pdf->SetDrawColor(0,0,0);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetLineWidth(0.5);
		$pdf->Cell(40,5,"client",1,0,'',0,1);
		$pdf->SetTextColor(0,0,255);
		$pdf->Cell(0,5,utf8_decode($customer->record['name']),1,1);
		$pdf->SetTextColor(0,0,0);
		$pdf->Cell(40,5,"numéro de contrat",1,0,'',0,1);
		$pdf->SetTextColor(0,0,255);
		$pdf->Cell(0,5,$this->record['number'],1,1);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetY(150);
		$pdf->SetFont('Arial','B',10);
		$pdf->SetTextColor(0,0,255);
		$pdf->Cell(0,5,$agency->record['name'],'LTR',1);
		$pdf->SetTextColor(0,0,0);
		$pdf->Cell(0,5,$agency->record['address'],'LR',1);
		$pdf->SetFont('Arial','',10);
		$pdf->Cell(0,5,$agency->record['url'],'LR',1);
		$pdf->Cell(0,5,$agency->record['phone'],'LR',1);
		$pdf->Cell(0,5,$agency->record['fax'],'LR',1);
		$pdf->Cell(0,15,'','LR',1);
		$pdf->SetFont('Arial','B',10);
		$pdf->SetTextColor(0,0,255);
		$pdf->Cell(0,5,$corp->record['name'],'LR',1);
		$pdf->SetTextColor(0,0,0);
		$pdf->Cell(0,5,$corp->record['address'],'LR',1);
		$pdf->SetFont('Arial','',10);
		$pdf->Cell(0,5,$corp->record['phone'],'LR',1);
		$pdf->Cell(0,5,$corp->record['siret'],'LR',1);
		$pdf->Cell(0,5,$corp->record['siren'],'LR',1);
		$pdf->Cell(0,5,$corp->record['sa'],'LRB',1);
		$pdf->Image('themes/'.$Settings->theme.'/images/logo.png',150,260);
		
		//page 2
		$pdf->AddPage('P');
		$pdf->Image('themes/'.$Settings->theme.'/images/logo.png',150,10);
		$pdf->SetY(20);
    $pdf->SetFont('Arial','B',20);
		$pdf->SetTextColor(0,0,255);
		$pdf->Cell(0,10,"Contrat N°".$this->record['number'],0,0,'C');
		$pdf->SetY(60);
    $pdf->SetFont('Arial','',10);
		$pdf->SetTextColor(0,0,0);
		$pdf->Cell(0,15,"Ce contrat est conclu",0,1);
		$pdf->Cell(0,20,"ENTRE",0,1);
		$pdf->SetX(30);
		$pdf->SetTextColor(0,0,255);
		$pdf->Cell(0,5,$agency->record['name'],0,1);
		$pdf->SetX(30);	
		$pdf->Cell(0,5,$agency->record['address'],0,1);
    $pdf->Ln(8);
		$pdf->SetTextColor(0,0,0);
		$pdf->Cell(35,5,"ci après dénommé : ",0,0);
		$pdf->SetTextColor(0,0,255);
		$pdf->Cell(0,5,"APX Computer",0,1);
		$pdf->SetTextColor(0,0,0);
		$pdf->Cell(0,5,"d'une part",0,1);
    $pdf->Ln(8);
		$pdf->Cell(0,20,"ET",0,1);
		$pdf->SetX(30);
		$pdf->SetTextColor(0,0,255);
		$pdf->Cell(0,5,utf8_decode($customer->record['name']),0,1);
		$pdf->SetX(30);	
		$pdf->Cell(0,5,utf8_decode($customer->record['address']),0,1);	
    $pdf->Ln(8);
		$pdf->SetTextColor(0,0,0);
		$pdf->Cell(35,5,"ci après dénommé : ",0,0);
		$pdf->SetTextColor(0,0,255);
		$pdf->Cell(0,5,"le Client",0,1);
		$pdf->SetTextColor(0,0,0);
		$pdf->Cell(0,5,"d'autre part",0,1);	
    $pdf->Ln(8);
		$pdf->Cell(0,5,"Le présent Contrat et l'ensemble de ses Annexes constituent le cadre intégral et exclusif des parties quant à son objet",0,1);	
		$pdf->Ln(20);
    $pdf->SetFont('Arial','B',10);
		$pdf->SetTextColor(0,0,255);
		$pdf->Cell(100,5,utf8_decode($agency->record['name']),0,0);
		$pdf->Cell(0,5,"le Client",0,1);
    $pdf->SetFont('Arial','',10);
		$pdf->SetTextColor(0,0,0);
		$pdf->Cell(100,5,"(signature autorisée)",0,0);
		$pdf->Cell(0,5,"(signature autorisée)",0,1);
		$pdf->Ln();
		$pdf->Cell(100,5,"Nom :    ".utf8_decode($agency_contact->record['firstname'])." ".utf8_decode($agency_contact->record['laststname']),0,0);
		$pdf->Cell(0,5,"Nom :    ".utf8_decode($administrative_contact->record['firstname'])." ".utf8_decode($administrative_contact->record['lastname']),0,1);
		$pdf->Cell(100,5,"Titre :    ".$agency_contact->record['job'],0,0);
		$pdf->Cell(0,5,"Titre :    ".utf8_decode($administrative_contact->record['job']),0,1);
		$pdf->Cell(100,5,"Date :    ",0,0);
		$pdf->Cell(0,5,"Date :    ",0,1);
		$pdf->Ln(20);
    $pdf->SetFont('Arial','I',8);
		$pdf->Cell(100,5,"Cachet et signature",0,0);
		$pdf->Cell(0,5,"Cachet et signature",0,1);
		
		//Annexe A
		$pdf->AddPage('P');
		$pdf->Image('themes/'.$Settings->theme.'/images/logo.png',150,10);
		$pdf->SetY(20);
    $pdf->SetFont('Arial','B',20);
		$pdf->SetTextColor(0,0,255);
		$pdf->Cell(0,10,"Annexe A",0,0,'C');
		$pdf->SetY(60);
		$pdf->SetFont('Arial','',10);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetLineWidth(0.5);
		$pdf->Cell(40,10,"client",1,0);
		$pdf->SetTextColor(0,0,255);
		$pdf->Cell(0,10,utf8_decode($customer->record['name']),1,1);
		$pdf->SetTextColor(0,0,0);
		$pdf->Cell(40,10,"numéro de contrat",1,0);
		$pdf->SetTextColor(0,0,255);
		$pdf->Cell(0,10,$this->record['number'],1,1);
		$pdf->SetTextColor(0,0,0);
		$pdf->Cell(40,10,"Facturation",1,0);
		$pdf->Cell(0,10,utf8_decode($this->record['facturation']),1,1);
		$pdf->Cell(40,10,"Mode de paiement",1,0);
		$pdf->Cell(0,10,utf8_decode($this->record['payment']),1,1);
		$pdf->Ln(30);
		$pdf->SetFont('Arial','B',10);
		$pdf->SetTextColor(0,0,255);
		$pdf->Cell(0,5,"Adresses des installations objet du présent contrat",0,0);
		$pdf->Ln(10);
		$pdf->SetFont('Arial','',10);
		$pdf->SetTextColor(0,0,0);
		$pdf->Cell(40,10,"Adresse",1,0);
		$pdf->Cell(0,10,utf8_decode($this->record['delivery_address']),1,1);
		$pdf->Cell(40,10,"contact",1,0,'',0,1);
		$pdf->Cell(0,10,utf8_decode($technical_contact->record['firstname']." ".$technical_contact->record['lastname']),1,1);
		$pdf->Cell(40,10,"téléphone",1,0,'',0,1);
		$pdf->Cell(0,10,utf8_decode($technical_contact->record['phone']),1,1);
		$pdf->Cell(40,10,"télécopie",1,0,'',0,1);
		$pdf->Cell(0,10,utf8_decode($technical_contact->record['fax']),1,1);
		$pdf->Cell(40,10,"email",1,0,'',0,1);
		$pdf->Cell(0,10,utf8_decode($technical_contact->record['email']),1,1);

		//Items
		$step=20;
		$pointer=0;
		while($pointer < count($items->results)) {
			$pdf->AddPage('L');
    	$pdf->SetFont('Arial','B',20);
			$pdf->SetTextColor(0,0,255);
			$pdf->Cell(0,10,"Liste des produits/modules objets du présent contrat",0,1,'C');
			$pdf->Ln(30);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetLineWidth(0.5);
			$pdf->SetFont('Arial','B',10);
			$pdf->Cell(30,5,"PN",1,0,'C');
			$pdf->Cell(20,5,"SN",1,0,'C');
			$pdf->Cell(20,5,"Hostid",1,0,'C');
			$pdf->Cell(160,5,"Désignation",1,0,'C');
			$pdf->Cell(20,5,"Renouv.",1,0,'C');
			$pdf->Cell(8,5,"Qté",1,0,'C');
			$pdf->Cell(25,5,"Prix mensuel",1,1,'C');
		
			$pdf->SetFont('Arial','',10);
		
			for($i=0;$i<$step;$i++) {
				$pdf->Cell(30,5,$items->results[$pointer]['pn'],1,0);
				$pdf->Cell(20,5,$items->results[$pointer]['sn'],1,0);
				$pdf->Cell(20,5,$items->results[$pointer]['hostid'],1,0);
				$pdf->Cell(160,5,utf8_decode($items->results[$pointer]['designation']),1,0);
				$pdf->Cell(20,5,$items->results[$pointer]['renewal'],1,0);
				$pdf->Cell(8,5,$items->results[$pointer]['quantity'],1,0);
				$pdf->Cell(25,5,money_format('%.2i', $items->results[$pointer]['high_cost']),1,1);
				$pointer++;
				if($pointer == count($items->results)) break;
				}
			}
    $pdf->SetFont('Arial','B',10);
		$pdf->Cell(258,5,"Coût total du contrat",1,0,'C');
		$pdf->Cell(25,5,money_format('%.2i', $contract_vars->record['total_high_cost_contract']),1,1);
		
		//Annexe B
		$pdf->AddPage('P');
		$pdf->Image('themes/'.$Settings->theme.'/images/logo.png',150,10);
		$pdf->SetY(20);
    $pdf->SetFont('Arial','B',20);
		$pdf->SetTextColor(0,0,255);
		$pdf->Cell(0,10,"Annexe B",0,0,'C');
		$pdf->SetY(60);
		$pdf->SetFont('Arial','',10);
		$pdf->SetTextColor(0,0,0);
		$pdf->Cell(40,10,"La présente annexe fait partie intégrante du contrat de maintenance entre APX Computer et le client.",0,1);
		$pdf->SetTextColor(0,0,255);
		$pdf->Cell(40,10,"client :",0,0);
		$pdf->SetTextColor(0,0,0);
		$pdf->Cell(0,10,utf8_decode($customer->record['name']),0,1);
		$pdf->SetTextColor(0,0,255);
		$pdf->Cell(40,10,"Adresse de l'installation :",0,0);
		$pdf->SetTextColor(0,0,0);
		$pdf->Cell(0,10,utf8_decode($this->record['delivery_address']),0,1);
		$pdf->SetTextColor(0,0,255);
		$pdf->Cell(40,10,"Responsable du site :",0,0);
		$pdf->SetTextColor(0,0,0);
		$pdf->Cell(0,10,utf8_decode($technical_contact->record['name']),0,1);
		$pdf->SetTextColor(0,0,255);
		$pdf->Cell(50,10,"Date d'entrée en vigueur :",0,0);
		$pdf->SetTextColor(0,0,0);
		$pdf->Cell(0,10,utf8_decode(strftime("%a %d %b %Y", $this->record['start_date'])),0,1);
		$pdf->SetTextColor(0,0,255);
		$pdf->Cell(40,10,"Date d'expiration :",0,0);
		$pdf->SetTextColor(0,0,0);
		$pdf->Cell(0,10,utf8_decode(strftime("%a %d %b %Y", $contract_vars->record['end_date'])),0,1);
		$pdf->SetTextColor(0,0,255);
		$pdf->Cell(70,10,"Montant total en euros de votre contrat :",0,0);
		$pdf->SetTextColor(0,0,0);
		$pdf->Cell(0,10,money_format('%.2i', $contract_vars->record['total_high_cost_contract']),0,1);
		$pdf->SetTextColor(0,0,255);
		$pdf->Cell(40,10,"Facturation :",0,0);
		$pdf->SetTextColor(0,0,0);
		$pdf->Cell(0,10,utf8_decode($this->record['facturation']),0,1);
		$pdf->SetTextColor(0,0,255);
		$pdf->Cell(40,10,"Mode de paiement :",0,0);
		$pdf->SetTextColor(0,0,0);
		$pdf->Cell(0,10,utf8_decode($this->record['payment']),0,1);
		$pdf->SetTextColor(0,0,255);
		$pdf->Cell(40,10,"Délai d'intervention :",0,0);
		$pdf->SetTextColor(0,0,0);
		$value=utf8_decode($this->record['comments']);
		$pdf->MultiCell(0,5,$value,0,1);
		$pdf->Ln(20);
    $pdf->SetFont('Arial','B',10);
		$pdf->Cell(100,5,"D'ordre et pour compte :",0,0);
		$pdf->Cell(0,5,"D'ordre et pour compte :",0,1);
		$pdf->SetTextColor(0,0,255);
		$pdf->Cell(100,5,"APX Computer",0,0);
		$pdf->Cell(0,5,"le Client",0,1);
    $pdf->SetFont('Arial','',10);
		$pdf->SetTextColor(0,0,0);
		$pdf->Cell(100,5,"(signature autorisée)",0,0);
		$pdf->Cell(0,5,"(signature autorisée)",0,1);
		$pdf->Ln();
		$pdf->Cell(100,5,"Nom :    ".utf8_decode($agency_contact->record['firstname'])." ".utf8_decode($agency_contact->record['laststname']),0,0);
		$pdf->Cell(0,5,"Nom :    ".utf8_decode($administrative_contact->record['firstname']." ".$administrative_contact->record['lastname']),0,1);
		$pdf->Cell(100,5,"Titre :    ".utf8_decode($agency_contact->record['job']),0,0);
		$pdf->Cell(0,5,"Titre :    ".utf8_decode($administrative_contact->record['job']),0,1);
		$pdf->Cell(100,5,"Date :    ",0,0);
		$pdf->Cell(0,5,"Date :    ",0,1);
		$pdf->Cell(100,5,"Signature :    ",0,0);
		$pdf->Cell(0,5,"Signature :    ",0,1);
    $pdf->SetFont('Arial','I',8);
		$pdf->Cell(100,5,"Précédée de la mention: \"lu et approuvé\"",0,0);
		$pdf->Cell(0,5,"Précédée de la mention: \"lu et approuvé\"",0,1);

		///conditions générales soft
		$pagecount = $pdf->setSourceFile("/var/www".$Settings->doc_dir."conditions_generales_soft.pdf");
		$tplidx = $pdf->ImportPage(1);
		$pdf->addPage();
		$pdf->useTemplate($tplidx);
		
		
		$pdf->Output("Contrat_".$this->record['number'].".pdf",'I');
		}
}
