<?php
include_once 'laoseis.php';
$sql = "DELETE FROM Ladu WHERE ID='" . $_GET["ID"] . "'";
if (mysqli_query($conn, $sql)) {
    echo "Edukalt kustutatud";
?>
<!DOCTYPE html>
<html>
 <head>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0">
    <meta charset="utf-8">
 <link rel="stylesheet" href="style.css">
 <title> Laoseis</title>
 </head>
<body>
    <nav>
    <a href="index.php">Avaleht</a>
    <a href="myyk.php">Müüdud Tooted</a>
    <a href="insert.php" class="active">Lisa Toode</a>
    </nav>
<?php
    $message ="<h1>Edukalt kustutatud</h1>";
    
} else {
    echo "Error kustutamisel: " . mysqli_error($conn);
}
mysqli_close($conn);
?>
<footer>
    <p>Rõngu Auto OÜ</p>
    <p>Copyright &copy; <script>document.write(new Date().getFullYear())</script></p>
</footer>
 </body>
</html>