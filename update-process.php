<?php
include_once 'laoseis.php';
if(count($_POST)>0) {
mysqli_query($conn,"UPDATE Ladu set ID='" . $_POST['ID'] . "',Tootekood='" . $_POST['Tootekood'] . "', Nimetus='" . $_POST['Nimetus'] . "', Kogus='" . $_POST['Kogus'] . "' ,Sisseost='" . $_POST['Sisseost'] . "', Jaehind='" . $_POST['Jaehind'] . "',Lopphind='" . $_POST['Lopphind'] . "', Ost='" . $_POST['Ost'] . "',Olek='" . $_POST['Olek'] . "' WHERE ID='" . $_POST['ID'] . "'");
$message = "Edukalt uuendatud!";
}
$result = mysqli_query($conn,"SELECT * FROM Ladu WHERE ID='" . $_GET['ID'] . "'");
$row= mysqli_fetch_array($result);
?>
<html>
<head>
<title>Toote andmed</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
    <a href="index.php">Avaleht</a>
    <a href="myyk.php">Müüdud Tooted</a>
    <a href="tehtud_tood.php">Tehtud Tööd</a>
    <a href="insert.php" class="active">Lisa Toode</a>
</nav>
<form name="frmUser" method="post" action="">
<div><?php if(isset($message)) { echo $message; } ?>
</div>
<div style="padding-bottom:5px;">

ID: <br>
<input type="hidden" name="ID" class="txtField" value="<?php echo $row['ID']; ?>">
<input type="text" name="ID"  value="<?php echo $row['ID']; ?>" readonly>
<br>
Tootekood: <br>
<input type="text" name="Tootekood" class="txtField" value="<?php echo $row['Tootekood']; ?>">
<br>
Nimetus:<br>
<input type="text" name="Nimetus" class="txtField" value="<?php echo $row['Nimetus']; ?>">
<br>
Kogus:<br>
<input type="number" name="Kogus" class="txtField" value="<?php echo $row['Kogus']; ?>">
<br>
Sisseostetu Hind:<br>
<input type="number" name="Sisseost" class="txtField" value="<?php echo $row['Sisseost']; ?>">
<br>
Jaehind:<br>
<input type="number" name="Jaehind" class="txtField" value="<?php echo $row['Jaehind']; ?>">
<br>
Tehtud Hind:<br>
<input type="number" name="Lopphind" step="0.01" class="txtField" value="0">
<lable for="ost" name="Ost">Ostetud</lable>
<select id="ost" name="Ost">
    <option value="-" <?php if($row['Ost'] == '-') echo 'selected'; ?>>-</option>
    <option value="InterCars" <?php if($row['Ost'] == 'InterCars') echo 'selected'; ?>>Inter Cars</option>
    <option value="AD Baltic" <?php if($row['Ost'] == 'AD Baltic') echo 'selected'; ?>>AD Baltic</option>
    <option value="Balti Autoosad" <?php if($row['Ost'] == 'Balti Autoosad') echo 'selected'; ?>>Balti Autoosad</option>
</select>
<br>
<lable for="olek" name="Olek">Olek</lable>
<select id="olek" name="Olek">
    <option value="Isiklik" <?php if($row['Olek'] == 'Isiklik') echo 'selected'; ?>>Isiklik</option>
    <option value="Firma" <?php if($row['Olek'] == 'Firma') echo 'selected'; ?>>Firma</option>
    <option value="Tagastus" <?php if($row['Olek'] == 'Tagastus') echo 'selected'; ?>>Tagastus</option>
</select>
<br>
<input type="submit" name="submit" value="Uuenda" class="buttom">
</div>
</form>

<footer>
    <p>Rõngu Auto OÜ</p>
    <p>Copyright &copy; <script>document.write(new Date().getFullYear())</script></p>
</footer>
</body>
</html>