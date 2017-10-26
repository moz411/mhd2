<?php

class Documentation extends webdav_client{
	var $listing=array();
	var $view=null;
	var $file=null;
	var $taskbar="yes";
	
	function Documentation () {
		global $Settings;
		$this->set_server($Settings->webdav_server);
		$this->set_port(80);
		$this->set_user($Settings->webdav_user);
		$this->set_pass($Settings->webdav_pass);
		$this->set_protocol(1);
		$this->set_debug(false);
		}
		
	function view () {
		global $Settings;
			
		if (!$this->open()) {
  		error(_("Error: could not open server connection"));
  		exit;
			}

	// check if server supports webdav rfc 2518
		if (!$this->check_webdav()) {
  		error(_("Error: server does not support webdav or user/password may be wrong"));
  		exit;
			}
		
		$dir = $this->ls($Settings->doc_dir);
		if(count($dir) >= (count($this->listing))) {

		foreach($dir as $line) {
			$line['creationdate'] = date('d.m.Y H:i:s',$this->iso8601totime($line['creationdate']));
			//$line['name'] = explode("/",$line['href']);
			$line['name'] = end(split('[/]', $line['href']));
			if ($line['resourcetype'] != "collection"){
				array_push($this->listing,$line);
				}
			}
			
		$manager = new TemplateManager();
		$template =& $manager->prepare("themes/".$Settings->theme."/docs_view.html");
		$tproc = new TemplateProcessor();
		$tproc->set("title", $this->title." Docs");
		$tproc->set("theme_url", $Settings->base_url."/themes/".$Settings->theme);
		$tproc->set("encoding", $Settings->encoding);
		$tproc->set("menu", $_SESSION['rights']);
		$tproc->set("listing", $this->listing);
		$tproc->set("taskbar", "yes");
		$this ->view = $tproc->process($template);
		}
		echo $this->view;
		}
		
	function show () {
		global $Settings;
		$this->taskbar="no";
		$manager = new TemplateManager();
		$template =& $manager->prepare("themes/".$Settings->theme."/frame.html");
		$tproc = new TemplateProcessor();
		$tproc->set("title", $Settings->title." - ".$this->file);
		$tproc->set("theme_url", $Settings->base_url."/themes/".$Settings->theme);
		$tproc->set("encoding", $Settings->encoding);
		$tproc->set("menu", $_SESSION['rights']);
		$tproc->set("link", $this->file);
		if($this->taskbar!="no") $tproc->set("taskbar", $this->taskbar);
		echo $tproc->process($template);
		}
		
		function create () {
		}
		
		function update () {
		}
		
		function search () {
		}


}
