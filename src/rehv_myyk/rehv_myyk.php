<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}
include_once '../db/laoseis.php';
$result = mysqli_query($conn, "SELECT UPPER(RegNr) as RegNr, DATE_FORMAT(Kuupaev, '%d.%m.%Y') AS FormattedDate, Kogus, UPPER(Moot) AS Moot, Tootja, Hooaeg, Tarnija FROM Rehvi_myyk ORDER BY Kuupaev DESC");
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
    <input type="text" id="searchBar" onkeyup="search()" placeholder="Otsi Reg.Nr">
    <table id=myTable>
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
        <tbody>
            <?php while ($row = mysqli_fetch_array($result)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row["RegNr"]); ?></td>
                    <td><?php echo htmlspecialchars($row["Moot"]); ?></td>
                    <td><?php echo htmlspecialchars($row["Tootja"]); ?></td>
                    <td><?php echo htmlspecialchars($row["Kogus"]); ?> tk</td>
                    <td><?php echo htmlspecialchars($row["Hooaeg"]); ?></td>
                    <td><?php echo htmlspecialchars($row["Tarnija"]); ?></td>
                    <td><?php echo htmlspecialchars($row["FormattedDate"]); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

<?php require_once '../includes/footer.php'; ?>
</body>
<script>
    function search() {
        var input, filter, table, tr, td, i, txtValue;
        input = document.getElementById("searchBar");
        filter = input.value.toUpperCase();
        table = document.getElementById("myTable");
        tr = table.getElementsByTagName("tr");

        for (i = 0; i < tr.length; i++) {
            td = tr[i].getElementsByTagName("td")[0];
            if (td) {
                txtValue = td.textContent || td.innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }

        var headerRow = table.querySelector("thead tr");
        if (headerRow) {
            headerRow.style.display = "";
        }
    }
</script>
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
