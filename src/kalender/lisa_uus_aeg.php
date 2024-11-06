<?php
session_start();
// Check if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page
    header("Location: ../login/login.php");
    exit;
}
include_once '../db/laoseis.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kliendi_nimi = $_POST['kliendi_nimi'];
    $broneeritud_aeg = $_POST['broneeritud_aeg'];
    $algus_aeg = $_POST['algus_aeg'];
    $lopp_aeg = $_POST['lopp_aeg'];
    $kirjeldus = $_POST['kirjeldus'];
    $reg_nr = $_POST['reg_nr'];
    $user_id = $_SESSION['user_id']; // Capture the logged-in user's ID

    // Validate the time to ensure it's on the hour
    $algus_minute = date('i', strtotime($algus_aeg));
    $lopp_minute = date('i', strtotime($lopp_aeg));

    if (!in_array($algus_minute, ['00']) || !in_array($lopp_minute, ['00'])) {
        echo "Algusaeg ja lõppaeg peavad olema täistunnil!";
        exit;
    }

    // Prepare the SQL statement to avoid SQL injection
    $stmt = $conn->prepare("INSERT INTO Kalender (kliendi_nimi, broneeritud_aeg, algus_aeg, lopp_aeg, kirjeldus, reg_nr, user_id) 
    VALUES (?, ?, ?, ?, ?, ?, ?)");

    // Bind parameters
    $stmt->bind_param("ssssssi", $kliendi_nimi, $broneeritud_aeg, $algus_aeg, $lopp_aeg, $kirjeldus, $reg_nr, $user_id);

    // Execute the statement
    if ($stmt->execute()) {
        // Redirect to kalender.php after successful insertion
        header('Location: kalender.php');
        exit; // Ensure script execution stops after redirection
    } else {
        echo "Viga: " . $stmt->error;
    }

    // Close the statement
    $stmt->close();
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

    <!-- Form to create a new appointment -->
    <h1>Loo uus broneering</h1>
    <form method="POST" action="lisa_uus_aeg.php">
    <label for="kliendi_nimi">Kliendi nimi:</label>
    <input type="text" id="kliendi_nimi" name="kliendi_nimi" required><br>

    <label for="reg_nr">Registreerimisnumber:</label>
    <input type="text" id="reg_nr" name="reg_nr"><br>

    <label for="broneeritud_aeg">Broneeritud kuupäev:</label>
    <input type="date" id="broneeritud_aeg" name="broneeritud_aeg" required onchange="fetchAvailableTimes()"><br>

    <label for="algus_aeg">Algusaeg:</label>
    <select id="algus_aeg" name="algus_aeg" required></select><br>

    <label for="lopp_aeg">Lõppaeg:</label>
    <select id="lopp_aeg" name="lopp_aeg" required></select><br>

    <label for="kirjeldus">Kirjeldus:</label>
    <textarea id="kirjeldus" name="kirjeldus"></textarea><br>
    
    <div class="formButton">
        <input type="submit" name="submit" value="Loo Broneering"></input>
    </div>
</form>


</body>
<footer>
    <p>Rõngu Auto OÜ</p>
    <p>Copyright &copy;
        <script>document.write(new Date().getFullYear())</script>
    </p>
</footer>
<script>
function fetchAvailableTimes() {
    var selectedDate = document.getElementById("broneeritud_aeg").value;

    if (selectedDate) {
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "saadaval_aegade_query.php?date=" + selectedDate, true);
        xhr.onload = function () {
            if (xhr.status === 200) {
                var times = JSON.parse(xhr.responseText);
                populateTimeSelects(times);
            }
        };
        xhr.send();
    }
}

function populateTimeSelects(times) {
    var algusAegSelect = document.getElementById("algus_aeg");
    var loppAegSelect = document.getElementById("lopp_aeg");

    // Clear current options
    algusAegSelect.innerHTML = '';
    loppAegSelect.innerHTML = '';

    // Populate new options
    times.forEach(function(time) {
        var option = document.createElement("option");
        option.value = time;
        option.text = time;
        algusAegSelect.appendChild(option);

        // Clone option for end time selection
        var optionEnd = option.cloneNode(true);
        loppAegSelect.appendChild(optionEnd);
    });
}
</script>

</html>