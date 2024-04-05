<?php
include_once '../db/laoseis.php';
if(isset($_POST['submit']))
{	 
	 $tootekood = $_POST['Tootekood'];
	 $nimetus = $_POST['Nimetus'];
	 $kogus = $_POST['Kogus'];
	 $sisseost = $_POST['Sisseost'];
	 $jaehind = $_POST['Jaehind'];
	 $lopphind = $_POST['Lopphind'];
	 $ost = $_POST['ost'];
	 $olek = $_POST['olek'];
	 $sql = "INSERT INTO Ladu (Tootekood,Nimetus,Kogus,Sisseost,Jaehind, Lopphind, Ost, Olek)
	 VALUES ('$tootekood','$nimetus','$kogus','$sisseost','$jaehind','$lopphind','$ost','$olek')";
	 $message = "Sisestatud edukalt";
	 if (mysqli_query($conn, $sql)) {
	    $message = "Sisestatud edukalt";
?>
<html>
<head>
	<link rel="icon" type="image/x-icon" href="img/cartehniklogo_svg.svg">
	<link rel="stylesheet" href="/style.css">
    <title>Toote Sisestus</title>
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