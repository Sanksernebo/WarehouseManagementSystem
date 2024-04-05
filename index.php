<?php
global $conn;
include_once 'src/db/laoseis.php';
$result = mysqli_query($conn,"SELECT Tootekood, Nimetus, Kogus, Sisseost, Jaehind, Ost, Olek FROM ladu ORDER BY ID DESC");
?>
<!DOCTYPE html>
<html>
 <head>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0">
    <meta charset="utf-8">
 <link rel="stylesheet" href="style.css">
 <title> Laoseis</title>
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
     <link rel="icon" type="image/x-icon" href="src/img/cartehniklogo_svg.svg">
 </head>
<body>
<nav>
    <a href="index.php">Avaleht</a>
    <a href="src/myydud_tooted/myyk.php">Müüdud Tooted</a>
    <a href="src/tehtud_tood/tehtud_tood.php">Tehtud Tööd</a>
    <div class="dropdown">
        <button class="dropbtn">Rehvid
            <i class="fa fa-caret-down"></i>
        </button>
        <div class="dropdown-content">
            <a href="src/rehv_myyk/rehv_myyk.php">Müüdud Rehvid</a>
            <a href="src/rehv_ladu/rehv_ladu.php">Rehvid Laos</a>
        </div>
    </div>
    <a href="src/lisa_lattu/lisa_lattu.php" class="active">Lisa Toode</a>
</nav>
<?php
if (mysqli_num_rows($result) > 0) {
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
      <a href=src/lisa_lattu/lisa_lattu.php class=active>Lisa Toode</a>
  </nav>";
};

?>
<h1>Laoseis</h1>
<table id=myTable>
    <input type="text" id="myInput" onkeyup="myFunction()" placeholder="Sisesta Tootekood">
	<tr>
    	<td>Tootekood</td>
    	<td>Nimetus</td>
    	<td>Kogus</td>
    	<td>Sisseostu Hind</td>
    	<td>Jaehind</td>
    	<td>Ostetud</td>
    	<td>Olek</td>
    	<td>Tegevus</td>
	</tr>
    	<?php
    	$i=0;
    	while($row = mysqli_fetch_array($result)) {
	    ?>
	<tr class="<?php if(isset($classname)) echo $classname;?>">
	<td><?php echo $row["Tootekood"]; ?></td>
	<td><?php echo $row["Nimetus"]; ?></td>
	<td><?php echo $row["Kogus"]; ?></td>
	<td><?php echo $row["Sisseost"]; ?></td>
	<td><?php echo $row["Jaehind"]; ?></td>
	<td><?php echo $row["Ost"]; ?></td>
	<td><?php echo $row["Olek"]; ?></td>
	<td class="button-container">
	    <a href="update-process.php?ID=<?php echo $row["ID"]; ?>" class="muuda-link">Muuda</a>
	    <a href="delete-process.php?ID=<?php echo $row["ID"]; ?>" class="kustuta-link">Kustuta</a>
    </td>
	</tr>
	<?php
	$i++;
	}
	?>
	
	<script>
function myFunction() {
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