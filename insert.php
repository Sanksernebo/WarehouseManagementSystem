<!DOCTYPE html>
<head>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="icon" type="image/x-icon" href="cartehniklogo_svg.svg">
    <title>Toote Sisestus</title>
</head>
<body>
<nav>
    <a href="index.php">Avaleht</a>
    <a href="myyk.php">Müüdud Tooted</a>
    <a href="tehtud_tood.php">Tehtud Tööd</a>
    <div class="dropdown">
        <button class="dropbtn">Rehvid
            <i class="fa fa-caret-down"></i>
        </button>
        <div class="dropdown-content">
            <a href="rehv_myyk.php">Müüdud Rehvid</a>
            <a href="rehv_ladu.php">Rehvid Laos</a>
        </div>
    </div>
    <a href="insert.php" class="active">Lisa Toode</a>
</nav>
<h1>Lisa Toode</h1>
	<form method="post" action="process.php">
		Tootekood:<br>
		<input type="text" name="Tootekood">
		<br>
		Nimetus:<br>
		<input type="text" name="Nimetus">
		<br>
		Kogus:<br>
		<input type="number" name="Kogus">
		<br>
		Sisseostu Hind:<br>
		<input type="number" step="0.01" name="Sisseost">
		<br>
		Jaehind:<br>
		<input type="number" step="0.01" name="Jaehind">
		<br>
        Tehtud Hind:<br>
	<input type="number" value="0" step="0.01" name="Lopphind" readonly>
		<br>
		<lable for="Ost" name="Ost">Ostetud</lable>
		<select name="ost" value="-">
		    <option value="-">-</option>
		    <option value="InterCars">Inter Cars</option>
		    <option value="AD Baltic">AD Baltic</option>
		    <option value="Balti Autoosad">Balti Autoosad</option>
		</select>
		<br>
		<lable for="Olek" name="Olek">Olek</lable>
		<select name="olek" value="Isiklik">
		    <option value="Isiklik">Isiklik</option>
		    <option value="Firma">Firma</option>
		    <option value="Tagastus">Tagastus</option>
		</select>
		<br><br>
		<input type="submit" name="submit" value="Sisesta">
	</form>
<footer>
    <p>Rõngu Auto OÜ</p>
    <p>Copyright &copy; <script>document.write(new Date().getFullYear())</script></p>
</footer>
  </body>
</html>