<?php
define('FPDF_FONTPATH','font/');
require('fpdi.php');

$pdf= new fpdi();
$pagecount = $pdf->setSourceFile("pdfdoc.pdf");

$tplidx = $pdf->ImportPage(1);

$pdf->addPage();
$pdf->useTemplate($tplidx,10,10,90);

$pdf->Output("newpdf.pdf","I");
?>