<?php

class Item extends generic_db {

	var $close=null;
	var $table = "items";

	function Item () {
		global $Settings;
		$this->title = "[".$Settings->title."]";
	}
	
	function show ($id) {
		global $Settings;
		
		$this->sql = "SELECT * FROM $this->table WHERE id='$id';";
		$this->query();
		
		$this->results[0]['theme_url'] = $Settings->base_url."/themes/".$Settings->theme;
		$source['high_cost'] = str_replace('.',',',$source['high_cost']);
		$source['low_cost'] = str_replace('.',',',$source['low_cost']);
				
		$manager = new TemplateManager();
		$template =& $manager->prepare("themes/".$Settings->theme."/item_addedit.html");
		$tproc = new TemplateProcessor();
		$tproc->set("title", $this->title." Item ".$this->result['id']);
		$tproc->set("theme_url", $Settings->base_url."/themes/".$Settings->theme);
		$tproc->set("encoding", $Settings->encoding);
		$tproc->set("item", $this->results);
		if($this->close=="yes") $tproc->set("close", "yes");
		echo $tproc->process($template);
		
	}
		
	function getlist ($cid) {

		$this->sql = "SELECT items.type, items.id, items.designation FROM items 
									LEFT JOIN contracts ON items.contractid=contracts.id 
									LEFT JOIN customers ON contracts.cid=customers.id
									WHERE customers.id='$cid';";
		$this->query();
		}

	function create ($datas) {
	
		if(!is_array($datas)) error("update items : this is not an array !");
		
		$source=array_clean($datas, "submit");
		$source=array_clean($source, "commit");
		
		$source['high_cost'] = str_replace(',','.',$source['high_cost']);
		$source['low_cost'] = str_replace(',','.',$source['low_cost']);
		
		$this->commit($source);
	}
	
			
	function update ($datas) {
		if(!is_array($datas)) error("update items : this is not an array !");
		
		$source=array_clean($datas, "submit");
		$source=array_clean($source, "commit");
		$source['high_cost'] = str_replace(',','.',$source['high_cost']);
		$source['low_cost'] = str_replace(',','.',$source['low_cost']);
		
		$itemid=$source['id'];
		
		$this->sql ="SELECT * FROM $this->table WHERE id='$itemid';";
		$this->query();
		
		$diffs = array_diff_assoc($source,$this->results['0']);
		
		if(count($diffs) >0) {
			$update=new Contract();
			$update->sql="UPDATE $this->table SET ";
			foreach($diffs as $key=>$val)
				$update->sql.="$key='$val',";
			$update->sql=substr("$update->sql", 0, -1);
			$update->sql.=" WHERE id='$itemid';";
			$update->updaterow();
			}
	}
}
?>
