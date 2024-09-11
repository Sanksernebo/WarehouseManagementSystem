<?php
session_start();
// Check if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page
    header("Location: ../login/login.php");
    exit;
}
include_once '../db/laoseis.php';

// Function to generate the calendar
function generateCalendar($year, $month, $conn)
{
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $first_day_of_month = date('N', strtotime("$year-$month-01"));

    echo '<div class="calendar-month">';
    $day_names = ['E', 'T', 'K', 'N', 'R', 'L', 'P'];
    foreach ($day_names as $day_name) {
        echo '<div class="calendar-header">' . $day_name . '</div>';
    }

    // Empty boxes before the first day of the month
    for ($i = 1; $i < $first_day_of_month; $i++) {
        echo '<div class="calendar-empty"></div>';
    }

    // Days of the current month
    for ($day = 1; $day <= $days_in_month; $day++) {
        $current_date = "$year-$month-" . str_pad($day, 2, '0', STR_PAD_LEFT);

        $sql = "SELECT COUNT(*) AS total FROM Kalender WHERE broneeritud_aeg = '$current_date'";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $appointment_count = $row['total'];

        echo '<div class="calendar-day" onclick="openDay(\'' . $current_date . '\')">';
        echo $day;
        if ($appointment_count > 0) {
            if ($appointment_count == 1) {
                echo "<br><span class='appointment-count'>1 broneering</span>";
            } else {
                echo "<br><span class='appointment-count'>$appointment_count broneeringut</span>";
            }
        }
        echo '</div>';
    }

    echo '</div>';
}

// Get current year and month or use parameters from URL
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$selected_date = isset($_GET['date']) ? $_GET['date'] : null;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0">
    <meta charset="utf-8">
    <link rel="stylesheet" href="../../style.css">
    <script src="https://kit.fontawesome.com/4d1395116e.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="icon" type="image/x-icon" href="../img/cartehniklogo_svg.svg">
    <title>Laoseis</title>
    <script>
        function openDay(date) {
            window.location.href = 'kalender.php?date=' + date;
        }
    </script>
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
            <a href="../../src/kalender/kalender.php"></a>
        </div>
    </nav>

    <h1>Töögraafik</h1>

    <a href="lisa_uus_aeg.php" class="lisa-link">Lisa Broneering</a>

    <?php
    // Array of month names in Estonian
    $month_names = ['Jaanuar', 'Veebruar', 'Märts', 'Aprill', 'Mai', 'Juuni', 'Juuli', 'August', 'September', 'Oktoober', 'November', 'Detsember'];

    // Get the current month name
    $current_month_name = $month_names[$month - 1];
    ?>

    <!-- Navigation for months with current month and year display -->
    <div class="calendar-navigation">
        <div class="current-month"><?php echo $current_month_name . ' ' . $year; ?></div>
        <div class="navigation-buttons">
            <a
                href="kalender.php?year=<?php echo $year; ?>&month=<?php echo ($month == 1) ? 12 : $month - 1; ?>&year=<?php echo ($month == 1) ? $year - 1 : $year; ?>">Eelmine
                Kuu</a>
            <a
                href="kalender.php?year=<?php echo $year; ?>&month=<?php echo ($month == 12) ? 1 : $month + 1; ?>&year=<?php echo ($month == 12) ? $year + 1 : $year; ?>">Järgmine
                Kuu</a>
        </div>
    </div>

    <!-- Calendar for the month -->
    <?php generateCalendar($year, $month, $conn); ?>

    <!-- Daily view if a date is selected -->
    <?php if ($selected_date): ?>
        <div class="daily-view">
            <?php
            // Format the selected date to "dd.mm.yyyy"
            $formatted_date = date("d.m.Y", strtotime($selected_date));
            ?>
            <h2>Broneeringud <?php echo $formatted_date; ?> jaoks</h2>
            <?php
            // Prepare the SQL statement
            $stmt = $conn->prepare("SELECT Kalender.*, Login.kasutajanimi FROM Kalender 
                         LEFT JOIN Login ON Kalender.user_id = Login.ID 
                         WHERE broneeritud_aeg = ?");

            // Bind the parameters
            $stmt->bind_param("s", $selected_date);

            // Execute the statement
            $stmt->execute();

            // Get the result
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<p><strong>" . htmlspecialchars($row['kliendi_nimi']) . "</strong> (" . htmlspecialchars($row['reg_nr']) . ") - " . htmlspecialchars($row['algus_aeg']) . " kuni " . htmlspecialchars($row['lopp_aeg']) . "<br>" . htmlspecialchars($row['kirjeldus']) . "<br> Lisas kasutaja: " . htmlspecialchars($row['kasutajanimi']) . "</p>";
                }
            } else {
                echo "<p>Broneeringud puuduvad.</p>";
            }

            // Close the statement
            $stmt->close();
            ?>
        </div>
    <?php endif; ?>
    <footer>
        <p>Rõngu Auto OÜ</p>
        <p>Copyright &copy;
            <script>document.write(new Date().getFullYear())</script>
        </p>
    </footer>