<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}
include_once '../db/laoseis.php';
require_once '../includes/csrf.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    csrf_verify();

    $kliendi_nimi    = $_POST['kliendi_nimi'];
    $broneeritud_aeg = $_POST['broneeritud_aeg'];
    $algus_aeg       = $_POST['algus_aeg'];
    $lopp_aeg        = $_POST['lopp_aeg'];
    $kirjeldus       = $_POST['kirjeldus'];
    $reg_nr          = $_POST['reg_nr'];
    $user_id         = $_SESSION['user_id'];

    if ($algus_aeg >= $lopp_aeg) {
        $error = "Algusaeg peab olema enne lõppaega.";
    } elseif ($broneeritud_aeg < date('Y-m-d')) {
        $error = "Kuupäev ei saa olla minevikus.";
    } else {
        // Check for overlapping bookings on the same date
        $conflict = $conn->prepare(
            "SELECT kalendri_id FROM Kalender
             WHERE broneeritud_aeg = ?
               AND algus_aeg < ? AND lopp_aeg > ?
             LIMIT 1"
        );
        $conflict->bind_param("sss", $broneeritud_aeg, $lopp_aeg, $algus_aeg);
        $conflict->execute();
        $conflict->store_result();

        if ($conflict->num_rows > 0) {
            $error = "Valitud ajavahemik on juba broneeritud.";
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO Kalender (kliendi_nimi, broneeritud_aeg, algus_aeg, lopp_aeg, kirjeldus, reg_nr, user_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("ssssssi", $kliendi_nimi, $broneeritud_aeg, $algus_aeg, $lopp_aeg, $kirjeldus, $reg_nr, $user_id);

            if ($stmt->execute()) {
                header('Location: kalender.php');
                exit;
            } else {
                $error = "Sisestamine ebaõnnestus.";
            }
            $stmt->close();
        }
        $conflict->close();
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
    <title>Laoseis</title>
</head>

<body>
<?php require_once '../includes/nav.php'; ?>

    <h1>Loo uus broneering</h1>

    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="POST" action="lisa_uus_aeg.php">
        <?= csrf_field() ?>
        <label for="kliendi_nimi">Kliendi nimi:</label>
        <input type="text" id="kliendi_nimi" name="kliendi_nimi" required><br>

        <label for="reg_nr">Registreerimisnumber:</label>
        <input type="text" id="reg_nr" name="reg_nr"><br>

        <label for="broneeritud_aeg">Broneeritud kuupäev:</label>
        <input type="date" id="broneeritud_aeg" name="broneeritud_aeg" required><br>

        <label for="algus_aeg">Algusaeg:</label>
        <input type="time" step="3600" min="09:00" max="18:00" id="algus_aeg" name="algus_aeg" required><br>

        <label for="lopp_aeg">Lõppaeg:</label>
        <input type="time" step="3600" id="lopp_aeg" min="09:00" max="18:00" name="lopp_aeg" required><br>

        <label for="kirjeldus">Kirjeldus:</label>
        <textarea id="kirjeldus" name="kirjeldus"></textarea><br>

        <div class="formButton">
            <input type="submit" name="submit" value="Loo Broneering">
        </div>
    </form>

<?php require_once '../includes/footer.php'; ?>
</body>

<script>
    const startTimeInput = document.getElementById('algus_aeg');
    const endTimeInput = document.getElementById('lopp_aeg');

    startTimeInput.addEventListener('input', (e) => {
        let hour = e.target.value.split(':')[0];
        e.target.value = `${hour}:00`;
    });

    endTimeInput.addEventListener('input', (e) => {
        let hour = e.target.value.split(':')[0];
        e.target.value = `${hour}:00`;
    });
</script>

</html>
