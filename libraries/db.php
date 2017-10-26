<?php

class generic_db extends db_mysql {
	var $last_tid = null;
	var $sql = null;
	var $available_directions=array("DESC","ASC");
	var $limit=0;	
	var $orderby="id";
	var $direction="DESC";
	var $pos=0;
	
}


class db_mysql {

	var $host = null; 
	var $database = null; 
	var $user = null; 
	var $pass = null;
	var $table = null;
	var $link  = null; 
	var $result = null;
	var $record = null;
	var $row = null;
	var $numrows = null;
	//var $table;
	var $results = array();
		
	function db_mysql () {
		$this->row = 0;
		$this->errno = mysql_errno();
		$this->error = mysql_error();
		}
	
	function halt ($msg) {
		printf("</td></tr></table><b>database error:</b> %s<br>\n", $this->sql);
		printf("<b>MySQL error</b>: $msg<br>\n",
		$this->errno,
		$this->error);
		die("Session halted.");
	}

	function connect () {
		global $Settings;
		$this->host = $Settings->dbhost;
		$this->database = $Settings->dbname;
		$this->user = $Settings->dbuser;
		$this->pass = $Settings->dbpasswd;
		
		if ( 0 == $this->link ) {
			$this->link=mysql_connect($this->host, $this->user, $this->pass);
			if (!$this->link)
				$this->halt("link == false, connect failed");
			if (!mysql_query(sprintf("use %s",$this->database),$this->link))
        		$this->halt("cannot use database ".$this->database);
		}
		return;
	}

	function disconnect () {
		if ( !0 == $this->link ) {
			mysql_close($this->link);
			$this->link = 0;
			}
		return;
	}

	function query () {
		foreach ($this->results as $key => $value)
    	unset($this->results[$key]);

	 	$this->connect();
    	$result = mysql_query($this->sql, $this->link);
		if (!$result) {
			$this->halt(mysql_error());
			exit -1;
			}
		
		$this->numrows = mysql_num_rows($result);
		//if($this->numrows > 0 && $this->numrows > count($this->results))  {
			/* if(0 < count($this->results)) {
				for($i=0;$i<$this->numrows;$i++) {
					$this->record = mysql_fetch_array($this->result, MYSQL_ASSOC);
					$this->results[$i] += $this->record;
					}
					return 0;
			} else { */
				for($i=0;$i<$this->numrows;$i++) {
					$this->record = mysql_fetch_array($result, MYSQL_ASSOC);
					$this->results[$i] = $this->record;
					}
					return 0;
				//} else return -1;
	}
	
	function countrows () {
		 	$this->connect();
    	$result = mysql_query($this->sql, $this->link);
		if (!$result) {
			$this->halt(mysql_error());
			exit -1;
			}
		
		$this->numrows = mysql_num_rows($result);
		return 0;
		}
	
	function commit ($datas) {
		if (!is_array($datas))
			return -1;
		$columns="(";
		$values="(";
		foreach($datas as $key=>$val) {
			#$val=str_replace("'","\'",$val);
			if ($key != "submit" && $key != "commit") {
				$columns .= "$key,";
				$values .= "'$val',";
				}
			}
		 $columns=substr("$columns", 0, -1);
		 $values=substr("$values", 0, -1);
		 
		 $columns.=")";
		 $values .= ")";
		$this->sql = "INSERT INTO $this->table $columns VALUES $values;";	 
		$this->connect();
    	$this->result = mysql_query($this->sql, $this->link);
		if (!$this->result) {
			echo $this->error;
			echo mysql_error();
			exit -1;
			}
		$this->last_tid = mysql_insert_id();
		return 0;
		}

	function updaterow () {
	
	 	$this->connect();
    		$this->result = mysql_query($this->sql, $this->link);
		if (!$this->result) {
			$this->halt(mysql_error());
			exit -1;
			}
		return 0;
	}
	
	function next_record () {
		mysql_data_seek($this->result,$this->row);
		$this->record = mysql_fetch_array($this->result);
		if (!is_array($this->record))
			return -1;
		
	}

	function seek ($pos) {
		$status = mysql_data_seek($this->result, $pos);
		if ($status)
			$this->row = $pos;
		return;
	}
}


	
	
