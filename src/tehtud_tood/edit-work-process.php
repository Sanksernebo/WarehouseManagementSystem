<?php
include_once '../db/laoseis.php';
if(count($_POST)>0) {
mysqli_query($conn,"UPDATE Tehtud_tood set Auto_id='" . $_POST['Auto_id'] . "',RegNr='" . $_POST['RegNr'] . "', Kuupaev='" . $_POST['Kuupaev'] . "', Odomeeter='" . $_POST['Odomeeter'] . "' ,Tehtud_tood='" . $_POST['Tehtud_tood'] . "' WHERE Auto_id='" . $_POST['Auto_id'] . "'");
$message = "Edukalt uuendatud!";
header("Location: ../tehtud_tood/tehtud_tood.php");
}
$result = mysqli_query($conn,"SELECT * FROM Tehtud_tood WHERE Auto_id='" . $_GET['Auto_id'] . "'");
$row= mysqli_fetch_array($result);
?>
<html>
<head>
    <link rel="stylesheet" href="/style.css">
    <link rel="icon" type="image/x-icon" href="img/cartehniklogo_svg.svg">
    <title>Tehtud Tööd -  <?php echo $row['RegNr']; ?></title>
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
    </div>
    </nav>
<form name="frmUser" method="post" action="">
<div><?php if(isset($message)) { echo $message; } ?>
</div>
<div style="padding-bottom:5px;">

ID: <br>
<input type="text" name="Auto_id"  value="<?php echo $row['Auto_id']; ?>" readonly>
<br>
Reg Nr: <br>
<input type="text" name="RegNr" class="txtField" value="<?php echo $row['RegNr']; ?>">
<br>
Kuupäev:<br>
<input type="datetime" name="Kuupaev" class="txtField" value="<?php echo $row['Kuupaev']; ?>"readonly>
<br>
Odomeeter:<br>
<input type="number" name="Odomeeter" class="txtField" value="<?php echo $row['Odomeeter']; ?>">
<br>
Tehtud Tööd:<br>
<input type="text" name="Tehtud_tood" class="txtField" value="<?php echo $row['Tehtud_tood']; ?>" rows="4" cols="48">
<br>
<div class="formButton">
<input type="submit" name="submit" value="Uuenda" class="button">
</div>
</div>
</form>

<footer>
    <p>Rõngu Auto OÜ</p>
    <p>Copyright &copy; <script>document.write(new Date().getFullYear())</script></p>
</footer>
</body>
</html>