<?php
define('LOGO', '../img/cartehniklogo_must.svg');
require_once('../../TCPDF/tcpdf.php');

// Get the RegNr from the GET parameter
if(isset($_GET['RegNr'])) {
    $RegNr = $_GET['RegNr'];

    // Fetch data from the database based on RegNr
    include_once '../db/laoseis.php';
    $query = "SELECT UPPER(RegNr) as RegNr, DATE_FORMAT(Kuupaev, '%d.%m.%Y %H:%i') AS Kuupaev, Odomeeter, Tehtud_tood FROM Tehtud_tood WHERE UPPER(RegNr) = UPPER('$RegNr')";
    $result = mysqli_query($conn, $query);

    if(mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        // Create PDF using TCPDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetTitle('Tehtud Töö PDF: '. $RegNr);
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        // $pdf->SetHeaderData(LOGO);

        // Add a page
        $pdf->AddPage();

        // Set font for Title
        $pdf->SetFont('dejavusans', 'B', 16);
        // Header: Tehtud Tööd - RegNr
        $pdf->Cell(0, 10, 'Tehtud Tööd - ' . $row['RegNr'], 0, 0, 'L');
        // Set font for Kuupäev
        $pdf->SetFont('dejavusans', '', 12);
        $pdf->Cell(0, 10,''. $row['Kuupaev'], 0, 1, 'R');
        $pdf->Ln(10);

        // Set font for body
        $pdf->SetFont('dejavusans', '', 12);
        // Body: Reg Nr and Odomeeter
        $pdf->Cell(0, 10, 'RegNr: ' . $row['RegNr'], 0, 0, 'L');
        $pdf->Cell(0, 10, 'Odomeeter: ' . $row['Odomeeter'] . ' km', 0, 1, 'R');
        $pdf->Ln(2);


$html = '
            <table border="1" cellpadding="10">
                <thead>
                    <tr>
                        <th><b>Tehtud Töö</b></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>'.$row['Tehtud_tood'].'</td>
                    </tr>
                </tbody>
            </table>';

// output the HTML content
$pdf->writeHTML($html, true, false, true, false, '');

        // Output PDF
        $pdf->Output('Tehtud_too_'.$RegNr.'.pdf', 'I');
    } else {
        echo 'Error: Andmeid ei leitud.';
    }
} else {
    echo 'Error: RegNr puudub.';
}
?>
