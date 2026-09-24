<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: src/login/login.php");
    exit;
}
include_once 'src/db/laoseis.php';
$result = mysqli_query($conn, "SELECT Tootekood, Nimetus, Kogus, Sisseost, Jaehind, Ost, Olek, toote_id FROM Ladu ORDER BY toote_id DESC LIMIT 51");
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <link rel="stylesheet" href="style.css">
    <script src="https://kit.fontawesome.com/4d1395116e.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="icon" type="image/x-icon" href="src/img/cartehniklogo_svg.svg">
    <title>Laoseis</title>
</head>

<body>
<?php require_once 'src/includes/nav_root.php'; ?>

    <h1>Laoseis</h1>
    <a href="src/lisa_lattu/lisa_lattu.php" class="lisa-link">Lisa Laoseisu</a>
    <?php
    $searchbar_endpoint    = 'src/avaleht_nupud/search.php';
    $searchbar_placeholder = 'Otsi';
    $searchbar_js_path     = 'src/includes/searchbar.js';
    require_once 'src/includes/searchbar_init.php';
    ?>
    <table id="myTable">
        <thead>
            <tr>
                <td>Tootekood</td>
                <td>Nimetus</td>
                <td>Kogus</td>
                <td>Sisseostu Hind</td>
                <td>Jaehind</td>
                <td>Ostetud</td>
                <td>Olek</td>
                <td>Tegevus</td>
            </tr>
        </thead>
        <tbody id="tableBody">
            <?php
            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    include 'src/avaleht_nupud/_row.php';
                }
            } else {
                echo "<tr class='empty-state'><td colspan='8'><p style='font-weight:bold'>Tulemusi ei leitud</p></td></tr>";
            }
            ?>
        </tbody>
    </table>

<?php require_once 'src/includes/footer.php'; ?>
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
