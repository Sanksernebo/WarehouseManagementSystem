<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="icon" type="image/x-icon" href="cartehniklogo_svg.svg">
        <title>Rehvid Laos</title>
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
    
    <h1>Lisa Müüdud Rehvid</h1>
	<form method="post" action="rehv_ladu-process.php">
		Auto Reg.Nr:<br>
		<input type="text" name="RegNr">
		<br>
		Omanik:<br>
		<input type="text" name="Omanik">
		<br>
        Kogus:<br>
        <input type="number" name="Kogus">
        <br>
        <lable for="Hooaeg" name="Hooaeg">Hooaeg</lable>
        <select name="hooaeg" value="Suverehv">
            <option value="Suverehv">Suverehv</option>
            <option value="Naastrehv">Naastrehv</option>
            <option value="Lamellrehv">Lamellrehv</option>
        </select>
        <br>
        Kuupäev<br>
        <input type="date" name="Kuupaev">
		<br><br>
		<input type="submit" name="submit" value="Lisa">
	</form>
<footer>
    <p>Rõngu Auto OÜ</p>
    <p>Copyright &copy; <script>document.write(new Date().getFullYear())</script></p>
</footer>
  </body>
</html>