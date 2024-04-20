<?php
include_once '../db/laoseis.php';
$message = '';

if (isset($_POST['submit'])) {
    $RegNr = $_POST['RegNr'];
    $Kuupaev = $_POST['Kuupaev'];
    $Kogus = $_POST['Kogus'];
    $Omanik = $_POST['Omanik'];
    $hooaeg = $_POST['hooaeg'];

    // Prepare the SQL statement to avoid SQL injection
    $stmt = mysqli_prepare($conn, "INSERT INTO Rehvi_ladu (RegNr, Kuupaev, Omanik, Kogus, Hooaeg) VALUES (?, ?, ?, ?, ?)");
    
    // Check if the statement was prepared successfully
    if ($stmt === false) {
        die('MySQL prepare error: ' . mysqli_error($conn));
    }
    
    // Bind parameters to the prepared statement as strings
    mysqli_stmt_bind_param($stmt, 'sssss', $RegNr, $Kuupaev, $Omanik, $Kogus, $hooaeg);

    // Execute the prepared statement
    if (mysqli_stmt_execute($stmt)) {
        $message = "Sisestatud edukalt";
        mysqli_stmt_close($stmt); // Close the statement to free up resources
        header("Location: rehv_ladu.php"); // Redirect after successful insertion
        exit;
    } else {
        $message = "Midagi läks valesti: " . mysqli_error($conn);
        mysqli_stmt_close($stmt); // Close the statement to free up resources
    }
}

if (!isset($_POST['submit']) || !empty($message)) :
?>
<html>
<head>
    <title>Rehvide Laoseis</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0">
    <meta charset="utf-8">
    <link rel="stylesheet" href="/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
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
        <a href="/src/lisa_lattu/lisa_lattu.php" class="active">Lisa Toode</a>
    </nav>
<footer>
    <p>Rõngu Auto OÜ</p>
    <p>Copyright &copy; <script>document.write(new Date().getFullYear())</script></p>
</footer>
</body>
</html>
<?php
endif;
mysqli_close($conn);
?>