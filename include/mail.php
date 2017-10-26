<?php

class Mail {
	var $msgnum = null;

function view ($tid) {
	global $Settings;
	$mbox = imap_open ("{".$Settings->mail_server.":143/notls}INBOX.".$tid, $Settings->mail_account,$Settings->mail_password, OP_READONLY)
	or die("can't connect: ".imap_last_error());
	
	$headers = imap_headers($mbox); 
	$msgnum = imap_num_msg($mbox);
	$lines = array();


  for ($i=1;$i<($msgnum+1);$i++) {
		$header = imap_headerinfo($mbox,$i);
		$elements=imap_mime_header_decode($header->subject);
		for($j=0;$j<count($elements);$j++) {
 	  	 	$subject = $elements[$j]->text;
			}
		array_push($lines, array(
			"from" => $header->fromaddress,
			"to" => $header->toaddress,
			"date" => $header->date,
			"subject" => $subject,
			"msgnum" => $i
			));
    }
	imap_close($mbox);
	return $lines;
	}

function show ($tid) {
	global $Settings;
	$mbox = imap_open ("{".$Settings->mail_server.":143/notls}INBOX.".$tid, $Settings->mail_account,$Settings->mail_password, OP_READONLY)
	or die("can't connect: ".imap_last_error());
	
	$header = imap_headerinfo($mbox,$this->msgnum);
	$data = get_mail_parts($mbox, $this->msgnum, "TEXT/PLAIN");
	imap_close($mbox);
	
	$manager = new TemplateManager();
	$template =& $manager->prepare("themes/".$Settings->theme."/frame.html");
	$tproc = new TemplateProcessor();
	$tproc->set("title", $Settings->title." - ".$header->subject);
	$tproc->set("theme_url", $Settings->base_url."/themes/".$Settings->theme);
	$tproc->set("encoding", $Settings->encoding);
	$tproc->set("data", utf8_encode($data));
	echo $tproc->process($template);
	}
}

?>
