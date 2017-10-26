<?php
global $Settings;
$whattodo=$_GET['whattodo'];

if (isset($_GET['whattodo']) && $_SESSION['rights'][0][$whattodo] == "Y") {

		switch($whattodo) {
		
			case "addjob":
				$newticket = new Ticket;
		
				if(!isset($_SESSION['ticket'])) {$_SESSION['ticket'] = new Ticket;}
				$ticket=&$_SESSION['ticket'];
				$ticket->timeout=0;

				if (isset($_POST['submit']) && $_POST['submit']==_(add)) {
					$_POST += array("date_opened" => now());
					$newticket->commit($_POST);
					$file=new File;
					$file->dir="ticket/".$newticket->last_tid;
					$file->makedir();
					$newticket->show($newticket->last_tid);
				} elseif (isset($_POST['submit']) && $_POST['submit']==_(modify)) {
					$cid=$_POST['cid'];
					$tid=$_POST['tid'];
					$newticket->create($tid,$cid);
				} elseif (isset($_POST['submit']) && $_POST['submit']==_(apply)) {
					$newticket->update($_POST);
					$ticket->show($_POST['id']);
				} elseif (isset($_POST['submit']) && $_POST['submit']==_(close)) {
					$_POST += array("date_closed" => now());
					$newticket->changestatus("close",$_POST['tid']);
					$ticket->view();
				} elseif (isset($_POST['submit']) && $_POST['submit']==_(reopen)) {
					$_POST += array("date_closed" => "000000000");
					$newticket->changestatus("open",$_POST['tid']);
					$ticket->view();
				} elseif (isset($_GET['cid'])) {
				$newticket->create(0,$_GET['cid']);
				} else {
				$newticket->create(0,0);
				}
			break;
			
			case "addevent":
				$event = new Event;
				if(!isset($_SESSION['ticket'])) {$_SESSION['ticket'] = new Ticket;}
				$ticket=&$_SESSION['ticket'];
				
				if (isset($_POST['commit']) && $_POST['commit']=="event") {
					$event->commit($_POST);
					$ticket->show($_GET['tid']);
					}
			break;
				
  			case "viewjobs":
				if(!isset($_SESSION['ticket'])) {$_SESSION['ticket'] = new Ticket;}
				$ticket=&$_SESSION['ticket'];
				
				if (isset($_GET['tid'])) $ticket->show($_GET['tid']);	
				else $ticket->view();
			break;
			
  			case "printjob":
				if(!isset($_SESSION['ticket'])) {$_SESSION['ticket'] = new Ticket;}
				$ticket=&$_SESSION['ticket'];
				$ticket->print_ticket();
			break;
			
  			case "showfiles":
				$file=new File;
				$file->file=$_GET['file'];
				$file->dir=$_GET['dir'];
				$file->show();
			break;
			
  			case "addfile":
				$file=new File;
				$file->file=$_FILES['userfile']['name'];
				$file->dir=$_GET['dir'];
				$file->upload($_FILES);
				if(!ereg ("(.+)/([0-9]+)", $_GET['dir'], $regs) && !isset($_SESSION[$regs[1]])) break;
				else $_SESSION[$regs[1]]->show();
			break;
			
  			case "showmails":
				if(!isset($_SESSION['ticket'])) {$_SESSION['ticket'] = new Ticket;}
				$ticket=&$_SESSION['ticket'];
				$mail=new Mail;
				$mail->msgnum=$_GET['msgnum'];
				$mail->show($ticket->record['id']);
			break;
			
  			case "search":
				if(!isset($_SESSION['search'])) {$_SESSION['search'] = new Search;}
				$search=&$_SESSION['search'];
				
				if(isset($_POST['table']) && array_key_exists($_POST['table'],$search->tables))
					$search->table_scope=$_POST['table'];
				if(isset($_POST['column']))
					$search->column_scope=$_POST['column'];
				if(isset($_POST['string']) && $_POST['string']!="")
					$search->string=$_POST['string'];
				$search->view();
			break;
						
			case "addcontract":
				$newcontract = new Contract;
				if (isset($_POST['submit']) && $_POST['submit']==_(add)) {
					if(isset($_POST['start_date']) && $_POST['start_date'] != "")
						$_POST['start_date'] = convert_date($_POST['start_date']);
					$newcontract->commit($_POST);
					$file=new File;
					$file->dir="contract/".$newcontract->last_tid;
					$file->makedir();
					$newcontract->show($newcontract->last_tid);
				} elseif (isset($_POST['submit']) && $_POST['submit']==_(modify)) {
					if(isset($_POST['start_date']) && $_POST['start_date'] != "")
						$_POST['start_date'] = convert_date($_POST['start_date']);
					$cid=$_POST['cid'];
					$contractid=$_POST['contractid'];
					$newcontract->create($contractid,$cid);
				} elseif (isset($_POST['submit']) && $_POST['submit']==_(apply)) {
					if(isset($_POST['start_date']) && $_POST['start_date'] != "")
						$_POST['start_date'] = convert_date($_POST['start_date']);
					$newcontract->update($_POST);
					$newcontract->show($_POST['id']);
				} elseif (isset($_GET['cid'])) {
					$newcontract->create(0,$_GET['cid']);
				} else {
					$newcontract->create(0,0);
				}
			break;
			
			case "viewcontracts":
				if(!isset($_SESSION['contract'])) {$_SESSION['contract'] = new Contract;}
				$contract=&$_SESSION['contract'];
				if (isset ($_GET['pos'])) $contract->pos=$_GET['pos'];
				if (isset ($_GET['status'])) $contract->status=$_GET['status'];
				if (isset ($_GET['orderby'])) $contract->orderby=$_GET['orderby'];
				if (isset ($_GET['direction'])) $contract->direction=$_GET['direction'];
			
				if (isset($_GET['contractid'])) $contract->show($_GET['contractid']);
				else $contract->view();
			break;
			
			case "additem":
				if(!isset($_SESSION['contract'])) {$_SESSION['contract'] = new Contract;}
				$contract=&$_SESSION['contract'];
				if(!isset($_SESSION['item'])) {$_SESSION['item'] = new Item;}
				$item=&$_SESSION['item'];
				if(isset($_POST['renewal']) && $_POST['renewal'] != "")
					$_POST['renewal'] = convert_date($_POST['renewal']);
				if (isset($_POST['submit']) && $_POST['submit']==_(add)) {
					$item->create($_POST);
					$contract->show($_POST['contractid']);
				} elseif (isset($_GET['itemid'])) {
					$item->show($_GET['itemid']);
				} elseif (isset($_POST['submit']) && $_POST['submit']==_(apply)) {
					$item->close="yes";
					$item->update($_POST);
					$contract->show($_POST['contractid']);
				};
			break;
			
  			case "printcontract":
				if(!isset($_SESSION['contract'])) {$_SESSION['contract'] = new Contract;}
				$contract=&$_SESSION['contract'];
				$contract->print_contract();
			break;
			
  			case "exportcsv":
				if(!isset($_SESSION['contract'])) {$_SESSION['contract'] = new Contract;}
				$contract=&$_SESSION['contract'];
				$contract->export_csv();
			break;
			
			case "addcustomer":
				$newcustomer = new Customer;
				if (isset($_POST['submit']) && $_POST['submit']==_(add)) {
					$newcustomer->commit($_POST);
					$file=new File;
					$file->dir="customer/".$newcustomer->last_tid;
					$file->makedir();
					$newcustomer->show($newcustomer->last_tid);
				} elseif (isset($_POST['submit']) && $_POST['submit']==_(modify)) {
					$newcustomer->create($_POST['cid']);
				} elseif (isset($_POST['submit']) && $_POST['submit']==_(apply)) {
					$newcustomer->update($_POST);
					$newcustomer->show($_POST['id']);
				} else {
					$newcustomer->create(0,0);
				}
			break;
			
			case "viewcustomers":
				if(!isset($_SESSION['customer'])) {$_SESSION['customer'] = new Customer;}
				$customer=&$_SESSION['customer'];
				if (isset ($_GET['pos'])) $customer->pos=$_GET['pos'];
				if (isset ($_GET['type'])) $customer->type=$_GET['type'];
				if (isset ($_GET['orderby'])) $customer->orderby=$_GET['orderby'];
				if (isset ($_GET['direction'])) $customer->direction=$_GET['direction'];
				if (isset($_GET['cid'])) $customer->show($_GET['cid']);	
				else $customer->view();
			break;
			
			case "addcontact":
				if($Settings->contact_db=="ldap") $newcontact = new Contact_ldap;
				else $newcontact = new Contact;
				if (isset($_POST['submit']) && $_POST['submit']==_(add)) {
					if(isset($_GET['close'])) $newcontact->close=$_GET['close'];
					$_POST['uid']=strtolower(removeaccents(substr($_POST['firstname'],0,1).$_POST['lastname']));
					$newcontact->commit($_POST);
					$newcontact->show($newcontact->last_tid);
				} elseif (isset($_POST['submit']) && $_POST['submit']==_(modify)) {
					$newcontact->create($_POST['uid'],0);
				} elseif (isset($_POST['submit']) && $_POST['submit']==_(apply)) {
					$newcontact->update($_POST);
					$newcontact->show($_POST['uid']);
				} else {
					if(isset($_GET['taskbar']) && $_GET['taskbar']=="no") unset($newcontact->taskbar);
					if(isset($_GET['cid']) && $_GET['cid']!=0) $cid=$_GET['cid']; else $cid=0;
					$newcontact->create(0,$cid);
				}			
			break;
		
			case "viewcontacts":
				if(!isset($_SESSION['contact'])) {
					if($Settings->contact_db=="ldap") $_SESSION['contact'] = new Contact_ldap;
					else $_SESSION['contact'] = new Contact;
				}
				$contact=&$_SESSION['contact'];
				if (isset ($_GET['pos'])) $contact->pos=$_GET['pos'];
				if (isset ($_GET['type'])) $contact->type=$_GET['type'];
				if (isset ($_GET['orderby'])) $contact->orderby=$_GET['orderby'];
				if (isset ($_GET['direction'])) $contact->direction=$_GET['direction'];
				if (isset($_GET['uid'])) $contact->show($_GET['uid']);
				else $contact->view();
			break;
		
			case "booking":
			break;
		
			case "viewdocs":
				if(!isset($_SESSION['docs'])) $_SESSION['docs'] = new Documentation;
				$docs=&$_SESSION['docs'];
				$docs->view();
			break;
			
  			case "showdoc":
				$doc = new Documentation;
				$doc->file=$_GET['file'];
				$doc->show();
			break;
		
			case "stats":
				if(!isset($_SESSION['stats'])) $_SESSION['stats'] = new Stats;
				$stats=&$_SESSION['stats'];
				$stats->show(0);
			break;
		
			case "config":
				global $Settings;
				if (isset($_POST['submit']) && $_POST['submit']==_(apply))
					$Settings->update($_POST);
				$Settings->create();
			break;
		
			case "users":
				if(!isset($_SESSION['users'])) $_SESSION['users'] = new User;
				$users=&$_SESSION['users'];
				if (isset($_POST['submit']) && $_POST['submit']==_(add)) {
					$_POST['passwd']=md5($_POST['passwd']);
					$users->commit($_POST);
				} elseif (isset($_POST['submit']) && $_POST['submit']==_(apply))
					$users->update($_POST);
				$users->create();
			break;
		
			case "groups":
				if(!isset($_SESSION['groups'])) $_SESSION['groups'] = new Group;
				$groups=&$_SESSION['groups'];
				if (isset($_POST['submit']) && $_POST['submit']==_(add)) {
					$newgroup=array();
					$newgroup['name']=$_POST['group_name'];
					foreach($_POST['check_rights'] as $val) 
						$newgroup[$val] = 'Y';
					$groups->commit($newgroup);
				} elseif (isset($_POST['submit']) && $_POST['submit']==_(apply))
					$groups->update($_POST);
				$groups->create();
			break;
			
			case "logout":
				session_start();
				session_destroy();
				goto("index.php");
			break;
			
			default:
				goto("index.php");
			break;	
		}
	} else {
		global $Settings;
		$manager = new TemplateManager();
		$template =& $manager->prepare("themes/".$Settings->theme."/greetings.html");
		$tproc = new TemplateProcessor();
		$tproc->set("title", _("Welcome to ").$Settings->title);
		$tproc->set("theme_url", $Settings->base_url."/themes/".$Settings->theme);
		$tproc->set("encoding", $Settings->encoding);
		$tproc->set("taskbar", "yes");
		//$tproc->set("stderr", "access denied");
		$tproc->set("menu", $_SESSION['rights']);
		echo $tproc->process($template);
}
		
?>
