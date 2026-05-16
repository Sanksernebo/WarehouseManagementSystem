<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}
include_once '../db/laoseis.php';
require_once '../includes/csrf.php';

if (isset($_GET['kalendri_id'])) {
    $kalendri_id = $_GET['kalendri_id'];
    $stmt = $conn->prepare("SELECT * FROM Kalender WHERE kalendri_id = ?");
    $stmt->bind_param("i", $kalendri_id);
    $stmt->execute();
    $result = $stmt->get_result();

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

if (isset($_POST['confirm_delete'])) {
    csrf_verify();

    $stmt = $conn->prepare("DELETE FROM Kalender WHERE kalendri_id = ?");
    $stmt->bind_param("i", $kalendri_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
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
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0">
    <meta charset="utf-8">
    <link rel="stylesheet" href="../../style.css">
    <script src="https://kit.fontawesome.com/4d1395116e.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="icon" type="image/x-icon" href="../img/cartehniklogo_svg.svg">
    <title>Kustuta broneering</title>
</head>
<body>
<?php require_once '../includes/nav.php'; ?>

    <h1>Kustuta Broneering</h1>
    <form method="post" action="">
        <?= csrf_field() ?>
        <div>
            <label>Kliendi nimi:</label>
            <input type="text" name="kliendi_nimi" value="<?php echo htmlspecialchars($appointment['kliendi_nimi']); ?>" readonly><br>

            <label>Broneeritud kuupäev:</label>
            <input type="date" name="broneeritud_aeg" value="<?php echo htmlspecialchars($appointment['broneeritud_aeg']); ?>" readonly><br>

            <label>Algusaeg:</label>
            <input type="time" name="algus_aeg" value="<?php echo htmlspecialchars($appointment['algus_aeg']); ?>" readonly><br>

            <label>Lõppaeg:</label>
            <input type="time" name="lopp_aeg" value="<?php echo htmlspecialchars($appointment['lopp_aeg']); ?>" readonly><br>

            <label>Kirjeldus:</label>
            <textarea readonly><?php echo htmlspecialchars($appointment['kirjeldus']); ?></textarea><br>

            <label>Registreerimisnumber:</label>
            <input type="text" name="reg_nr" value="<?php echo htmlspecialchars($appointment['reg_nr']); ?>" readonly><br>

            <div class="formButton">
                <input type="submit" name="confirm_delete" value="Kinnita Kustutamine" class="button">
            </div>
        </div>
    </form>

<?php require_once '../includes/footer.php'; ?>
</body>
</html>