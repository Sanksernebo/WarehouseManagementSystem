<?php
session_start();
// Check if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page
    header("Location: ../login/login.php");
    exit;
}

include_once '../db/laoseis.php';

// Check if appointment ID is provided in the URL
if (isset($_GET['kalendri_id'])) {
    $kalendri_id = $_GET['kalendri_id'];
    $stmt = $conn->prepare("SELECT * FROM Kalender WHERE kalendri_id = ?");
    $stmt->bind_param("i", $kalendri_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch the appointment data
    if ($result->num_rows == 1) {
        $appointment = $result->fetch_assoc();
    } else {
        echo "<p>Broneeringut ei leitud.</p>";
        exit();
    }
    $stmt->close();
} else {
    echo "<p>Broneeringu ID puudub.</p>";
    exit();
}

// Handle delete confirmation
if (isset($_POST['confirm_delete'])) {
    $stmt = $conn->prepare("DELETE FROM Kalender WHERE kalendri_id = ?");
    $stmt->bind_param("i", $kalendri_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "<p>Edukalt kustutatud!</p>";
        header("Location: kalender.php");
        exit();
    } else {
        echo "<p>Viga: Ei suutnud kustutada.</p>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="et">
<head>
    <meta charset="UTF-8">
    <title>Kustuta broneering</title>
    <link rel="stylesheet" href="../../style.css">
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
                <button class="dropbtn">Rehvid<i class="fa fa-caret-down"></i></button>
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

    <h1>Kustuta Broneering</h1>
    <form method="post" action="">
        <div>
            <label>Kliendi nimi:</label>
            <input type="text" value="<?php echo htmlspecialchars($appointment['kliendi_nimi']); ?>" readonly><br>

            <label>Broneeritud kuupäev:</label>
            <input type="date" value="<?php echo $appointment['broneeritud_aeg']; ?>" readonly><br>

            <label>Algusaeg:</label>
            <input type="time" value="<?php echo $appointment['algus_aeg']; ?>" readonly><br>

            <label>Lõppaeg:</label>
            <input type="time" value="<?php echo $appointment['lopp_aeg']; ?>" readonly><br>

            <label>Kirjeldus:</label>
            <textarea readonly><?php echo htmlspecialchars($appointment['kirjeldus']); ?></textarea><br>

            <label>Registreerimisnumber:</label>
            <input type="text" value="<?php echo htmlspecialchars($appointment['reg_nr']); ?>" readonly><br>

            <div class="formButton">
                <input type="submit" name="confirm_delete" value="Kinnita Kustutamine" class="button">
            </div>
        </div>
    </form>

    <footer>
        <p>Rõngu Auto OÜ</p>
        <p>&copy; <script>document.write(new Date().getFullYear())</script></p>
    </footer>
</body>
</html>