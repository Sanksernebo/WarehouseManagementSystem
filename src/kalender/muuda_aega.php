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

$error        = '';
$error_fields  = [];
$conflict_info = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    csrf_verify();

    $kliendi_nimi    = $_POST['kliendi_nimi'];
    $broneeritud_aeg = $_POST['broneeritud_aeg'];
    $algus_aeg       = $_POST['algus_aeg'];
    $lopp_aeg        = $_POST['lopp_aeg'];
    $kirjeldus       = $_POST['kirjeldus'];
    $reg_nr          = $_POST['reg_nr'];

    $appointment['kliendi_nimi']    = $kliendi_nimi;
    $appointment['broneeritud_aeg'] = $broneeritud_aeg;
    $appointment['algus_aeg']       = $algus_aeg;
    $appointment['lopp_aeg']        = $lopp_aeg;
    $appointment['kirjeldus']       = $kirjeldus;
    $appointment['reg_nr']          = $reg_nr;

    if ($algus_aeg >= $lopp_aeg) {
        $error        = "Algusaeg peab olema enne lõppaega.";
        $error_fields = ['algus_aeg', 'lopp_aeg'];
    } else {
        $conflict = $conn->prepare(
            "SELECT kliendi_nimi, reg_nr, algus_aeg, lopp_aeg FROM Kalender
             WHERE broneeritud_aeg = ?
               AND algus_aeg < ? AND lopp_aeg > ?
               AND kalendri_id != ?
             LIMIT 1"
        );
        $conflict->bind_param("sssi", $broneeritud_aeg, $lopp_aeg, $algus_aeg, $kalendri_id);
        $conflict->execute();
        $conflict_result = $conflict->get_result();

        if ($conflict_row = $conflict_result->fetch_assoc()) {
            $error         = "Valitud ajavahemik on juba broneeritud.";
            $error_fields  = ['broneeritud_aeg', 'algus_aeg', 'lopp_aeg'];
            $conflict_info = $conflict_row;
        } else {
            $update_stmt = $conn->prepare(
                "UPDATE Kalender SET kliendi_nimi=?, broneeritud_aeg=?, algus_aeg=?, lopp_aeg=?, kirjeldus=?, reg_nr=? WHERE kalendri_id=?"
            );
            $update_stmt->bind_param("ssssssi", $kliendi_nimi, $broneeritud_aeg, $algus_aeg, $lopp_aeg, $kirjeldus, $reg_nr, $kalendri_id);

            if ($update_stmt->execute()) {
                header('Location: kalender.php');
                exit;
            } else {
                $error = "Uuendamine ebaõnnestus.";
            }
            $update_stmt->close();
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
    <title>Laoseis - Muuda broneeringut</title>
</head>

<body>
<?php require_once '../includes/nav.php'; ?>

    <h1>Muuda broneeringut</h1>

    <?php if ($error): ?>
        <p class="error">
            <?= htmlspecialchars($error) ?>
            <?php if ($conflict_info): ?>
                — <strong><?= htmlspecialchars($conflict_info['kliendi_nimi']) ?></strong>
                <?php if ($conflict_info['reg_nr']): ?>
                    (<?= htmlspecialchars($conflict_info['reg_nr']) ?>)
                <?php endif; ?>
                <?= htmlspecialchars(substr($conflict_info['algus_aeg'], 0, 5)) ?>–<?= htmlspecialchars(substr($conflict_info['lopp_aeg'], 0, 5)) ?>
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="kalendri_id" value="<?php echo htmlspecialchars($appointment['kalendri_id']); ?>">

        <label for="kliendi_nimi">Kliendi nimi:</label>
        <input type="text" name="kliendi_nimi" value="<?php echo htmlspecialchars($appointment['kliendi_nimi']); ?>" required><br>

        <label for="reg_nr">Registreerimisnumber:</label>
        <input type="text" name="reg_nr" value="<?php echo htmlspecialchars($appointment['reg_nr']); ?>"><br>

        <label for="broneeritud_aeg">Broneeritud kuupäev:</label>
        <input type="date" name="broneeritud_aeg" value="<?php echo htmlspecialchars($appointment['broneeritud_aeg']); ?>"
               class="<?= in_array('broneeritud_aeg', $error_fields) ? 'field-error' : '' ?>" required><br>

        <label for="algus_aeg">Algusaeg:</label>
        <select id="algus_aeg" name="algus_aeg" class="<?= in_array('algus_aeg', $error_fields) ? 'field-error' : '' ?>" required>
            <?php for ($h = 9; $h <= 17; $h++):
                $val = sprintf('%02d:00', $h); ?>
                <option value="<?= $val ?>" <?= substr($appointment['algus_aeg'], 0, 5) === $val ? 'selected' : '' ?>><?= $val ?></option>
            <?php endfor; ?>
        </select><br>

        <label for="lopp_aeg">Lõppaeg:</label>
        <select id="lopp_aeg" name="lopp_aeg" class="<?= in_array('lopp_aeg', $error_fields) ? 'field-error' : '' ?>" required>
            <?php for ($h = 10; $h <= 18; $h++):
                $val = sprintf('%02d:00', $h); ?>
                <option value="<?= $val ?>" <?= substr($appointment['lopp_aeg'], 0, 5) === $val ? 'selected' : '' ?>><?= $val ?></option>
            <?php endfor; ?>
        </select><br>

        <label for="kirjeldus">Kirjeldus:</label>
        <textarea name="kirjeldus"><?php echo htmlspecialchars($appointment['kirjeldus']); ?></textarea><br>

        <div class="formButton">
            <input type="submit" name="submit" value="Muuda broneeringut" class="button">
        </div>
    </form>

<?php require_once '../includes/footer.php'; ?>
</body>

</html>
