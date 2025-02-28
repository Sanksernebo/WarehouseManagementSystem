<?php
session_start();
// Check if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page
    header("Location: ../login/login.php");
    exit;
}
include_once '../db/laoseis.php';
$result = mysqli_query($conn, "SELECT too_id, UPPER(RegNr) as RegNr, DATE_FORMAT(Kuupaev, '%d.%m.%Y %H:%i') AS FormattedDate, Odomeeter, Tehtud_tood FROM Tehtud_tood ORDER BY Kuupaev DESC");
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
    <title>Laoseis</title>
</head>

<body>
    <nav>
        <div class="logo">
            <a href="../../index.php">
                <img src="../../src/img/cartehniklogo_valge.svg" alt="Cartehnik logo">
            </a>
        </div>
        <div class="nav-links">
            <a href="../../index.php">Avaleht</a>
            <a href="../../src/myydud_tooted/myyk.php">Müüdud Tooted</a>
            <a href="../../src/tehtud_tood/tehtud_tood.php">Tehtud Tööd</a>
            <div class="dropdown">
                <button class="dropbtn">Rehvid
                    <i class="fa fa-caret-down"></i>
                </button>
                <div class="dropdown-content">
                    <a href="../../src/rehv_myyk/rehv_myyk.php">Müüdud Rehvid</a>
                    <a href="../../src/rehv_ladu/rehv_ladu.php">Rehvid Laos</a>
                </div>
            </div>
            <a href="../../src/kalender/kalender.php">Töögraafik</a>
            <a href="../login/logout.php">
                <?php if (isset($_SESSION['username'])): ?>
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?>,</span>
                <?php endif; ?>
                Logi välja
            </a>
        </div>
    </nav>
    <?php
    if (mysqli_num_rows($result) > 0) {
    } else {
        echo "<p style=font-weight:bold>Tulemusi ei leitud </p>";
        echo "
    <nav>
        <a href=index.php>Avaleht</a>
        <a href=src/myydud_tooted/myyk.php>Müüdud Tooted</a>
        <a href=src/tehtud_tood/tehtud_tood.php>Tehtud Tööd</a>
        <div class=dropdown>
            <button class=dropbtn>Rehvid
                <i class=fa fa-caret-down></i>
            </button>
            <div class=dropdown-content>
                <a href=src/rehv_myyk/rehv_myyk.php>Müüdud Rehvid</a>
                <a href=src/rehv_ladu/rehv_ladu.php>Rehvid Laos</a>
            </div>
        </div>
    </nav>";
    }

    ?>
    <h1>Tehtud Tööd</h1>
    <a href="lisa_too.php" class="lisa-link">Lisa Töö</a>
    <input type="text" id="searchBar" onkeyup="search()" placeholder="Otsi Reg.Nr">
    <table id=myTable>
        <thead>
            <tr>
                <td>Auto Reg.Nr</td>
                <td>Kuupäev</td>
                <td>Odomeeter</td>
                <td width="50%">Tehtud Tööd</td>
                <td>Tegevus</td>
            </tr>
        </thead>
        <tbody>
            <?php
            $i = 0;
            while ($row = mysqli_fetch_array($result)) {
                ?>
                <tr>
                    <td>
                        <?php echo $row["RegNr"]; ?>
                        <a href="../../src/pdf_generaator/pdf_koostamine.php?too_id=<?php echo $row['too_id']; ?>" target="_blank">
                            <i class="fa-solid fa-file-pdf fa-lg pdf-icon"></i>
                        </a>
                    </td>
                    <td><?php echo $row["FormattedDate"]; ?></td>
                    <td><?php echo $row["Odomeeter"]; ?> km</td>
                    <td><?php echo $row["Tehtud_tood"]; ?></td>
                    <td>
                        <a href="../../src/tehtud_tood/edit-work-process.php?too_id=<?php echo $row["too_id"]; ?>">
                            <i class="fa-solid fa-pen-to-square fa-lg muuda-icon"></i>
                        </a>
                    </td>
                </tr>
                <?php
                $i++;
            }
            ?>
        </tbody>
    </table>
    <footer>
        <p>Rõngu Auto OÜ</p>
        <p>Copyright &copy;
            <script>document.write(new Date().getFullYear())</script>
        </p>
    </footer>
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
                    tr[i].style.display = ""; // Show the row
                } else {
                    tr[i].style.display = "none"; // Hide the row
                }
            }
        }

        // Ensure the thead remains visible even after filtering
        var headerRow = table.querySelector("thead tr");
        if (headerRow) {
            headerRow.style.display = ""; // Show the header row
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