<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}
include_once '../db/laoseis.php';

if (isset($_GET['kalendri_id'])) {
    $kalendri_id = $_GET['kalendri_id'];

    $sql = "SELECT * FROM Kalender WHERE kalendri_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $kalendri_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $appointment = $result->fetch_assoc();
    } else {
        echo "<p>Broneeringut ei leitud.</p>";
        exit;
    }
} else {
    echo "<p>Broneeringu ID puudub.</p>";
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once '../includes/csrf.php';
    csrf_verify();

    $kliendi_nimi  = $_POST['kliendi_nimi'];
    $broneeritud_aeg = $_POST['broneeritud_aeg'];
    $algus_aeg     = $_POST['algus_aeg'];
    $lopp_aeg      = $_POST['lopp_aeg'];
    $kirjeldus     = $_POST['kirjeldus'];
    $reg_nr        = $_POST['reg_nr'];

    if ($algus_aeg >= $lopp_aeg) {
        $error = "Algusaeg peab olema enne lõppaega.";
    } elseif ($broneeritud_aeg < date('Y-m-d')) {
        $error = "Kuupäev ei saa olla minevikus.";
    } else {
        $update_sql = "UPDATE Kalender SET kliendi_nimi=?, broneeritud_aeg=?, algus_aeg=?, lopp_aeg=?, kirjeldus=?, reg_nr=? WHERE kalendri_id=?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssssssi", $kliendi_nimi, $broneeritud_aeg, $algus_aeg, $lopp_aeg, $kirjeldus, $reg_nr, $kalendri_id);

        if ($update_stmt->execute()) {
            header('Location: kalender.php');
            exit;
        } else {
            $error = "Uuendamine ebaõnnestus.";
        }
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

<body>
<?php require_once '../includes/nav.php'; ?>

    <h1>Muuda broneeringut</h1>

    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="kalendri_id" value="<?php echo htmlspecialchars($appointment['kalendri_id']); ?>">

        <label for="kliendi_nimi">Kliendi nimi:</label>
        <input type="text" name="kliendi_nimi" value="<?php echo htmlspecialchars($appointment['kliendi_nimi']); ?>" required><br>

        <label for="reg_nr">Registreerimisnumber:</label>
        <input type="text" name="reg_nr" value="<?php echo htmlspecialchars($appointment['reg_nr']); ?>"><br>

        <label for="broneeritud_aeg">Broneeritud kuupäev:</label>
        <input type="date" name="broneeritud_aeg" value="<?php echo htmlspecialchars($appointment['broneeritud_aeg']); ?>" required><br>

        <label for="algus_aeg">Algusaeg:</label>
        <input type="time" name="algus_aeg" step="3600" min="09:00" max="18:00" value="<?php echo htmlspecialchars($appointment['algus_aeg']); ?>" required><br>

        <label for="lopp_aeg">Lõppaeg:</label>
        <input type="time" name="lopp_aeg" step="3600" min="09:00" max="18:00" value="<?php echo htmlspecialchars($appointment['lopp_aeg']); ?>" required><br>

        <label for="kirjeldus">Kirjeldus:</label>
        <textarea name="kirjeldus"><?php echo htmlspecialchars($appointment['kirjeldus']); ?></textarea><br>

        <div class="formButton">
            <input type="submit" name="submit" value="Muuda broneeringut" class="button">
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
