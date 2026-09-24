<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}
include_once '../db/laoseis.php';
require_once '../includes/csrf.php';

$hooajad = ['Suverehv', 'Naastrehv', 'Lamellrehv'];
// value => label; FIXUS exists in the DB enum, so keep it selectable for old rows
$tarnijad = [
    'INTERCARS' => 'InterCars',
    'ERIMELL'   => 'Erimell',
    'LATTAKO'   => 'Latakko',
    'FIXUS'     => 'Fixus',
    'MUU'       => 'Muu',
];
$message = '';

if (count($_POST) > 0) {
    csrf_verify();

    $row = [
        'rehvimyyk_id' => $_POST['rehvimyyk_id'],
        'RegNr'        => $_POST['RegNr'],
        'Moot'         => $_POST['Moot'],
        'Tootja'       => $_POST['Tootja'],
        'Kogus'        => $_POST['Kogus'],
        'Hooaeg'       => $_POST['hooaeg'],
        'Tarnija'      => $_POST['tarnija'],
        'Kuupaev'      => $_POST['Kuupaev'],
    ];

    $stmt = mysqli_prepare($conn, "UPDATE Rehvi_myyk SET RegNr=?, Moot=?, Tootja=?, Kogus=?, Hooaeg=?, Tarnija=?, Kuupaev=? WHERE rehvimyyk_id=?");
    mysqli_stmt_bind_param($stmt, 'sssisssi', $row['RegNr'], $row['Moot'], $row['Tootja'], $row['Kogus'], $row['Hooaeg'], $row['Tarnija'], $row['Kuupaev'], $row['rehvimyyk_id']);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        header("Location: rehv_myyk.php");
        exit;
    }
    $message = "Uuendamine ebaõnnestus.";
    mysqli_stmt_close($stmt);
} elseif (isset($_GET['id'])) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM Rehvi_myyk WHERE rehvimyyk_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $_GET['id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_array($result);
    mysqli_stmt_close($stmt);
    if (!$row) {
        echo "Error: Kirjet ei leitud";
        exit;
    }
} else {
    header("Location: rehv_myyk.php");
    exit;
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0">
    <meta charset="utf-8">
    <link rel="stylesheet" href="../../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="icon" type="image/x-icon" href="../img/cartehniklogo_svg.svg">
    <title>Müüdud Rehvid - <?php echo htmlspecialchars(strtoupper($row['RegNr'])); ?></title>
</head>

<body>
<?php require_once '../includes/nav.php'; ?>

    <h1>Muuda Müüdud Rehve</h1>
    <form method="post" action="">
        <?= csrf_field() ?>
        <?php if ($message): ?>
            <p style="font-weight:bold;"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <input type="hidden" name="rehvimyyk_id" value="<?php echo htmlspecialchars($row['rehvimyyk_id']); ?>">
        Auto Reg.Nr:<br>
        <input type="text" name="RegNr" value="<?php echo htmlspecialchars($row['RegNr']); ?>">
        <br>
        Mõõt:<br>
        <input type="text" name="Moot" value="<?php echo htmlspecialchars($row['Moot']); ?>">
        <br>
        Tootja:
        <input type="text" name="Tootja" value="<?php echo htmlspecialchars($row['Tootja']); ?>">
        <br>
        Kogus:<br>
        <input type="number" name="Kogus" value="<?php echo htmlspecialchars($row['Kogus']); ?>">
        <br>
        <label for="hooaeg">Hooaeg</label>
        <select id="hooaeg" name="hooaeg">
            <?php foreach ($hooajad as $hooaeg): ?>
                <option value="<?php echo $hooaeg; ?>" <?php if ($row['Hooaeg'] === $hooaeg) echo 'selected'; ?>><?php echo $hooaeg; ?></option>
            <?php endforeach; ?>
        </select>
        <br>
        <label for="tarnija">Tarnija</label>
        <select id="tarnija" name="tarnija">
            <?php foreach ($tarnijad as $value => $label): ?>
                <option value="<?php echo $value; ?>" <?php if ($row['Tarnija'] === $value) echo 'selected'; ?>><?php echo $label; ?></option>
            <?php endforeach; ?>
        </select>
        <br>
        Kuupäev<br>
        <input type="date" name="Kuupaev" value="<?php echo htmlspecialchars($row['Kuupaev']); ?>">
        <br><br>
        <div class="formButton">
            <input type="submit" name="submit" value="Uuenda">
        </div>
    </form>

<?php require_once '../includes/footer.php'; ?>
</body>

</html>
