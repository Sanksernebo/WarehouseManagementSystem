<?php
global $row;
include_once '../db/laoseis.php';
if(isset($_POST['submit']))
{	 
	 $RegNr = $_POST['RegNr'];
	 $Kuupaev = $_POST['Kuupaev'];
	 $Kogus = $_POST['Kogus'];
	 $Moot = $_POST['Moot'];
    $Tootja = $_POST['Tootja'];
    $hooaeg = $_POST['hooaeg'];
    $tarnija = $_POST['tarnija'];


	 $sql = "INSERT INTO Rehvi_myyk (RegNr,Moot,Tootja,Kogus,Hooaeg,Tarnija,Kuupaev)
	 VALUES ('$RegNr','$Moot','$Tootja','$Kogus','$hooaeg','$tarnija','$Kuupaev')";
	 $message = "Sisestatud edukalt";
	 if (mysqli_query($conn, $sql)) {
	    $message = "Sisestatud edukalt";
         header("Location: rehv_myyk/rehv_myyk.php");
         exit();
?>
<html>
<head>
    <title>Rehvi Müügi Sisestus</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0">
    <meta charset="utf-8">
    <link rel="stylesheet" href="/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="icon" type="image/x-icon" href="img/cartehniklogo_svg.svg">
</head>
<body>
<nav><nav>
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

<footer>
    <p>Rõngu Auto OÜ</p>
    <p>Copyright &copy; <script>document.write(new Date().getFullYear())</script></p>
</footer>
</body>
</html>
<?php
	 }
     else{
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
        <a href=src/lisa_lattu/lisa_lattu.php class=active>Lisa Toode</a>
    </nav>";
    }
	 mysqli_close($conn);
}
?>