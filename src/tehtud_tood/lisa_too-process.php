<?php
include_once '../db/laoseis.php';
if(isset($_POST['submit']))
{	 
	 $RegNr = $_POST['RegNr'];
	 $Kuupaev = $_POST['Kuupaev'];
	 $Odomeeter = $_POST['Odomeeter'];
	 $Tehtud_tood = $_POST['Tehtud_tood'];

	 $sql = "INSERT INTO Tehtud_tood (RegNr,Kuupaev,Odomeeter,Tehtud_tood)
	 VALUES ('$RegNr','$Kuupaev','$Odomeeter','$Tehtud_tood')";
	 $message = "Sisestatud edukalt";
	 if (mysqli_query($conn, $sql)) {
	    $message = "Sisestatud edukalt";
        header("Location: tehtud_tood/tehtud_tood.php");
        exit();
?>
<html>
<head>
    <title>Töö Sisestus</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0">
    <meta charset="utf-8">
    <link rel="stylesheet" href="/style.css">
    <link rel="icon" type="image/x-icon" href="img/cartehniklogo_svg.svg">
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
    </nav>

    <form name="frmUser" method="post" action="">
<div><?php if(isset($message)) { echo $message; } ?>
</div>
<div style="padding-bottom:5px;">

Reg.Nr: <br>
<input type="text" name="RegNr" class="txtField" value="<?php echo $row['RegNr']; ?>">
<br>
Kuupäev:<br>
<input type="date" name="Kuupaev" class="txtField" value="<?php echo $row['Kuupaev']; ?>">
<br>
Odomeeter:<br>
<input type="number" name="Odomeeter" class="txtField" value="<?php echo $row['Odomeeter']; ?>">
<br>
Tehtud Tööd:<br>
<textarea type="text" name="Tehtud_tood" class="txtField" value="<?php echo $row['Tehtud_tood']; ?>"></textarea>

<br>
<input type="submit" name="submit" value="Lisa" class="button">
</div>
</form>
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
        </nav>";
    }
	 mysqli_close($conn);
}
?>