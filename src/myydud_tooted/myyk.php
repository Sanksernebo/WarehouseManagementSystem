<?php
session_start();
// Check if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page
    header("Location: ../login/login.php");
    exit;
}
include_once '../db/laoseis.php';
$result = mysqli_query($conn,"SELECT Tootekood, Nimetus, Kogus, DATE_FORMAT(Kuupaev, '%d.%m.%Y %H:%i') AS FormattedDate, Sisseost, Hind, Summa FROM Ladu_logi ORDER BY Kuupaev DESC");
?>
<!DOCTYPE html>
<html>
 <head>
     <meta charset="utf-8">
    <link rel="stylesheet" href="/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="icon" type="image/x-icon" href="../img/cartehniklogo_svg.svg">
    <title>Müüdud tooted</title>
 </head>
    <body>
    <nav>
	<div class="logo">
			<a href="../../index.php">
				<img src="/src/img/cartehniklogo_valge.svg" alt="Cartehnik logo">
			</a>
	</div>
	<div class="nav-links">
        <a href="../../index.php">Avaleht</a>
        <a href="/src/myydud_tooted/myyk.php">Müüdud Tooted</a>
        <a href="/src/tehtud_tood/tehtud_tood.php">Tehtud Tööd</a>
        <div class="dropdown">
            <button class="dropbtn">Rehvid
                <i class="fa fa-caret-down"></i>
            </button>
            <div class="dropdown-content">
                <a href="/src/rehv_myyk/rehv_myyk.php">Müüdud Rehvid</a>
                <a href="/src/rehv_ladu/rehv_ladu.php">Rehvid Laos</a>
            </div>
        </div>
        <a href="../login/logout.php">
                <?php if (isset($_SESSION['username'])): ?>
                <span><?php echo htmlspecialchars($_SESSION['username']); ?>,</span>
                <?php endif; ?>
         Logi välja
        </a>
    </div>
    </nav>
        <?php
        if (mysqli_num_rows($result) > 0) {
        ?>
<h1>Müüdud Tooted</h1>
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
    <?php
    	$i=0;
    	while($row = mysqli_fetch_array($result)) {
	    ?>
    <tbody>
        <tr>
            <td><?php echo $row["Tootekood"]; ?></td>
            <td><?php echo $row["Nimetus"]; ?></td>
            <td><?php echo $row["Kogus"]; ?></td>
            <td><?php echo $row["FormattedDate"]; ?></td>
            <td><?php echo $row["Sisseost"]; ?></td>
            <td><?php echo $row["Hind"]; ?></td>
            <td><?php echo $row["Summa"]; ?></td>
        </tr>
    </tbody>
		<?php
	$i++;
	}
	?>
<?php
}
else{
    echo "<p style=font-weight:bold>Tulemusi ei leitud </p>";
    echo "
    <nav>
        <a href=index.php>Avaleht</a>
        <a href=src/myydud_tooted/myyk.php>Müüdud Tooted</a>
        <a href=src/tehtud_tood/tehtud_tood.php>Tehtud Tööd</a>
        <div class=dropdown>
            <button class=dropbtn>Rehvid
                <i class=fa fa-caret-down></i>
            </button>
            <div class=dropdown-content>
                <a href=src/rehv_myyk/rehv_myyk.php>Müüdud Rehvid</a>
                <a href=src/rehv_ladu/rehv_ladu.php>Rehvid Laos</a>
            </div>
        </div>
    </nav>";
}
?>
<footer>
    <p>Rõngu Auto OÜ</p>
    <p>Copyright &copy; <script>document.write(new Date().getFullYear())</script></p>
</footer>
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