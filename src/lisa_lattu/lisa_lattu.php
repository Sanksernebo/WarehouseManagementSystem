<?php
session_start();
// Check if the user is not logged in
if (!isset($_SESSION['user_id'])) {
	// Redirect to the login page
	header("Location: ../login/login.php");
	exit;
}
?>
<!DOCTYPE html>

<head>
	<link rel="stylesheet" href="../../style.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
	<link rel="icon" type="image/x-icon" href="../img/cartehniklogo_svg.svg">
	<title>Toote Sisestus</title>
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
			<a href="../login/logout.php">
				<?php if (isset($_SESSION['username'])): ?>
					<span><?php echo htmlspecialchars($_SESSION['username']); ?>,</span>
				<?php endif; ?>
				Logi välja
			</a>
		</div>
	</nav>
	<h1>Lisa Toode</h1>
	<form method="post" action="lisa_lattu-process.php">
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
		<label for="Ost" name="Ost">Ostetud</label>
		<select name="ost" value="-">
			<option value="-">-</option>
			<option value="InterCars">Inter Cars</option>
			<option value="AD Baltic">AD Baltic</option>
			<option value="Balti Autoosad">Balti Autoosad</option>
			<option value="Erimell">Erimell</option>
		</select>
		<br>
		<label for="Olek" name="Olek">Olek</label>
		<select name="olek" value="Isiklik">
			<option value="Isiklik">Isiklik</option>
			<option value="Firma">Firma</option>
			<option value="Tagastus">Tagastus</option>
		</select>
		<br><br>
		<div class="formButton">
			<input type="submit" name="submit" value="Sisesta">
		</div>
	</form>
	<footer>
		<p>Rõngu Auto OÜ</p>
		<p>Copyright &copy;
			<script>document.write(new Date().getFullYear())</script>
		</p>
	</footer>
</body>

</html>