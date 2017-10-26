<?php

function mhd2_callback($classname) {
		include_once("include/$classname.php");
}

function goto ($url) {
	if (!headers_sent()) {
		header("Location: $url");
	} else {
		echo "<meta http-equiv=\"refresh\" content=\"0;url=$url\">\r\n";
	}
exit;
}
	
function error ($message) {
	$_SESSION['stderr'] = _($message);
	//echo $_SESSION['stderr'];
	//exit -1; 
}   

function now() {
       return date("YmdHis");
   }

function array_replace ($a , $tofind , $toreplace){
 $i = array_search($tofind, $a);
  if ($i === false)
  {
  return $a;
  }
  else
  {
  $a[$i] = $toreplace;
  return array_replace($a, $tofind, $toreplace);
  }
 
}

function print_r_html ($r) {
   foreach ($r as $key => $val) {
       if (is_array($val)) {
           echo "[$key] = An Array:<BLOCKQUOTE>";
           print_r_html($val);
           echo "</BLOCKQUOTE></P>";
       } else {
           echo "[$key] = '$val'<BR>";
       }
   }
}

function array_clean ($array, $todelete = false) {
   foreach($array as $key => $value) {
       if(is_array($value))
       		$array[$key] = array_clean($array[$key], $todelete, $caseSensitive);
       else {
           if($todelete) {
               if(stristr($key, $todelete) !== false)
                       unset($array[$key]);
               }
           }
   }
   return $array;
}

function removeaccents ($string) { 
   $string = strtr($string,  "ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ",
  "aaaaaaaaaaaaooooooooooooeeeeeeeecciiiiiiiiuuuuuuuuynn"); 
   return $string; 
   }

function removequote ($string) { 
   $string = ereg_replace("'","\'",$string); 
   return $string; 
   }
	 
function get_mail_parts ($stream, $msg_number, $mime_type, $structure = false, $part_number = false)
{
if(!$structure)
 {
 $structure = imap_fetchstructure($stream, $msg_number);
 }
if($structure)
 {
 if($mime_type == get_mime_type($structure))
  {
  if(!$part_number)
   {
   $part_number = "1";
   }
  $text = imap_fetchbody($stream, $msg_number, $part_number);
  if($structure->encoding == 3)
   {
   return imap_base64($text);
   }
  else if($structure->encoding == 4)
   {
   return imap_qprint($text);
   }
  else
   {
   return $text;
   }
  }
 if($structure->type == 1) /* multipart */
  {
  while(list($index, $sub_structure) = each($structure->parts))
   {
   if($part_number)
    {
    $prefix = $part_number . '.';
    }
   $data = get_mail_parts($stream, $msg_number, $mime_type, $sub_structure,
$prefix . ($index + 1));
   if($data)
    {
    return $data;
    }
   }
  }
 }
return false;
}

function get_mime_type (&$structure) {
	$primary_mime_type = array("TEXT", "MULTIPART",
	"MESSAGE", "APPLICATION", "AUDIO",
	"IMAGE", "VIDEO", "OTHER");
	if($structure->subtype)
	 {
	 return $primary_mime_type[(int) $structure->type] . '/' .
	$structure->subtype;
 	}
	return "TEXT/PLAIN";
	}
	
function convert_date ($date) {
	if (ereg ("([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})", $date, $regs))
		return $regs[1].$regs[2].$regs[3]."0000000";
	elseif (ereg ("([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})", $date, $regs))
		return $regs[3].$regs[2].$regs[1]."0000000";
	elseif (ereg ("([0-9]{1,2})/([0-9]{1,2})/([0-9]{4})", $date, $regs))
		return $regs[3].$regs[2].$regs[1]."0000000";
	}
	
function range_step ($low,$high,$step=1) {
$ranArray = range($low,$high);
$step--;
$keys = count($ranArray);
   for($i=0;$i<$keys;$i++)
   {
   $retArray[] = $ranArray[$i];
   $i = $i + $step;
   }
return $retArray;
}

function duration ($timestamp) {
    
    $months=floor($timestamp / (60*60*24*31));
    $timestamp%=60*60*24*31;
    
    $weeks=floor($timestamp / (60*60*24*7));
    $timestamp%=60*60*24*7;
    
    $days=floor($timestamp / (60*60*24));
    $timestamp%=60*60*24;
    
    $hrs=floor($timestamp / (60*60));
    $timestamp%=60*60;
    
    $mins=floor($timestamp / 60);
    $secs=$timestamp % 60;
   
   $str="";

   if ($months >= 1) { $str.=" {$months} "._("months"); }
   if ($weeks >= 1) { $str.=" {$weeks} "._("weeks"); }
   if ($days >= 1) { $str.=" {$days} "._("days"); return $str;}
   if ($hrs >= 1) { $str.=" {$hrs} "._("hours"); return $str;}
   if ($mins >= 1) { $str.=" {$mins} "._("min."); return $str;}
   //if ($secs >= 1) { $str.="{$secs} "._("seconds"); }
   
   return $str;
}
?>
