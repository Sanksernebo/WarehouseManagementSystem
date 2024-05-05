<?php
session_start();
// Check if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page
    header("Location: ../login/login.php");
    exit;
}
require_once ('../TCPDF/tcpdf.php');

// Extend the TCPDF class to create custom Header and Footer
class MYPDF extends TCPDF
{
    //Page header
    public function Header()
    {
        // set header data
        $img_file = '../../src/img/cartehniklogo_must.png';
        $txt = 'Rõngu Auto OÜ';
        $txt2 = 'Aia 4-24 Rõngu, Elva vald, Tartumaa';
        // display header image
        $this->Image($img_file, 12, -10, 50, '', 'PNG', '', 'T', false, 300, '', false, false, 0);
        // Set font and size for the text
        $this->SetFont('Lato-Regular', '', 10);

        // set width of the text
        $text_width = 180;

        // position the text block to the right side
        $this->SetY($this->GetY() + 20); // Adjust vertical position as needed
        $this->MultiCell($text_width, 5, $txt, 0, 'R', false);
        $this->MultiCell($text_width, 5, $txt2, 0, 'R', false);

        // add line to indicate end of header
        $this->Line(10, 22, 200, 22);
    }
    // Page footer
    public function Footer()
    {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        $this->Line(10, 282, 200, 282);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
        $this->Cell(0, 10, $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// Get the RegNr from the GET parameter
if (isset($_GET['RegNr'])) {
    $RegNr = $_GET['RegNr'];

    // Fetch data from the database based on RegNr
    include_once '../db/laoseis.php';
    $query = "SELECT UPPER(RegNr) as RegNr, DATE_FORMAT(Kuupaev, '%d.%m.%Y %H:%i') AS Kuupaev, Odomeeter, Tehtud_tood FROM Tehtud_tood WHERE UPPER(RegNr) = UPPER('$RegNr')";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        // Create PDF using TCPDF
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetTitle('Tehtud Töö PDF: ' . $RegNr);
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // Add a page
        $pdf->AddPage();

        // Set font for Title
        $pdf->SetFont('Lato-Regular', 'B', 20);
        // Header - Title: Tehtud Tööd - RegNr
        $pdf->Cell(0, 10, 'Tehtud Tööd - ' . $row['RegNr'], 0, 0, 'L');

        // Set font for Kuupäev
        $pdf->SetFont('Lato-Regular', '', 12);
        $pdf->Cell(0, 10, '' . $row['Kuupaev'], 0, 1, 'R');
        $pdf->Ln(10);

        // Set font for body
        $pdf->SetFont('Lato-Regular', '', 12);
        // Body: Reg Nr and Odomeeter
        $pdf->Cell(0, 10, 'RegNr: ' . $row['RegNr'], 0, 0, 'L');
        $pdf->Cell(0, 10, 'Odomeeter: ' . $row['Odomeeter'] . ' km', 0, 1, 'R');
        $pdf->Ln(2);


        $html = '
            <table border="1" cellpadding="10">
                <thead>
                    <tr>
                        <th style="font-size:16px;"><b>Tehtud Töö</b></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="font-size:12px">' . $row['Tehtud_tood'] . '</td>
                    </tr>
                </tbody>
            </table>';

        // output the HTML content
        $pdf->writeHTML($html, true, false, true, false, '');

        // Output PDF: I = internal, D = download
        $pdf->Output('Tehtud_too_' . $RegNr . '.pdf', 'I');
    } else {
        echo 'Error: Andmeid ei leitud.';
    }
} else {
    echo 'Error: RegNr puudub.';
}
?>