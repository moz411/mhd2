<?php

class db_ldap {

	var $record = null;
	var $numrows = null;
	var $results = array();
	var $request = null;
	var $tree=null;
	var $host=null;
	var $admin=null;
	var $password=null;
	var $available_directions=array("DESC","ASC");
	var $limit=0;	
	var $orderby="cn";
	var $direction="DESC";
	var $justthese = array("dn", "cn", "o", "telephonenumber","mail", "facsimiletelephonenumber", "postaladdress", "employeetype", "uid");
	
	function db_ldap () {
		
	}
	
	function query () {
		$ds=ldap_connect($this->host) or error("Cannot connect ldap server");
		ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);

		if ($ds) {
    	$r=ldap_bind($ds) or error("Cannot bind ldap tree");
    	$sr=ldap_search($ds,"$this->tree","$this->request", $this->justthese,"0", "$this->limit") or error("Cannot search in ldap tree");  
    	$result=ldap_get_entries($ds, $sr) or error("Cannot get ldap entries");
			ldap_close($ds);
		} else error("Unable to connect to LDAP server");
		
		$this->numrows=$result["count"];
		unset($this->results);
		for ($i=0; $i<$this->numrows; $i++) {
			$name=explode(" ", $result[$i]["cn"][0]);
			$firstname=$name[0];
			$lastname=$name[1];
			//for($i=0;$i<count($name);$i++)
			//	$lastname += $name[$i];
			$this->record = array(
			'dn' => $result[$i]["dn"],
			'firstname' => $firstname,
			'lastname' => $lastname,
			'customer' => $result[$i]["o"][0],
			'phone' => $result[$i]["telephonenumber"][0],
			'email' => $result[$i]["mail"][0],
			'fax' => $result[$i]["facsimiletelephonenumber"][0],
			'address' => $result[$i]["postaladdress"][0],
			'job' => $result[$i]["employeetype"][0],
			'uid' => $result[$i]["uid"][0]
			);
			$this->results[$i]=$this->record;
		}
		return;
	}	
	
	function commit ($datas) {
		global $Settings;
		if (!is_array($datas))
			return -1;
			
		$datas = $this->convert($datas);
		$dn="cn=".$datas['cn'].",".$Settings->ldap_tree;
			
		 
		$ds=ldap_connect($this->host) or error("Cannot connect ldap server");
		ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
		//$login="cn=".$this->admin.", ".$this->tree;

		if ($ds) {
			$r=ldap_bind($ds, $this->admin, $this->password) or error("Cannot bind ldap tree");
			$sr=ldap_add($ds, $dn, $datas) or error("Cannot modify the ldap tree");
			ldap_close($ds);
		} else error("Unable to connect to LDAP server");
		$this->last_tid = $datas['uid'];
		return 0;
		}
	
	function updaterow ($dn,$source) {
		$ds=ldap_connect($this->host) or error("Cannot connect ldap server");
		ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
		//$login="cn=".$this->admin.", ".$this->tree;
			
		$source = $this->convert($source);

		if ($ds) {
			$r=ldap_bind($ds, $this->admin, $this->password) or error("Cannot bind ldap tree");
    	$sr=ldap_modify($ds, $dn, $source) or error("Cannot modify the ldap tree");
			ldap_close($ds);
		} else error("Unable to connect to LDAP server");

	}
	
	function convert ($datas) {
				foreach($datas as $key=>$val) {
			switch($key) {
				case "cid":
					$customer = new Customer();
					$customer->sql ="SELECT name FROM customers ";
					$customer->sql.="WHERE id='".$datas['cid']."'; ";	
					$customer->query();
					$datas['o']=utf8_encode($customer->record['name']);
					unset($datas['cid']);
				break;
				case "uid":
					$datas['sn']=&$datas['uid'];
				break;
				case "firstname":
					if(isset($datas['firstname']) && $datas['firstname'] != "")
						$datas["cn"]=utf8_encode(trim($datas['firstname']." ".$datas['lastname']));
						unset($datas['firstname']);
						unset($datas['lastname']);
				break;
				case "phone":
					if(isset($datas['phone']) && $datas['phone'] != "")
					 $datas["telephonenumber"]=$datas['phone'];
					 unset($datas['phone']);
				break;
				case "email":
					if(isset($datas['email']) && $datas['email'] != "")
					 $datas["mail"]=utf8_encode($datas['email']);
					 unset($datas['email']);
				break;
				case "address":
					if(isset($datas['address']) && $datas['address'] != "")
					 $datas["postaladdress"]=utf8_encode($datas['address']);
					 unset($datas['address']);
				break;
				case "fax":
					if(isset($datas['fax']) && $datas['fax'] != "")
					 $datas["facsimiletelephonenumber"]=$datas['fax'];
					 unset($datas['fax']);
				break;
				case "job":
					if(isset($datas["job"]) && $datas['job'] != "")
					 $datas['employeetype']=utf8_encode($datas['job']);
					 unset($datas['job']);
				break;
				case "submit":
					 unset($datas['submit']);
				break;
				case "commit":
					unset($datas['commit']);
				break;
				}
			}
		$datas['objectClass']="inetorgperson";
		return $datas;
		}
}

?>
