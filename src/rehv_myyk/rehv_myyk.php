<?php
include_once '../db/laoseis.php';
$result = mysqli_query($conn,"SELECT UPPER(RegNr) as RegNr, Kuupaev, Kogus, UPPER(Moot) AS Moot,Tootja, Hooaeg, Tarnija FROM Rehvi_myyk ORDER BY Kuupaev DESC");
?>
    <!DOCTYPE html>
<html>
 <head>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0">
    <meta charset="utf-8">
    <link rel="stylesheet" href="/style.css">
    <link rel="icon" type="image/x-icon" href="img/cartehniklogo_svg.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
 <title>Laoseis</title>
 </head>
<body>
<nav>
        <a href="../../index.php">Avaleht</a>
        <a href="/src/myydud_tooted/myyk.php">Müüdud Tooted</a>
        <a href="/src/tehtud_tood/tehtud_tood.php">Tehtud Tööd</a>
        <div class="dropdown">
            <button class="dropbtn">Rehvid
                <i class="fa fa-caret-down"></i>
            </button>
            <div class="dropdown-content">
                <a href="/src/rehv_myyk/rehv_myyk.php">Müüdud Rehvid</a>
                <a href="/src/rehv_ladu/rehv_ladu.php">Rehvid Laos</a>
            </div>
        </div>
        <a href="/src/lisa_lattu/lisa_lattu.php" class="active">Lisa Toode</a>
    </nav>
    <h1>Müüdud Rehvid</h1>
    <a href="lisa_rehv_myyk.php" class="lisa-link">Lisa Müük</a>
    <br><br>

    <table id=myTable>
        <input type="text" id="myInput" onkeyup="search()" placeholder="Otsi Reg.Nr">

        <tr>
            <td>Auto Reg.Nr</td>
            <td>Mõõt</td>
            <td>Tootja</td>
            <td>Kogus</td>
            <td>Hooaeg</td>
            <td>Tarnija</td>
            <td>Kuupäev</td>
        </tr>
        <?php
        $i=0;
        while($row = mysqli_fetch_array($result)) {
            ?>
            <tr class="<?php if(isset($classname)) echo $classname;?>">
                <td> <?php echo $row["RegNr"]; ?></td>
                <td><?php echo $row["Moot"]; ?></td>
                <td><?php echo $row["Tootja"]; ?></td>
                <td><?php echo $row["Kogus"]; ?> tk</td>
                <td><?php echo $row["Hooaeg"]; ?></td>
                <td><?php echo $row["Tarnija"]; ?></td>
                <td><?php echo $row["Kuupaev"]; ?></td>
            </tr>
            <?php
            $i++;
        }
        ?>
        <script>
            function search() {
                // Declare variables
                var input, filter, table, tr, td, i, txtValue;
                input = document.getElementById("myInput");
                filter = input.value.toUpperCase();
                table = document.getElementById("myTable");
                tr = table.getElementsByTagName("tr");

                // Loop through all table rows, and hide those who don't match the search query
                for (i = 0; i < tr.length; i++) {
                    td = tr[i].getElementsByTagName("td")[1];
                    if (td) {
                        txtValue = td.textContent || td.innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            tr[i].style.display = "";
                        } else {
                            tr[i].style.display = "none";
                        }
                    }
                }
            }
        </script>
    </table>

    <footer>
        <p>Rõngu Auto OÜ</p>
        <p>Copyright &copy; <script>document.write(new Date().getFullYear())</script></p>
    </footer>
</body>
</html>