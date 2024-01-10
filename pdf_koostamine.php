<?php
session_cache_limiter('nocache');
session_start();
include_once 'laoseis.php';

// PDFi koostamine.

// Kui kasutaja täidab vormi ja vajutab "Koosta ...", siis saabuvad väärtused siia nii, et nad on POST parameetrid.
// Korjan nad sealt POST seest välja ja teen tavalisteks muutujateks.
$RegNr = trim(substr(strip_tags($_POST['RegNr']), 0, 30));
$Kuupaev = trim(substr(strip_tags($_POST['Kuupaev']), 0, 14));
$Odomeeter = trim(substr(strip_tags($_POST['Odomeeter']), 0, 180));
$Tehtud_tood = trim(substr(strip_tags($_POST['Tehtud_tood']), 0, 30));


// Kui andmed oli sisestamata, siis koostame ikkagi PDF-i. Kui tahab, täidab käsitsi.

if ($RegNr == '') {
    $RegNr = '________________________________';
}

if ($Kuupaev == '') {
    $Kuupaev = '________________________';
}

if ($Odomeeter == '') {
    $Odomeeter = '________________________________________________';
}

if ($Tehtud_tood == '') {
    $Tehtud_tood= '________________________________';
}

require('tcpdf/tcpdf.php');

// Extend the TCPDF class to create custom Header and Footer
class MYPDF extends TCPDF
{
    //Page header
    public function Header()
    {
        // get the current page break margin
        $bMargin = $this->getBreakMargin();
        // get current auto-page-break mode
        $auto_page_break = $this->AutoPageBreak;
        // disable auto-page-break
        $this->SetAutoPageBreak(false, 0);
        // set bacground image
        $img_file = '';
        $this->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
        // restore auto-page-break status
        $this->SetAutoPageBreak($auto_page_break, $bMargin);
        // set the starting point for the page content
        $this->setPageMark();
    }
}

$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
// set header and footer fonts
$pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(0);
// remove default footer
$pdf->setPrintFooter(false);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// add a page
$pdf->AddPage();

// get the current page break margin
$bMargin = $pdf->getBreakMargin();
// get current auto-page-break mode
$auto_page_break = $pdf->getAutoPageBreak();
// disable auto-page-break
$pdf->SetAutoPageBreak(false, 0);

$html = <<<EOT
<p><span style="text-align:center; font-size:14.0pt"><b>Tehtud Tööd</b></span></p>
<p>Tööde teostamise kuupäev: <b>$Kuupaev</b></p>
<p><b>MÜÜJA:</b></p>
<table border=".5" cellpadding="6">
<tr>
<td width="50%">
<p><b>$RegNr</b><br>
<i>(müüja füüsilise või juriidilise isiku nimi)</i></p>
</td>
<td>
<p><b>$Odomeeter</b><br>
<i>(isikukood)</i></p>
</td>
</tr>
<tr>
<td width="100%">
<p><b>$Tehtud_tood</b><br>
<i>(aadress)</i></p>
</td>
</tr>
</table>
<p><b>ja</b></p>
<p><b>OSTJA:</b></p>
<table border=".5" cellpadding="6">
<tr>
<td width="50%">
<p><b></b><br>
<i>(müüja füüsilise või juriidilise isiku nimi)</i></p>
</td>
<td>
<p><b></b><br>
<i>(isikukood)</i></p>
</td>
</tr>
<tr>
<td width="100%">
<p><b></b><br>
<i>(aadress)</i></p>
</td>
</tr>
</table>
<p>sõlmisid käesoleva müügilepingu alljärgnevas:</p>
<p>1. <b>MÜÜJA </b>kohustub <b>OSTJALE </b>üle andma sõiduki</p>
<table border=".5" cellpadding="6">
<tr>
<td width="50%">
<p><b></b><br>
<i>(mark)</i></p>
</td>
<td>
<p><b></b><br>
<i>(mudel)</i></p>
</td>
</tr>
<tr>
<td width="50%">
<p><b></b><br>
<i>(VIN-kood)</i></p>
</td>
<td>
<p><b></b><br>
<i>(registreerimisnumber)</i></p>
</td>
</tr>
</table>

<p>Väljastas:</p>
<p><b>Vahur Sangernebo</b>: __________________________&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

EOT;

$pdf->SetAutoPageBreak($auto_page_break, $bMargin);
$pdf->setPageMark();
$pdf->SetFont('times', '', 10);
$pdf->writeHTML($html, true, false, true, false, '');

$pdf->Output('leping_auto.pdf', 'I');

?>