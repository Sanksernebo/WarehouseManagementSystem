<?php
session_start();
// Check if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page
    header("Location: ../login/login.php");
    exit;
}
include_once '../db/laoseis.php';

if (count($_POST) > 0) {
    // Prepare an SQL statement for execution
    $stmt = mysqli_prepare($conn, "UPDATE Ladu SET Tootekood=?, Nimetus=?, Kogus=?, Sisseost=?, Jaehind=?, Lopphind=?, Ost=?, Olek=? WHERE toote_id=?");

    // Bind variables to a prepared statement as parameters
    mysqli_stmt_bind_param($stmt, 'ssddsdssi', $_POST['Tootekood'], $_POST['Nimetus'], $_POST['Kogus'], $_POST['Sisseost'], $_POST['Jaehind'], $_POST['Lopphind'], $_POST['Ost'], $_POST['Olek'], $_POST['toote_id']);

    // Execute the prepared statement
    mysqli_stmt_execute($stmt);

    // Check if the statement was successful
    if (mysqli_stmt_affected_rows($stmt) > 0) {
        $message = "Edukalt uuendatud!";
    } else {
        $message = "Uuendamine ebaõnnestus: " . mysqli_stmt_error($stmt);
    }

    // Close the statement
    mysqli_stmt_close($stmt);

    // Redirect after update
    header("Location: ../../index.php");
    exit;
}

// Securely fetch data if ID is provided via GET
if (isset($_GET['ID'])) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM Ladu WHERE toote_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $_GET['ID']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_array($result)) {
        // Record is fetched and stored in $row
    } else {
        echo "Error: Toodet ei leitud";
        exit;
    }
    mysqli_stmt_close($stmt);
}
?>

<html>

<head>
    <link rel="stylesheet" href="../../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="icon" type="image/x-icon" href="../img/cartehniklogo_svg.svg">
    <title>Toote andmed</title>
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

    <h1>Muuda toote andmeid</h1>

    <form name="frmUser" method="post" action="">
        <div><?php if (isset($message)) {
            echo $message;
        } ?>
        </div>
        <div style="padding-bottom:5px;">

            ID: <br>
            <input type="hidden" name="ID" class="txtField" value="<?php echo $row['toote_id']; ?>">
            <input type="text" name="ID" value="<?php echo $row['toote_id']; ?>" readonly>
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
                <option value="-" <?php if ($row['Ost'] == '-')
                    echo 'selected'; ?>>-</option>
                <option value="InterCars" <?php if ($row['Ost'] == 'InterCars')
                    echo 'selected'; ?>>Inter Cars</option>
                <option value="AD Baltic" <?php if ($row['Ost'] == 'AD Baltic')
                    echo 'selected'; ?>>AD Baltic</option>
                <option value="Balti Autoosad" <?php if ($row['Ost'] == 'Balti Autoosad')
                    echo 'selected'; ?>>Balti
                    Autoosad</option>
            </select>
            <br>
            <lable for="olek" name="Olek">Olek</lable>
            <select id="olek" name="Olek">
                <option value="Isiklik" <?php if ($row['Olek'] == 'Isiklik')
                    echo 'selected'; ?>>Isiklik</option>
                <option value="Firma" <?php if ($row['Olek'] == 'Firma')
                    echo 'selected'; ?>>Firma</option>
                <option value="Tagastus" <?php if ($row['Olek'] == 'Tagastus')
                    echo 'selected'; ?>>Tagastus</option>
            </select>
            <br>
            <div class="formButton">
                <input type="submit" name="submit" value="Uuenda" class="buttom">
            </div>
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