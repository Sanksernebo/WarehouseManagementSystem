<?php
include_once 'laoseis.php';
$result = mysqli_query($conn,"SELECT UPPER(RegNr) as RegNr, Kuupaev, Odomeeter, Tehtud_tood FROM Tehtud_tood ORDER BY Kuupaev DESC");
?>
<!DOCTYPE html>
<html>
 <head>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0">
    <meta charset="utf-8">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
     <link rel="icon" type="image/x-icon" href="cartehniklogo_svg.svg">
 <title>Laoseis</title>
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
<?php
if (mysqli_num_rows($result) > 0) {
}
else{
    echo "<p style=font-weight:bold>Tulemusi ei leitud </p>";
    echo "<nav>
    <a href=index.php>Avaleht</a>
    <a href=myyk.php>Müüdud Tooted</a>
    <a href=tehtud_tood.php>Tehtud Tööd</a>
    <a href=insert.php class=active>Lisa Toode</a>
        </nav>";
}

?>
<h1>Tehtud Tööd</h1>
<a href="lisa_too.php" class="lisa-link">Lisa Töö</a>
<br><br>

<table id=myTable>
    <input type="text" id="myInput" onkeyup="search()" placeholder="Otsi Reg.Nr">

	<tr>
    	<td>Auto Reg.Nr</td>
    	<td>Kuupäev</td>
    	<td>Odomeeter</td>
    	<td width="35%">Tehtud Tööd</td>
	</tr>
    	<?php
    	$i=0;
    	while($row = mysqli_fetch_array($result)) {
	    ?>
	<tr class="<?php if(isset($classname)) echo $classname;?>"><td> <a href="pdf_koostamine.php"><?php echo $row["RegNr"]; ?></a></td>
	<td><?php echo $row["Kuupaev"]; ?></td>
	<td><?php echo $row["Odomeeter"]; ?> km</td>
	<td><?php echo $row["Tehtud_tood"]; ?></td>


	
	</tr>
		<?php
	$i++;
	}
	?>
	<script>
function search() {
  // Declare variables
  var input, filter, table, tr, td, i, txtValue;
  input = document.getElementById("myInput");
  filter = input.value.toUpperCase();
  table = document.getElementById("myTable");
  tr = table.getElementsByTagName("tr");

  // Loop through all table rows, and hide those who don't match the search query
  for (i = 0; i < tr.length; i++) {
    td = tr[i].getElementsByTagName("td")[0];
    if (td) {
      txtValue = td.textContent || td.innerText;
      if (txtValue.toUpperCase().indexOf(filter) > -1) {
        tr[i].style.display = "";
      } else {
        tr[i].style.display = "none";
      }
    }
  }
}
</script>
</table>

<footer>
    <p>Rõngu Auto OÜ</p>
    <p>Copyright &copy; <script>document.write(new Date().getFullYear())</script></p>
</footer>
</body>
</html>