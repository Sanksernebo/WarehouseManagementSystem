<?php
session_start();
// Check if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page
    header("Location: ../login/login.php");
    exit;
}
include_once '../db/laoseis.php';

// Get the appointment ID from the URL
if (isset($_GET['kalendri_id'])) {
    $kalendri_id = $_GET['kalendri_id'];

    // Fetch the existing appointment details
    $sql = "SELECT * FROM Kalender WHERE kalendri_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $kalendri_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if an appointment was found
    if ($result->num_rows > 0) {
        $appointment = $result->fetch_assoc();  // Fetch the appointment details
    } else {
        echo "<p>Broneeringut ei leitud.</p>";
        exit;  // Stop the script if no appointment is found
    }
} else {
    echo "<p>Broneeringu ID puudub.</p>";
    exit;  // Stop the script if no appointment ID is provided
}

// Handle the form submission to update the appointment
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kliendi_nimi = $_POST['kliendi_nimi'];
    $broneeritud_aeg = $_POST['broneeritud_aeg'];
    $algus_aeg = $_POST['algus_aeg'];
    $lopp_aeg = $_POST['lopp_aeg'];
    $kirjeldus = $_POST['kirjeldus'];
    $reg_nr = $_POST['reg_nr'];

    $update_sql = "UPDATE Kalender SET kliendi_nimi = ?, broneeritud_aeg = ?, algus_aeg = ?, lopp_aeg = ?, kirjeldus = ?, reg_nr = ? WHERE kalendri_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssssssi", $kliendi_nimi, $broneeritud_aeg, $algus_aeg, $lopp_aeg, $kirjeldus, $reg_nr, $kalendri_id);

    if ($update_stmt->execute()) {
        // Redirect back to the calendar after a successful update
        header('Location: kalender.php');
        exit;
    } else {
        echo "Viga: " . $update_stmt->error;
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0">
    <meta charset="utf-8">
    <link rel="stylesheet" href="../../style.css">
    <script src="https://kit.fontawesome.com/4d1395116e.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="icon" type="image/x-icon" href="../img/cartehniklogo_svg.svg">
    <title>Laoseis - Muuda broneeringut</title>
</head>
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
        <a href="../../src/kalender/kalender.php">Töögraafik</a>
        <a href="../login/logout.php">
            <?php if (isset($_SESSION['username'])): ?>
                <span><?php echo htmlspecialchars($_SESSION['username']); ?>,</span>
            <?php endif; ?>
            Logi välja
        </a>
    </div>
</nav>

<body>

    <form method="POST">
        <input type="hidden" name="kalendri_id" value="<?php echo $appointment['kalendri_id']; ?>">
        <label for="kliendi_nimi">Kliendi nimi:</label>
        <input type="text" name="kliendi_nimi" value="<?php echo $appointment['kliendi_nimi']; ?>" required><br>

        <label for="reg_nr">Registreerimisnumber:</label>
        <input type="text" name="reg_nr" value="<?php echo $appointment['reg_nr']; ?>"><br>

        <label for="broneeritud_aeg">Broneeritud kuupäev:</label>
        <input type="date" name="broneeritud_aeg" value="<?php echo $appointment['broneeritud_aeg']; ?>" required><br>

        <label for="algus_aeg">Algusaeg:</label>
        <input type="time" name="algus_aeg" value="<?php echo $appointment['algus_aeg']; ?>" required><br>

        <label for="lopp_aeg">Lõppaeg:</label>
        <input type="time" name="lopp_aeg" value="<?php echo $appointment['lopp_aeg']; ?>" required><br>

        <label for="kirjeldus">Kirjeldus:</label>
        <textarea name="kirjeldus"><?php echo $appointment['kirjeldus']; ?></textarea><br>

        <div class="formButton">
            <input type="submit" name="submit" value="Muuda broneeringut" class="button">
        </div>
    </form>

</body>
<footer>
    <p>Rõngu Auto OÜ</p>
    <p>Copyright &copy;
        <script>document.write(new Date().getFullYear())</script>
    </p>
</footer>

</html>