<?php
session_start();
// Check if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page
    header("Location: ../login/login.php");
    exit;
}
include_once '../db/laoseis.php';

// Check for POST data and update the database
if (count($_POST) > 0) {
    // Prepare an SQL statement for execution
    $stmt = mysqli_prepare($conn, "UPDATE Tehtud_tood SET too_id=?, RegNr=?, Kuupaev=?, Odomeeter=?, Tehtud_tood=? WHERE too_id=?");

    // Bind variables to the prepared statement as parameters
    mysqli_stmt_bind_param($stmt, 'sssssi', $_POST['too_id'], $_POST['RegNr'], $_POST['Kuupaev'], $_POST['Odomeeter'], $_POST['Tehtud_tood'], $_POST['too_id']);

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
    header("Location: ../tehtud_tood/tehtud_tood.php");
    exit;
}

// Securely fetch data if too_id is provided via GET
if (isset($_GET['too_id'])) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM Tehtud_tood WHERE too_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $_GET['too_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_array($result)) {
        // Record is fetched and stored in $row
    } else {
        echo "Error: Autot ei leitud";
        exit;
    }
    mysqli_stmt_close($stmt);
}
?>

<html>

<head>
    <link rel="stylesheet" href="../../style.css">
    <link rel="icon" type="image/x-icon" href="../img/cartehniklogo_svg.svg">
    <title>Tehtud Tööd - <?php echo $row['RegNr']; ?></title>
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
            <a href="src/login/logout.php">
                <?php if (isset($_SESSION['username'])): ?>
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?>,</span>
                <?php endif; ?>Logi välja</a>
        </div>
    </nav>
    <form name="frmUser" method="post" action="">
        <div><?php if (isset($message)) {
            echo $message;
        } ?>
        </div>
        <div style="padding-bottom:5px;">

            ID: <br>
            <input type="text" name="too_id" value="<?php echo $row['too_id']; ?>" readonly>
            <br>
            Reg Nr: <br>
            <input type="text" name="RegNr" class="txtField" value="<?php echo $row['RegNr']; ?>">
            <br>
            Kuupäev:<br>
            <input type="datetime-local" name="Kuupaev" class="txtField" value="<?php echo $row['Kuupaev']; ?>">
            <br>
            Odomeeter:<br>
            <input type="number" name="Odomeeter" class="txtField" value="<?php echo $row['Odomeeter']; ?>">
            <br>
            Tehtud Tööd:<br>
            <textarea type="text" name="Tehtud_tood" rows="4" cols="48"><?php echo $row['Tehtud_tood']; ?></textarea>
            <br>
            <div class="formButton">
                <input type="submit" name="submit" value="Uuenda" class="button">
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