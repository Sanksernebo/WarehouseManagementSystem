<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}
include_once '../db/laoseis.php';
$result = mysqli_query($conn, "SELECT too_id, UPPER(RegNr) as RegNr, DATE_FORMAT(Kuupaev, '%d.%m.%Y %H:%i') AS FormattedDate, Odomeeter, Tehtud_tood FROM Tehtud_tood ORDER BY Kuupaev DESC LIMIT 51");
$rows = [];
while ($row = mysqli_fetch_array($result)) {
    $rows[] = $row;
}
$searchbar_initial_has_more = count($rows) > 50;
if ($searchbar_initial_has_more) array_pop($rows);
?>
<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0">
    <meta charset="utf-8">
    <link rel="stylesheet" href="../../style.css">
    <script src="https://kit.fontawesome.com/4d1395116e.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="icon" type="image/x-icon" href="../img/cartehniklogo_svg.svg">
    <title>Tehtud Tööd</title>
</head>

<body>
<?php require_once '../includes/nav.php'; ?>

    <h1>Tehtud Tööd</h1>
    <a href="lisa_too.php" class="lisa-link">Lisa Töö</a>
    <?php
    $searchbar_endpoint    = 'search.php';
    $searchbar_placeholder = 'Otsi';
    $searchbar_js_path     = '../includes/searchbar.js';
    require_once '../includes/searchbar_init.php';
    ?>
    <table id="myTable">
        <thead>
            <tr>
                <td>Auto Reg.Nr</td>
                <td>Kuupäev</td>
                <td>Odomeeter</td>
                <td width="50%">Tehtud Tööd</td>
                <td>Tegevus</td>
            </tr>
        </thead>
        <tbody id="tableBody">
            <?php
            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    include '_row.php';
                }
            } else {
                echo "<tr class='empty-state'><td colspan='5'><p style='font-weight:bold'>Tulemusi ei leitud</p></td></tr>";
            }
            ?>
        </tbody>
    </table>

<?php require_once '../includes/footer.php'; ?>
</body>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var currentUrl = window.location.href;
        document.querySelectorAll('.nav-links a').forEach(function (link) {
            if (link.href === currentUrl) {
                link.classList.add('active');
            }
        });
    });
</script>

</html>
