<?php

class File {

	var $file=null;
	var $dir=null;
	
function view () {
	global $Settings;
	$dir=$Settings->repository."/".$this->dir;
		$filelist=array();
		if (!is_dir($dir)) {
			error("The directory $this->dir doesn't exists");
			}
		$handle=opendir($dir);

		while (false !== ($file = readdir($handle))) {
			if ($file != '.' && $file != '..') {
				array_push($filelist, array(
					"link" => "index.php?whattodo=showfiles&dir=$this->dir&file=$file",
					"name" => $file
					));
				}
			}
			closedir($handle);
			return $filelist;
		}

function show () {
	global $Settings;
	$dir=$Settings->repository."/".$this->dir;
	$manager = new TemplateManager();
	$template =& $manager->prepare("themes/".$Settings->theme."/frame.html");
	$tproc = new TemplateProcessor();
	$tproc->set("title", $Settings->title." - ".$this->file);
	$tproc->set("theme_url", $Settings->base_url."/themes/".$Settings->theme);
	$tproc->set("encoding", $Settings->encoding);
	$tproc->set("file", $this->file);
	$tproc->set("link", $dir."/".$this->file);
	echo $tproc->process($template);
	}
			
function upload ($datas) {
	global $Settings;
	$dir=$Settings->repository."/".$this->dir;
	$destination=null;

if ($datas['userfile']['tmp_name']=="none"){
	 error( "Problem: no file to upload");
	 }

if (!is_dir($dir)) {
	error("The directory $this->dir doesn't exists");
	}

move_uploaded_file($datas['userfile']['tmp_name'], $dir."/".$this->file)	
	or error("The file $this->file can't be copied on the server");
	}		
	
function makedir () {
	global $Settings;
	$dir=$Settings->repository."/".$this->dir;
		if (is_dir($dir)) {
			error("The directory $dir already exist");
			return;
			}
		mkdir($dir, 0770) or error("unable to make dir $this->dir");
  	chmod($dir, 0770) or error("unable to chmod $this->dir");
		}
}
?>
