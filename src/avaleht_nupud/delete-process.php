<?php
include_once '../db/laoseis.php';

// Using prepared statements to safely fetch data
if (isset($_GET['ID'])) {
    $id = $_GET['ID'];
    $stmt = mysqli_prepare($conn, "SELECT * FROM Ladu WHERE ID = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_array($result);
    } else {
        echo "Error: Toodet ei leitud";
        exit();
    }
    mysqli_stmt_close($stmt); // Close the prepared statement
}

// Using prepared statements to safely delete data
if (isset($_POST['confirm_delete'])) {
    $id = $_POST['ID'];
    $stmt = mysqli_prepare($conn, "DELETE FROM Ladu WHERE ID = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        $message = "Edukalt kustutatud!";
        header("Location: ../../index.php");
        exit();
    } else {
        echo "Error: Ei suutnud kustutada. Kontrollige ID-d.";
    }
    mysqli_stmt_close($stmt); // Close the prepared statement
}
?>

<html>
<head>
    <link rel="stylesheet" href="/style.css">
    <link rel="icon" type="image/x-icon" href="img/cartehniklogo_svg.svg">
    <title>Toote andmed</title>
</head>
<body>
<nav>
    <div class="logo">
        <a href="../../index.php">
            <img src="/src/img/cartehniklogo_valge.svg" alt="Cartehnik logo">
        </a>
    </div>
    <div class="nav-links">
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
    </div>
</nav>
<h1> Kustuta toode laost </h1>
<form name="frmUser" method="post" action="">
<div><?php if(isset($message)) { echo $message; } ?></div>

<div style="padding-bottom:5px;">
    <input type="hidden" name="ID" value="<?php echo $row['ID']; ?>">
    <label for="ID">ID:</label>

    <input type="text" id="ID" name="ID" value="<?php echo $row['ID']; ?>" readonly><br>
    <label for="Tootekood">Tootekood:</label>

    <input type="text" id="Tootekood" name="Tootekood" value="<?php echo $row['Tootekood']; ?>" readonly><br>
    <label for="Nimetus">Nimetus:</label>

    <input type="text" id="Nimetus" name="Nimetus" value="<?php echo $row['Nimetus']; ?>" readonly><br>
    <label for="Kogus">Kogus:</label>

    <input type="number" id="Kogus" name="Kogus" value="<?php echo $row['Kogus']; ?>" readonly><br>
    <label for="Sisseost">Sisseostetu Hind:</label>

    <input type="number" id="Sisseost" name="Sisseost" value="<?php echo $row['Sisseost']; ?>" readonly><br>
    <label for="Jaehind">Jaehind:</label>

    <input type="number" id="Jaehind" name="Jaehind" value="<?php echo $row['Jaehind']; ?>" readonly><br>
    <label for="Ost">Ostetud:</label>

    <input type="text" id="Ost" name="Ost" value="<?php echo $row['Ost']; ?>" readonly><br>
    <label for="Olek">Olek:</label>

    <input type="text" id="Olek" name="Olek" value="<?php echo $row['Olek']; ?>" readonly><br>
    <div class="formButton">
    <input type="submit" name="confirm_delete" value="Kinnita Kustutamine">
    </div>
</div>
</form>

<footer>
    <p>Rõngu Auto OÜ</p>
    <p>Copyright &copy; <script>document.write(new Date().getFullYear())</script></p>
</footer>
</body>
</html>
