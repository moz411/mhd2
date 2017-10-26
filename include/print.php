<?php

define('FPDF_FONTPATH','libraries/font/');
require_once("libraries/fpdi.php");
;

class Pdf_contract extends fpdi {
	var $title=null;
	var $titlew=60;
	var $titleh=10;
	

	function Header () {
		}
		
	function Footer () {
		global $Settings;
		if($this->PageNo() != 1) {
    	$this->SetY(-15);
    	$this->SetFont('Arial','I',8);
    	$this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
			}
		}
}


class Pdf_ticket extends fpdf {
	var $title=null;
	var $titlew=60;
	var $titleh=10;
	

	function Header () {
		}
		
	function Footer () {
    $this->SetY(-15);
    $this->SetFont('Arial','I',8);
		$this->Cell(0,10,$this->title,0,0,'L',0,0);
    $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
		}
}
