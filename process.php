<?php
include_once 'laoseis.php';
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
    <title>Toote Sisestus</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
    <a href="index.php">Avaleht</a>
    <a href="myyk.php">Müüdud Tooted</a>
    <a href="insert.php" class="active">Lisa Toode</a>
</nav>
<footer>
    <p>Rõngu Auto OÜ</p>
    <p>Copyright &copy; <script>document.write(new Date().getFullYear())</script></p>
</footer>
</body>
</html>
<?php
	 } else {
		echo "Error: Midagi läks valesti!" . $sql . "
" . mysqli_error($conn);
	 }
	 mysqli_close($conn);
}
?>