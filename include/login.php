<?php

class Login {
	var $id=null;
	var $passwd=null;
	var $taskbar="no";
	
	function Login() {
		$this->table = "users";
	}
	
	function identify($id,$passwd) {
		global $Settings;
		$user = new User();
		$this->id =  HTMLSpecialChars($id);
		$this->passwd =  md5(HTMLSpecialChars($passwd));
		$user->validate($this->id);
		if(strlen($this->id) == 0 || strlen($this->passwd) == 0) {
			error("You must supply a login/password");
		} elseif ($user->record['passwd'] != $this->passwd) {
			error("Login/password incorrect");
		} else {
			$_SESSION['id'] = $user->record['id'];
			$_SESSION['firstname'] = $user->record['firstname'];
			$_SESSION['lastname'] = $user->record['lastname'];
			$_SESSION['valid'] = "Y";
			$_SESSION['rights'] = $user->getrights($this->id);
		}
			goto($Settings->base_url);
	}
}
	function loginform() {
		global $Settings;
		$manager = new TemplateManager();
		$template =& $manager->prepare("themes/".$Settings->theme."/login.html");
		$tproc = new TemplateProcessor();
		$tproc->set("title", $Settings->title." login");
		$tproc->set("theme_url", $Settings->base_url."/themes/".$Settings->theme);
		$tproc->set("encoding", $Settings->encoding);
		$locales=array(
            			array("value" => "fr_FR", "text" => "Francais"),
            			array("value" => "en_US", "text" => "English")
				);
		$tproc->set("locale",$locales);
		if(isset($_SESSION['stderr'])) {
			$tproc->set("stderr",$_SESSION['stderr']);
			unset($_SESSION['stderr']);
			}
		if($taskbar!="no") $tproc->set("taskbar", $taskbar);
		echo $tproc->process($template);
		}

?>
