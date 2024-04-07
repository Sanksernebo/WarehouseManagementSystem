<?php
include_once '../db/laoseis.php';
$sql = "DELETE FROM Ladu WHERE ID='" . $_GET["ID"] . "'";
if (mysqli_query($conn, $sql)) {
    echo "Edukalt kustutatud";
?>
<!DOCTYPE html>
<html>
 <head>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0">
    <meta charset="utf-8">
    <link rel="icon" type="image/x-icon" href="img/cartehniklogo_svg.svg">
    <link rel="stylesheet" href="/style.css">
    <title> Laoseis</title>
 </head>
<body>
<nav>
	<div class="logo">
			<a href="../../index.php">
				<img src="/src/img/cartehniklogo_valge.svg" alt="Cartehnik logo">
			</a>
	</div>
	<div class="nav-links">
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
    </div>
    </nav>
<?php
    $message ="<h1>Edukalt kustutatud</h1>";
    
} 
else{
    echo "<p style=font-weight:bold>Viga kustutamisel. </p>";
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
?>
<footer>
    <p>Rõngu Auto OÜ</p>
    <p>Copyright &copy; <script>document.write(new Date().getFullYear())</script></p>
</footer>
 </body>
</html>