<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}
include_once '../db/laoseis.php';
$result = mysqli_query($conn, "SELECT Tootekood, Nimetus, Kogus, DATE_FORMAT(Kuupaev, '%d.%m.%Y %H:%i') AS FormattedDate, Sisseost, Hind, Summa FROM Ladu_logi ORDER BY Kuupaev DESC");
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="../../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="icon" type="image/x-icon" href="../img/cartehniklogo_svg.svg">
    <title>Müüdud tooted</title>
</head>

<body>
<?php require_once '../includes/nav.php'; ?>

    <h1>Müüdud Tooted</h1>
    <?php if (mysqli_num_rows($result) > 0): ?>
    <table>
        <thead>
            <tr>
                <td>Tootekood</td>
                <td>Nimetus</td>
                <td>Kogus</td>
                <td>Kuupäev</td>
                <td>Sisseostu Hind</td>
                <td>Hind</td>
                <td>Summa</td>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_array($result)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row["Tootekood"]); ?></td>
                    <td><?php echo htmlspecialchars($row["Nimetus"]); ?></td>
                    <td><?php echo htmlspecialchars($row["Kogus"]); ?></td>
                    <td><?php echo htmlspecialchars($row["FormattedDate"]); ?></td>
                    <td><?php echo htmlspecialchars($row["Sisseost"]); ?></td>
                    <td><?php echo htmlspecialchars($row["Hind"]); ?></td>
                    <td><?php echo htmlspecialchars($row["Summa"]); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p style="font-weight:bold">Tulemusi ei leitud</p>
    <?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
</body>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var currentUrl = window.location.href;
        document.querySelectorAll('.nav-links a').forEach(function (link) {
            if (link.href === currentUrl) {
                link.classList.add('active');
            }
        });
    });
</script>

</html>
