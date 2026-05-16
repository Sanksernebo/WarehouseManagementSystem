<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: src/login/login.php");
    exit;
}
include_once 'src/db/laoseis.php';
$result = mysqli_query($conn, "SELECT * FROM Ladu ORDER BY toote_id DESC");
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
    <input type="text" id="searchBar" onkeyup="search()" placeholder="Sisesta Tootekood">
    <table id=myTable>
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
        <tbody>
            <?php
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_array($result)) {
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row["Tootekood"]); ?></td>
                        <td><?php echo htmlspecialchars($row["Nimetus"]); ?></td>
                        <td><?php echo htmlspecialchars($row["Kogus"]); ?></td>
                        <td><?php echo htmlspecialchars($row["Sisseost"]); ?></td>
                        <td><?php echo htmlspecialchars($row["Jaehind"]); ?></td>
                        <td><?php echo htmlspecialchars($row["Ost"]); ?></td>
                        <td><?php echo htmlspecialchars($row["Olek"]); ?></td>
                        <td>
                            <a href="src/avaleht_nupud/update-process.php?ID=<?php echo $row["toote_id"]; ?>">
                                <i class="fa-solid fa-pen-to-square fa-lg muuda-icon"></i>
                            </a>
                            <a href="src/avaleht_nupud/delete-process.php?ID=<?php echo $row["toote_id"]; ?>">
                                <i class="fa-solid fa-trash fa-lg kustuta-icon"></i>
                            </a>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                echo "<tr><td colspan='8'><p style='font-weight:bold'>Tulemusi ei leitud</p></td></tr>";
            }
            ?>
        </tbody>
    </table>

<?php require_once 'src/includes/footer.php'; ?>
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
            if (link.href === currentUrl) {
                link.classList.add('active');
            }
        });
    });
</script>

</html>
