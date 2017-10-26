<?php
global $Settings;
require_once("item.php");

class Stats extends generic_db {
		var $months=array();
		var $available_columns=array("total_tickets","opened_tickets","hard_tickets","soft_tickets");
		var $taskbar="yes";
		
	function Stats () {	
		global $Settings;
		$this->title = "[".$Settings->title."]";
		for($i=1;$i<13;$i++)
			array_push($this->months,strftime("%B",mktime(0,0,0,$i)));
	}
	
	function view () {

	}
	
	function show ($cid) {
		global $Settings;	
		
		$columns=array();
		foreach($this->available_columns as $column) {
			$column_text=str_replace("_"," ",$column);
			array_push($columns,array(
				"url" => "",
				"name" => _($column_text)));
			}
	
		$stats_total = $this->get_total();
	
		$stats_contracts_by_date=$this->get_stats_contracts_by_date();
		

		$manager = new TemplateManager();
		$template =& $manager->prepare("themes/".$Settings->theme."/stats.html");
		$tproc = new TemplateProcessor();
		$tproc->set("title", $this->title." Statistiques");
		$tproc->set("theme_url", $Settings->base_url."/themes/".$Settings->theme);
		$tproc->set("encoding", $Settings->encoding);
		$tproc->set("menu", $_SESSION['rights']);
		$tproc->set("columns", $columns);
		$tproc->set("stats_total", $stats_total);
		$tproc->set("stats_by_date", $stats_by_date);
		$tproc->set("stats_contracts_by_date", $stats_contracts_by_date);
		if($this->taskbar!="no") $tproc->set("taskbar", $this->taskbar);
		echo $tproc->process($template);
	}
	
	function get_total () {
	
		$stats_total=array();
		
		$this->sql = "SELECT COUNT(*) AS TOTAL_TICKETS FROM tickets WHERE 1;";	
		$this->query();
		$total_tickets = $this->record['TOTAL_TICKETS'];
		
		$this->sql = "SELECT COUNT(*) AS OPENED_TICKETS FROM tickets WHERE status = 'open';";	
		$this->query();
		$opened_tickets = $this->record['OPENED_TICKETS'];
		
		$this->sql = "SELECT COUNT(*) AS COUNT_HARD FROM tickets WHERE category = 'hardware';";	
		$this->query();
		$hard_tickets = $this->record['COUNT_HARD'];
		
		$this->sql = "SELECT COUNT(*) AS COUNT_SOFT FROM tickets WHERE category = 'software';";	
		$this->query();
		$soft_tickets = $this->record['COUNT_SOFT'];
		
/*		$this->sql = "SELECT YEAR(MIN(date_opened)) AS YEAR_START_STATS FROM tickets WHERE 1;";	
		$this->query();
		$year_start_stats=$this->record['YEAR_START_STATS'];*/
		
		array_push($stats_total,array(
						"total_tickets" => $total_tickets,
						"opened_tickets" => $opened_tickets,
						"hard_tickets" => $hard_tickets,
						"soft_tickets" => $soft_tickets
						));
		return $stats_total;
	}
	
	function get_by_date ($year,$month) {
		
		$this->sql = "SELECT COUNT(*) AS TOTAL_TICKETS FROM tickets WHERE 1 AND YEAR(date_opened) = $year AND MONTH(date_opened) = $month;";	
		$this->query();
		$total_tickets = $this->record['TOTAL_TICKETS'];

		return $total_tickets;
	}
	
	function get_stats_contracts_by_date () {
		
		$items=new Item();
		$result=array();
		foreach(range(2002, strftime("%G")) as $year) {
		$items->sql = "SELECT SUM(low_cost) AS total_low_cost,SUM(high_cost) AS total_high_cost, ";
		$items->sql.= "COUNT(DISTINCT contractid) AS total_contracts ";
		$items->sql.= "FROM items WHERE YEAR(renewal) = $year";
		$items->query();
		array_push($result,array(
			'year' => $year,
			'total_contracts' => $items->record['total_contracts'],
			'total_low_cost' => money_format('%.2n',$items->record['total_low_cost']),
			'total_high_cost' => money_format('%.2n',$items->record['total_high_cost']),
			'total_margin' => money_format('%.2n',$items->record['total_high_cost']-$items->record['total_low_cost'])
			));
		}
		return $result;
	}

}
