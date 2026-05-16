<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}
include_once '../db/laoseis.php';
require_once '../includes/csrf.php';

$error        = '';
$error_fields = [];
$conflict_info = null;
$form = [
    'kliendi_nimi'    => '',
    'reg_nr'          => '',
    'broneeritud_aeg' => '',
    'algus_aeg'       => '',
    'lopp_aeg'        => '',
    'kirjeldus'       => '',
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    csrf_verify();

    $form['kliendi_nimi']    = $_POST['kliendi_nimi'];
    $form['broneeritud_aeg'] = $_POST['broneeritud_aeg'];
    $form['algus_aeg']       = $_POST['algus_aeg'];
    $form['lopp_aeg']        = $_POST['lopp_aeg'];
    $form['kirjeldus']       = $_POST['kirjeldus'];
    $form['reg_nr']          = $_POST['reg_nr'];
    $user_id                 = $_SESSION['user_id'];

    if ($form['algus_aeg'] >= $form['lopp_aeg']) {
        $error        = "Algusaeg peab olema enne lõppaega.";
        $error_fields = ['algus_aeg', 'lopp_aeg'];
    } else {
        $conflict = $conn->prepare(
            "SELECT kliendi_nimi, reg_nr, algus_aeg, lopp_aeg FROM Kalender
             WHERE broneeritud_aeg = ?
               AND algus_aeg < ? AND lopp_aeg > ?
             LIMIT 1"
        );
        $conflict->bind_param("sss", $form['broneeritud_aeg'], $form['lopp_aeg'], $form['algus_aeg']);
        $conflict->execute();
        $conflict_result = $conflict->get_result();

        if ($conflict_row = $conflict_result->fetch_assoc()) {
            $error        = "Valitud ajavahemik on juba broneeritud.";
            $error_fields = ['broneeritud_aeg', 'algus_aeg', 'lopp_aeg'];
            $conflict_info = $conflict_row;
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO Kalender (kliendi_nimi, broneeritud_aeg, algus_aeg, lopp_aeg, kirjeldus, reg_nr, user_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("ssssssi", $form['kliendi_nimi'], $form['broneeritud_aeg'], $form['algus_aeg'], $form['lopp_aeg'], $form['kirjeldus'], $form['reg_nr'], $user_id);

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

    <form method="POST" action="lisa_uus_aeg.php">
        <?= csrf_field() ?>
        <label for="kliendi_nimi">Kliendi nimi:</label>
        <input type="text" id="kliendi_nimi" name="kliendi_nimi" value="<?= htmlspecialchars($form['kliendi_nimi']) ?>" required><br>

        <label for="reg_nr">Registreerimisnumber:</label>
        <input type="text" id="reg_nr" name="reg_nr" value="<?= htmlspecialchars($form['reg_nr']) ?>"><br>

        <label for="broneeritud_aeg">Broneeritud kuupäev:</label>
        <input type="date" id="broneeritud_aeg" name="broneeritud_aeg" value="<?= htmlspecialchars($form['broneeritud_aeg']) ?>"
               class="<?= in_array('broneeritud_aeg', $error_fields) ? 'field-error' : '' ?>" required><br>

        <label for="algus_aeg">Algusaeg:</label>
        <select id="algus_aeg" name="algus_aeg" class="<?= in_array('algus_aeg', $error_fields) ? 'field-error' : '' ?>" required>
            <option value="">-- Vali algusaeg --</option>
            <?php for ($h = 9; $h <= 17; $h++):
                $val = sprintf('%02d:00', $h); ?>
                <option value="<?= $val ?>" <?= $form['algus_aeg'] === $val ? 'selected' : '' ?>><?= $val ?></option>
            <?php endfor; ?>
        </select><br>

        <label for="lopp_aeg">Lõppaeg:</label>
        <select id="lopp_aeg" name="lopp_aeg" class="<?= in_array('lopp_aeg', $error_fields) ? 'field-error' : '' ?>" required>
            <option value="">-- Vali lõppaeg --</option>
            <?php for ($h = 10; $h <= 18; $h++):
                $val = sprintf('%02d:00', $h); ?>
                <option value="<?= $val ?>" <?= $form['lopp_aeg'] === $val ? 'selected' : '' ?>><?= $val ?></option>
            <?php endfor; ?>
        </select><br>

        <label for="kirjeldus">Kirjeldus:</label>
        <textarea id="kirjeldus" name="kirjeldus"><?= htmlspecialchars($form['kirjeldus']) ?></textarea><br>

        <div class="formButton">
            <input type="submit" name="submit" value="Loo Broneering">
        </div>
    </form>

<?php require_once '../includes/footer.php'; ?>
</body>

</html>
