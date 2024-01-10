<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" href="style.css">
        <title>Lisa Töö</title>
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
    
    <h1>Lisa Tehtud Töö</h1>   
	<form method="post" action="lisa_too-process.php">
		Auto Reg.Nr:<br>
		<input type="text" name="RegNr">
		<br>
		Kuupäev:<br>
		<input type="date" name="Kuupaev">
		<br><br>
		Odomeeter:<br>
		<input type="number" name="Odomeeter">
		<br>
		Tehtud Tööd:<br>
		<textarea type="text" name="Tehtud_tood" rows="4" cols="48"></textarea>

		<br><br>
		<input type="submit" name="submit" value="Sisesta">
	</form>
<footer>
    <p>Rõngu Auto OÜ</p>
    <p>Copyright &copy; <script>document.write(new Date().getFullYear())</script></p>
</footer>
  </body>
</html>