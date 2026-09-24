<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}
include_once '../db/laoseis.php';
$result = mysqli_query($conn, "SELECT UPPER(RegNr) as RegNr, DATE_FORMAT(Kuupaev, '%d.%m.%Y') AS FormattedDate, Kogus, UPPER(Moot) AS Moot, Tootja, Hooaeg, Tarnija FROM Rehvi_myyk ORDER BY Kuupaev DESC LIMIT 51");
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
    <link rel="icon" type="image/x-icon" href="../img/cartehniklogo_svg.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <title>Müüdud Rehvid</title>
</head>

<body>
<?php require_once '../includes/nav.php'; ?>

    <h1>Müüdud Rehvid</h1>
    <a href="lisa_rehv_myyk.php" class="lisa-link">Lisa Müük</a>
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
                <td>Mõõt</td>
                <td>Tootja</td>
                <td>Kogus</td>
                <td>Hooaeg</td>
                <td>Tarnija</td>
                <td>Kuupäev</td>
            </tr>
        </thead>
        <tbody id="tableBody">
            <?php foreach ($rows as $row): ?>
                <?php include '_row.php'; ?>
            <?php endforeach; ?>
        </tbody>
    </table>

<?php require_once '../includes/footer.php'; ?>
</body>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var currentUrl = window.location.href;
        document.querySelectorAll('.nav-links a').forEach(function (link) {
            if (link.href === currentUrl && !link.closest('.dropdown-content')) {
                link.classList.add('active');
            } else if (link.closest('.dropdown-content')) {
                link.closest('.dropdown').classList.add('active');
            }
        });
    });
</script>

</html>
