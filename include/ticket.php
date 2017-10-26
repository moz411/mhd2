<?php

global $Settings;

require_once("events.php");
require_once("contact.php");
require_once("contact_ldap.php");
require_once("item.php");
/*
Here are functions related to tickets : show (1 ticket), view (all tickets by range)
create (create new ticket), edit (edit existing)
Every function call its respective html template

*/

class Ticket extends generic_db {

	var $status="open";
	var $sqlstatus=null;
	var $link=null;
	var $taskbar="yes";
	var $available_columns=array(
			"id","customers.name", "summary","category","priority","opened_by", 
			"assigned_to","date_opened","date_updated","date_closed","status"
			);
	var $available_status=array("open","all");
	var $priorities=array();
	var $view=null;
	var $timeout=null;
	var $scope=null;
	var $string=null;
		
	
	
	function Ticket () {
		global $Settings;
		$this->table = "tickets";
		$this->title = "[".$Settings->title."]";
		}
	
	function view () {
		global $Settings;

		if(count($this->results) <= 0 
				|| (isset ($_GET['pos']) && $_GET['pos'] != $this->pos)
				|| (isset ($_GET['status']) && $_GET['status'] != $this->status)
				|| (isset ($_GET['orderby']) && $_GET['orderby'] != $this->orderby)
				|| (isset ($_GET['direction']) && $_GET['direction'] != $this->direction)
				|| $this->timeout == 0 )
			{
		
		if (isset ($_GET['pos'])) $this->pos=$_GET['pos'];
		if (isset ($_GET['status'])) $this->status=$_GET['status'];
		if (isset ($_GET['orderby'])) $this->orderby=$_GET['orderby'];
		if (isset ($_GET['direction'])) $this->direction=$_GET['direction'];
			
		if (array_search($this->orderby, $this->available_columns) === false) $this->orderby="id";
		if (array_search($this->direction, $this->available_directions) === false) $this->direction="DESC";
		if (array_search($this->status, $this->available_status) === false) $this->status="open";
		
			
		if($this->status=="open") {$invertstatus="all";$text_link=_("view all tickets");}
		else {$invertstatus="open";$text_link=_("view only opened tickets");}
		if($this->direction=="ASC") $invertdirection="DESC";
		else $invertdirection="ASC";
		if($this->status=="open") $this->sqlstatus="AND tickets.status='$this->status'";
		else $this->sqlstatus="";
		$this->limit=$this->pos.",".$Settings->range;
		
		if($this->status=="open") {
			$this->available_columns=array(
			"id","customers.name", "summary","category","priority","opened_by", 
			"assigned_to","date_opened","date_updated");
		} else {
			$this->available_columns=array(
			"id","customers.name", "summary","category","priority","opened_by", 
			"assigned_to","date_opened","date_updated","date_closed","status");
		}
		
		$columns=array();
		foreach($this->available_columns as $column) {
			$column_text=str_replace("_"," ",$column);
			array_push($columns,array(
				"url" => "index.php?whattodo=viewjobs&status=$this->status&orderby=$column&pos=$this->pos&direction=$invertdirection",
				"name" => _($column_text)));
		}

		$priorities=array(
    	array( "text" => _("low"),  "color" => "#FFFFFF" ),
    	array( "text" => _("normal"),  "color" => "#00FF00" ),
    	array( "text" => _("high"),  "color" => "#FFFF00" ),
    	array( "text" => _("urgent"), "color" => "#FF0000")
    	);
		$priority=array($priorities[$this->record['priority']]);
	
		$count=new Ticket();
		$count->sql = "SELECT COUNT(id) AS count FROM $this->table 
									 WHERE '1' $this->sqlstatus";
		$count->query();
		$total=$count->record['count'];
			
		$this->sql="SELECT tickets.id, tickets.summary, tickets.category, tickets.priority, 
								tickets.status, tickets.cid,
							  UNIX_TIMESTAMP(date_opened) AS date_opened, UNIX_TIMESTAMP(date_closed) AS date_closed, 
								UNIX_TIMESTAMP(MAX(events.date)) AS date_updated, 
								opened.firstname AS opened_by, closed.firstname AS closed_by, assigned.firstname AS assigned_to 
								FROM tickets LEFT JOIN customers ON customers.id=tickets.cid 
								LEFT JOIN events ON events.tid=tickets.id 
								LEFT JOIN users AS opened ON opened.id=tickets.opened_by 
								LEFT JOIN users AS closed ON closed.id=tickets.closed_by 
								LEFT JOIN users AS assigned ON assigned.id=tickets.assigned_to 
								WHERE '1' $this->sqlstatus GROUP BY tickets.id
								ORDER BY $this->orderby $this->direction LIMIT $this->limit; ";
		$this->query();		
		
		$results1 = $this->results;
		foreach($results1 as $key => $val) {
			
			//$this->sql = "SELECT UNIX_TIMESTAMP(MAX(events.date)) AS date_updated FROM events WHERE tid=".$val['id']."; ";
			//$this->query();
			//$results1[$key]['date_updated'] = $this->record['date_updated'];
			$results1[$key]['priority'] = $priorities[$val['priority']]['text'];
			$results1[$key]['priority_color'] = $priorities[$val['priority']]['color'];
			if (isset($val['cid']) && $val['cid'] != "") {
				$this->sql = "SELECT name FROM customers WHERE '1' AND id='".$val['cid']."'; ";
				$this->query();
				$results1[$key]['customer'] = $this->record['name'];
				}
			if (isset($val['opened_by']) && $val['opened_by'] != "") {
				$results1[$key]['opened_by'] = $val['opened_by'];
				}
			if (isset($val['assigned_to']) && $val['assigned_to'] != "") {
				$results1[$key]['assigned_to'] = $val['assigned_to'];
				}
			if (isset($val['closed_by']) && $val['closed_by'] != "") {
				$results1[$key]['closed_by'] = $val['closed_by'];
				}
			if (isset($val['date_updated']) && $val['date_updated'] != "") {
				$timestamp = (time() - $results1[$key]['date_updated']);
				$results1[$key]['date_updated'] = duration($timestamp);
				$results1[$key]['date_updated_color'] = $priorities[0]['color'];
				//five days
				if ($timestamp >= (60*60*24*5)) $results1[$key]['date_updated_color'] = $priorities[1]['color'];
				//15 days
				if ($timestamp >= (60*60*24*15)) $results1[$key]['date_updated_color'] = $priorities[2]['color'];
				//30 days
				if ($timestamp >= (60*60*24*30)) $results1[$key]['date_updated_color'] = $priorities[3]['color'];
				}
			if (isset($val['status']) && $this->status=="open") {
				unset($results1[$key]['status']);
				unset($results1[$key]['date_closed']);
				}
			}
			
			
			
		if(0 > $this->pos) $this->pos=0;
		if($total <= $this->pos) $this->pos=$total;
		if(0 <= ($this->pos-$Settings->range)) $pos1=($this->pos-$Settings->range); else $pos1=0;
		if($total >= ($this->pos+$Settings->range)) $pos2=($this->pos+$Settings->range); else $pos2=$this->pos;
		$lastpage = ($total-$Settings->range);
		
		$links=array(
			array("title" => "<<","link"	=> "index.php?whattodo=viewjobs&typestatus=$this->status&orderby=$this->orderby&pos=0&direction=$this->direction"),
			array("title" => "<","link"	=> "index.php?whattodo=viewjobs&status=$this->status&orderby=$this->orderby&pos=$pos1&direction=$this->direction"),
			array("title" => $text_link,"link"	=> "index.php?whattodo=viewjobs&status=$invertstatus&orderby=$this->orderby&pos=$this->pos&direction=$this->direction"),
			array("title" => ">","link"	=> "index.php?whattodo=viewjobs&status=$this->status&orderby=$this->orderby&pos=$pos2&direction=$this->direction"),
			array("title" => ">>","link"	=> "index.php?whattodo=viewjobs&typestatus=$this->status&orderby=$this->orderby&pos=$lastpage&direction=$this->direction")
				);
		$link_ticket="index.php?whattodo=viewjobs&status=$this->status&orderby=$this->orderby&pos=$this->pos";
		
		
			
		$manager = new TemplateManager();
		$template =& $manager->prepare("themes/".$Settings->theme."/ticket_view.html");
		$tproc = new TemplateProcessor();
		$tproc->set("title", $this->title." Tickets");
		$tproc->set("theme_url", $Settings->base_url."/themes/".$Settings->theme);
		$tproc->set("encoding", $Settings->encoding);
		$tproc->set("menu", $_SESSION['rights']);
		$tproc->set("timeout", ($Settings->timeout));
		$tproc->set("columns", $columns);
		$tproc->set("tickets", $results1);
		$tproc->set("link_ticket", $link_ticket);
		$tproc->set("links", $links);
		if($this->taskbar!="no") $tproc->set("taskbar", $this->taskbar);
		$this->view = $tproc->process($template);
		}
		echo $this->view;
	}
	
	function show ($tid) {
		global $Settings;

		$this->sql ="SELECT tickets.*, ";
		$this->sql.="opened.id AS opened_id, opened.firstname AS opened_by, ";
		$this->sql.="closed.id AS closed_id,closed.firstname AS closed_by, ";
		$this->sql.="assigned.id AS assigned_id, assigned.firstname AS assigned_to, ";
		$this->sql.="items.designation AS item_designation, items.sn AS item_sn FROM tickets ";
		$this->sql.="LEFT JOIN users AS opened ON opened.id=tickets.opened_by ";
		$this->sql.="LEFT JOIN users AS closed ON closed.id=tickets.closed_by ";
		$this->sql.="LEFT JOIN users AS assigned ON assigned.id=tickets.assigned_to ";
		$this->sql.="LEFT JOIN items ON items.id=tickets.item ";
		$this->sql.="WHERE tickets.id='$tid'; ";	
		$this->query();

		$customer = new Customer();
		$customer->sql ="SELECT customers.id AS cid, customers.name AS name FROM tickets ";
		$customer->sql.="LEFT JOIN customers ON customers.id=tickets.cid ";
		$customer->sql.="WHERE tickets.id='$tid'; ";	
		$customer->query();

		if($Settings->contact_db=="ldap") {
			$contact = new Contact_ldap();
			$contact->request="uid=".$this->record['contact'];
		} else {
			$contact = new Contact();
			$contact->sql ="SELECT contacts.uid AS uid, contacts.firstname AS firstname, contacts.lastname AS lastname, ";
			$contact->sql.="contacts.phone AS phone, contacts.email AS email FROM tickets ";
			$contact->sql.="LEFT JOIN contacts ON contacts.uid=tickets.contact ";
			$contact->sql.="WHERE tickets.id='$tid'; ";
		}
		$contact->query();
		$contact->results[0]['subject'] = "Ticket N".$this->record['id']." : ".$this->record['summary'];
		
		$events = new Event;
		$events->sql = "SELECT events.id, events.description,events.added_by ,UNIX_TIMESTAMP(events.date) AS date, ";
		$events->sql .= "added.firstname AS added_by FROM events ";
		$events->sql .= "LEFT JOIN users AS added ON added.id=events.added_by ";
		$events->sql .= "WHERE events.tid='$tid' ORDER BY events.date ASC; ";
		$events->query();
		
		$priorities=array(
    	array( "text" => "low",  "color" => "#FFFFFF" ),
    	array( "text" => "normal",  "color" => "#00FF00" ),
    	array( "text" => "high",  "color" => "#FFFF00" ),
    	array( "text" => "urgent", "color" => "#FF0000")
    	);
		$priority=array($priorities[$this->record['priority']]);
		
		$file=new File;
		$file->dir="ticket/".$this->record['id'];
		$files=$file->view();
		
		//$mail=new Mail;
		//$mails=$mail->view("ticket/".$this->record['id']);
		
		$link_ticket="index.php?whattodo=viewjobs&status=$this->status&orderby=$this->orderby&pos=$this->pos";
		
		$manager = new TemplateManager();
		$template =& $manager->prepare("themes/".$Settings->theme."/ticket_show.html");
		
		$tproc = new TemplateProcessor();
		$tproc->set("title", $this->title." Ticket N".$this->record['id']."");
		$tproc->set("theme_url", $Settings->base_url."/themes/".$Settings->theme);
		$tproc->set("encoding", $Settings->encoding);
		$tproc->set("menu", $_SESSION['rights']);
		$tproc->set("ticket", $this->results);
		$tproc->set("priority", $priority);
		$tproc->set("customer", $customer->results);
		$tproc->set("contact", $contact->results);
		$tproc->set("events", $events->results);
		$tproc->set("tid", $tid);
		$tproc->set("added_by", $_SESSION['id']);
		$tproc->set("files", $files);
		//$tproc->set("mails", $mails);
		$tproc->set("max_file_size", $Settings->max_file_size);
		$tproc->set("link_ticket", $link_ticket);
		if($this->record['status'] == "close") $tproc->set("close",$this->record['status'] );
		if($this->taskbar!="no") $tproc->set("taskbar", $this->taskbar);
		echo $tproc->process($template);
	}
	
	function create ($tid,$cid) {
		global $Settings;
		
		if(isset($tid) && $tid != 0) {
		$this->sql ="SELECT * FROM tickets WHERE id='$tid';";
		$this->query();

		$user = new User();		
		$user->sql ="SELECT opened.id AS opened_id, opened.firstname AS opened_by, ";
		$user->sql.="closed.id AS closed_id,closed.firstname AS closed_firstname, closed.lastname AS closed_lastname,";
		$user->sql.="assigned.id AS assigned_id, assigned.firstname AS assigned_firstname, assigned.lastname AS assigned_lastname FROM tickets ";
		$user->sql.="LEFT JOIN users AS opened ON opened.id=tickets.opened_by ";
		$user->sql.="LEFT JOIN users AS closed ON closed.id=tickets.closed_by ";
		$user->sql.="LEFT JOIN users AS assigned ON assigned.id=tickets.assigned_to ";
		$user->sql.="WHERE tickets.id='$tid'; ";
		$user->query();

		if($Settings->contact_db=="ldap") {
			$contact = new Contact_ldap();
			$contact->request="uid=".$this->record['contact'];
		} else {
		$contact = new Contact();
		$contact->sql  = "SELECT contacts.uid AS uid,contacts.firstname AS firstname, ";
		$contact->sql .= "contacts.lastname AS lastname FROM tickets ";
		$contact->sql.="LEFT JOIN contacts ON contacts.uid=tickets.contact ";
		$contact->sql .="WHERE tickets.id='$tid'; ";
		}
		$contact->query();

		$item = new Item();
		$item->sql  = "SELECT items.id AS id, items.type AS type, items.designation AS designation, items.sn AS sn ";
		$item->sql .="FROM tickets LEFT JOIN items ON items.id=tickets.item ";	
		$item->sql .="WHERE tickets.id='$tid'; ";	
		$item->query();
		}
		
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

		$userslist = new User();
		$userslist->getlist();

		$customerslist = new Customer();	
		$customerslist->getlist();

		$assigned_to=array();
		foreach($userslist->results as $val) {
			$tmpname=$val['firstname']." ".$val['lastname'];
			$tmparray=array(
				"text" => "$tmpname",
				"value" => $val['id']
				);			
			if($user->record['assigned_id'] == $val['id'])
				$tmparray+=array("checked" => TRUE);
			array_push($assigned_to, $tmparray);
			}
			if(!isset($tid) || $tid == 0) {
				$tmpname=$_SESSION['firstname']." ".$_SESSION['lastname'];
				$tmparray=array(
					"text" => "$tmpname",
					"value" => $_SESSION['id'],
					"checked" => TRUE
					);
			array_push($assigned_to, $tmparray);	
			}
		
		$priority=array(
    	array( "text" => _(low),  "value" => 0 ),
    	array( "text" => _(normal),  "value" => 1 ),
    	array( "text" => _(high),  "value" => 2 ),
    	array( "text" => _(urgent), "value" => 3)
    	);
		$key=$this->record['priority'];
		if(isset($key) && $key !="")
			$priority[$key]['checked'] = TRUE;
		else $priority[1]['checked'] = TRUE;
		
		if(!isset($user->record['opened_id']) || $user->record['opened_id']=="")
			$user->record['opened_id']=$_SESSION['id'];
		
		if(!isset($this->record['status']) || $this->record['status']=="")
			$this->record['status']="open";
			
		$this->link="index.php?whattodo=viewcustomers&type=$this->type&orderby=$this->orderby&pos=$this->pos";
	
		
		$manager = new TemplateManager();
		$template =& $manager->prepare("themes/".$Settings->theme."/ticket_addedit.html");
		$tproc = new TemplateProcessor();
		if(isset($tid) && $tid != 0) 
			$tproc->set("title", $this->title." Edit ticket N".$tid);
		else 
			$tproc->set("title", $this->title." New ticket");
		$tproc->set("theme_url", $Settings->base_url."/themes/".$Settings->theme);
		$tproc->set("encoding", $Settings->encoding);
		$tproc->set("menu", $_SESSION['rights']);
		$tproc->set("tid", $tid);
		$tproc->set("cid", $cid);
		$tproc->set("summary", $this->record['summary']);
		$tproc->set("detail", $this->record['detail']);;
		$tproc->set("category", $this->record['category']);
		$tproc->set("item_id", $item->record['id']);
		$tproc->set("item_designation", $item->record['designation']);
		$tproc->set("item_sn", $item->record['sn']);
		$tproc->set("priority", $priority);
		$tproc->set("opened_id", $user->record['opened_id']);
		$tproc->set("assigned_to", $assigned_to);
		$tproc->set("closed_id", $user->record['closed_id']);
		$tproc->set("customer_name", $customer->record['name']);
		$tproc->set("contact_id", $contact->record['uid']);
		$tproc->set("contact_firstname", $contact->record['firstname']);
		$tproc->set("contact_lastname", $contact->record['lastname']);
		
		$tproc->set("customerslist", $customerslist->results);
		$tproc->set("contactslist", $contactslist->results);
		$tproc->set("userslist", $userslist->results);
		$tproc->set("itemslist", $itemslist->results);
		$tproc->set("status", $this->record['status']);
		$tproc->set("link_ticket", $link_ticket);
		if($this->taskbar!="no") $tproc->set("taskbar", $this->taskbar);
		echo $tproc->process($template);
		}
		
	function update ($datas) {
		if(!is_array($datas)) error("update ticket : this is not an array !");
		
		$source=array_clean($datas, "submit");
		$source=array_clean($source, "commit");
		
		$tid=$source['id'];
		
		$this->sql ="SELECT * FROM tickets WHERE id='$tid';";
		$this->query();
		
		$diffs = array_diff_assoc($source,$this->results['0']);
		
		if(count($diffs) >0) {
			$update=new Ticket();
			$update->sql="UPDATE $this->table SET ";
			foreach($diffs as $key=>$val)
				$update->sql.="$key='$val',";
			$update->sql=substr("$update->sql", 0, -1);
			$update->sql.=" WHERE id='$tid';";
			$update->updaterow();
			}
		}
		
	function changestatus ($status, $tid) {
		$this->sql ="UPDATE $this->table SET status='$status'";
		if($status=="close")
			$this->sql .=",date_closed=NOW() ";
		$this->sql .="WHERE id='$tid';";
		$this->updaterow();
		return;
		}
		
	function search ($scope,$string) {
		global $Settings;

		if($scope != $this->scope || $string != $this->string)
			{
		$this->scope=$scope;
		$this->string=$string;
		
		$priorities=array(
    	array( "text" => _("low"),  "color" => "#FFFFFF" ),
    	array( "text" => _("normal"),  "color" => "#00FF00" ),
    	array( "text" => _("high"),  "color" => "#FFFF00" ),
    	array( "text" => _("urgent"), "color" => "#FF0000")
    	);
		$priority=array($priorities[$this->record['priority']]);
		
		$columns=array();
		foreach($this->available_columns as $column) {
			$column_text=str_replace("_"," ",$column);
			array_push($columns,array(
				"url" => "index.php?whattodo=search&status=$this->status&orderby=$column&pos=$this->pos&direction=$invertdirection",
				"name" => _($column_text)));
		}
		
		$this->sql = "SELECT DISTINCT tickets.id,tickets.summary,tickets.category,tickets.status, ";
		$this->sql .= "tickets.priority,UNIX_TIMESTAMP(tickets.date_opened) AS date_opened, ";
		$this->sql .= "UNIX_TIMESTAMP(tickets.date_closed) AS date_closed, ";
		$this->sql .= "UNIX_TIMESTAMP(MAX(events.date)) AS date_updated, ";
		$this->sql .= "customers.name AS customer, opened.firstname AS opened_by, ";
		$this->sql .= "closed.firstname AS closed_by, assigned.firstname AS assigned_to FROM tickets ";
		$this->sql .= "LEFT JOIN events ON events.tid=tickets.id ";
		$this->sql .= "LEFT JOIN customers ON customers.id=tickets.cid ";
		$this->sql .= "LEFT JOIN users AS opened ON opened.id=tickets.opened_by ";
		$this->sql .= "LEFT JOIN users AS closed ON closed.id=tickets.closed_by ";
		$this->sql .= "LEFT JOIN users AS assigned ON assigned.id=tickets.assigned_to ";
		if($this->scope=="customers.name") {
		$this->sql .= "WHERE customers.name='$this->string' GROUP BY tickets.id ORDER BY tickets.id DESC;";
		} else {
		$this->sql .= "WHERE MATCH ($this->scope) AGAINST ('$this->string') GROUP BY tickets.id ORDER BY tickets.id DESC;";
		}
		$this->query();
		
		$text_link = count($this->results)." results for ".$this->string;
		
		$links=array(
			array("title" => $text_link,"link"	=> "#")
			);
		
					
		$manager = new TemplateManager();
		$template =& $manager->prepare("themes/".$Settings->theme."/ticket_view.html");
		$tproc = new TemplateProcessor();
		$tproc->set("title", $this->title." Tickets");
		$tproc->set("theme_url", $Settings->base_url."/themes/".$Settings->theme);
		$tproc->set("encoding", $Settings->encoding);
		$tproc->set("columns", $columns);
		$tproc->set("priority", $priority);
		$tproc->set("tickets", $this->results);
		$tproc->set("links", $links);
		if($this->taskbar!="no") $tproc->set("taskbar", $this->taskbar);
		$this->view = $tproc->process($template);
		}
		echo $this->view;
	}
		
function print_ticket () {
		global $Settings;
		
		$customer = new Customer();
		$customer->sql ="SELECT customers.id AS cid, customers.name AS name FROM tickets ";
		$customer->sql.="LEFT JOIN customers ON customers.id=tickets.cid ";
		$customer->sql.="WHERE tickets.id='".$this->record['id']."'; ";	
		$customer->query();
		
		if($Settings->contact_db=="ldap") {
			$contact = new Contact_ldap();
			$contact->request="uid=".$this->record['contact'];
		} else {
			$contact = new Contact();
			$contact->sql ="SELECT contacts.uid AS uid, contacts.firstname AS firstname, contacts.lastname AS lastname, ";
			$contact->sql.="contacts.phone AS phone, contacts.email AS email FROM tickets ";
			$contact->sql.="LEFT JOIN contacts ON contacts.uid=tickets.contact ";
			$contact->sql.="WHERE tickets.id='".$this->record['id']."'; ";
		}
		$contact->query();
		
		
		$events = new Event;
		$events->sql = "SELECT events.id, events.description,events.added_by ,UNIX_TIMESTAMP(events.date) AS date, ";
		$events->sql .= "added.firstname AS added_by FROM events ";
		$events->sql .= "LEFT JOIN users AS added ON added.id=events.added_by ";
		$events->sql .= "WHERE events.tid='".$this->record['id']."' ORDER BY events.date ASC; ; ";
		$events->query();
		foreach($contact->results[0] as $key => $val)
			$contact->record[$key] = utf8_decode($val);
		foreach($this->results[0] as $key => $val)
			$this->record[$key] = utf8_decode($val);
			


		$pdf=new Pdf_ticket();
		$pdf->AliasNbPages();
		$pdf->title=utf8_decode($this->title)." Ticket N".$this->record['id']."";
		$pdf->AddPage('P');
		$pdf->Image('themes/'.$Settings->theme.'/images/logo.png',160,8,30,10);
		$pdf->SetFont('Arial','B',16);
		$pdf->Cell(0,10,$pdf->title,0,0,'C');
		$pdf->SetFillColor(214,214,214);
		$pdf->Ln();
		
		$pdf->SetFont('Arial','',10);
		$pdf->Cell(30,5,utf8_decode(_("customer")),1,0,'C',1);
		$pdf->Cell(0,5,$customer->record['name'],1,1);
		$pdf->Cell(30,5,utf8_decode(_("contact")),1,0,'C',1);
		$pdf->Cell(0,5,$contact->record['firstname']." ".$contact->record['lastname'],1,1);
		$pdf->Cell(30,5,utf8_decode(_("phone")),1,0,'C',1);
		$pdf->Cell(0,5,$contact->record['phone'] ,1,1);
		$pdf->Cell(30,5,utf8_decode(_("email")),1,0,'C',1);
		$pdf->Cell(0,5,$contact->record['email'] ,1,1);
		$pdf->Ln();
		
		$pdf->Cell(30,5,utf8_decode(_("summary")),1,0,'C',1);
		$pdf->Cell(0,5,utf8_decode($this->record['summary']),1,1);
		$pdf->Cell(30,5,utf8_decode(_("detail")),1,0,'C',1);
		$pdf->MultiCell(0,5,$this->record['detail'],1,1,'C',1);
		$pdf->Cell(30,5,utf8_decode(_("category")),1,0,'C',1);
		$pdf->Cell(0,5,$this->record['category'],1,1);
		$pdf->Cell(30,5,utf8_decode(_("item")),1,0,'C',1);
		$pdf->Cell(0,5,$this->record['item_designation']." SN : ".$this->record['item_sn'],1,1);
		$pdf->Ln();
		
		$pdf->Cell(30,5,utf8_decode(_("opened at")),1,0,'C',1);
		$pdf->Cell(40,5,utf8_decode($this->record['date_opened']),1,0);
		$pdf->Cell(0,5,utf8_decode(_(" by ")).$this->record['opened_by'],1,1,'',1);
		$pdf->Cell(0,5,utf8_decode(_("assigned to"))." ".$this->record['assigned_to'],1,1);
		$pdf->Cell(30,5,utf8_decode(_("closed at")),1,0,'C',1);
		$pdf->Cell(40,5,_($this->record['date_closed']),1,0);
		$pdf->Cell(0,5,utf8_decode(_(" by ")).$this->record['closed_by'],1,1,'',1);
		$pdf->Cell(30,5,utf8_decode(_("priority")),1,0,'C',1);
		$pdf->Cell(0,5,$this->record['priority'],1,1);
		$pdf->Ln();
		
		$pdf->SetFont('Arial','B',16);
		$pdf->Cell(60,20,utf8_decode(_("Events")),0,1,'C');
		
		$pdf->SetFont('Arial','',10);
		foreach($events->results as $event) {
			$pdf->Cell(40,5,utf8_decode(_("added by"))." ".$event['added_by'],1,0,'C',1);
			$pdf->Cell(50,5,utf8_decode(strftime("%a %d %b %Y%n %H:%M", $event['date'])),1,1,'C',1);
			$pdf->MultiCell(0,5,utf8_decode($event['description']),1);
			$pdf->Ln();
			}
			
		
		$pdf->Output("Ticket_N".$this->record['id'].".pdf",'I');
		}

		
}

?>
