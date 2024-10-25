<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}
include_once '../db/laoseis.php';

function generateCalendar($year, $month, $view, $conn, $selected_date = null)
{
    if ($view == 'monthly') {
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $first_day_of_month = date('N', strtotime("$year-$month-01"));

        echo '<div class="calendar-month">';
        $day_names = ['E', 'T', 'K', 'N', 'R', 'L', 'P'];
        foreach ($day_names as $day_name) {
            echo '<div class="calendar-header">' . $day_name . '</div>';
        }

        for ($i = 1; $i < $first_day_of_month; $i++) {
            echo '<div class="calendar-empty"></div>';
        }

        $current_date_today = date('Y-m-d');

        for ($day = 1; $day <= $days_in_month; $day++) {
            $current_date = "$year-$month-" . str_pad($day, 2, '0', STR_PAD_LEFT);

            $sql = "SELECT algus_aeg, lopp_aeg FROM Kalender WHERE broneeritud_aeg = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $current_date);
            $stmt->execute();
            $result = $stmt->get_result();

            $time_slots = array_fill(9, 9, false);

            while ($row = $result->fetch_assoc()) {
                $start_hour = (int) explode(":", $row['algus_aeg'])[0];
                $end_hour = (int) explode(":", $row['lopp_aeg'])[0];

                for ($hour = $start_hour; $hour < $end_hour; $hour++) {
                    if ($hour >= 9 && $hour < 18) {
                        $time_slots[$hour] = true;
                    }
                }
            }

            $bar_html = '<div class="time-bar">';
            foreach ($time_slots as $hour => $is_booked) {
                $bar_html .= '<div class="time-slot ' . ($is_booked ? 'booked' : 'available') . '"></div>';
            }
            $bar_html .= '</div>';

            $day_class = ($current_date == $current_date_today) ? 'calendar-day current-day' : 'calendar-day';
            echo '<div class="' . $day_class . '" onclick="openDay(\'' . $current_date . '\')">';
            echo $day;
            echo $bar_html;
            echo '</div>';
        }

        echo '</div>';
    }
}

$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$selected_date = isset($_GET['date']) ? $_GET['date'] : null;
$view = 'monthly';
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
                <button class="dropbtn">Rehvid <i class="fa fa-caret-down"></i></button>
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

    <h1>Töögraafik</h1>

    <a href="lisa_uus_aeg.php" class="lisa-link">Lisa Broneering</a>

    <?php
    $month_names = ['Jaanuar', 'Veebruar', 'Märts', 'Aprill', 'Mai', 'Juuni', 'Juuli', 'August', 'September', 'Oktoober', 'November', 'Detsember'];
    $current_month_name = $month_names[$month - 1];
    ?>

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

    <?php generateCalendar($year, $month, $view, $conn, $selected_date); ?>

    <?php if ($selected_date): ?>
        <div class="daily-view">
            <?php
            $formatted_date = date("d.m.Y", strtotime($selected_date));
            ?>
            <h2>Broneeringud <?php echo $formatted_date; ?> jaoks</h2>
            <table class="daily-view-table">
                <tr>
                    <th>Aeg</th>
                    <th>Broneeringu info</th>
                </tr>
                <?php
                $start_time = 9;
                $end_time = 18;

                $stmt = $conn->prepare("SELECT Kalender.*, Login.kasutajanimi FROM Kalender LEFT JOIN Login ON Kalender.user_id = Login.ID WHERE broneeritud_aeg = ? ORDER BY algus_aeg");
                $stmt->bind_param("s", $selected_date);
                $stmt->execute();
                $result = $stmt->get_result();

                $appointments = [];
                while ($row = $result->fetch_assoc()) {
                    $start_hour = (int) explode(":", $row['algus_aeg'])[0];
                    $end_hour = (int) explode(":", $row['lopp_aeg'])[0];
                    for ($hour = $start_hour; $hour < $end_hour; $hour++) {
                        $appointments[$hour] = $row;
                    }
                }

                $stmt->close();

                $hour = $start_time;
                while ($hour < $end_time) {
                    if (isset($appointments[$hour])) {
                        $appointment = $appointments[$hour];
                        $kliendi_nimi = htmlspecialchars($appointment['kliendi_nimi']);
                        $reg_nr = htmlspecialchars($appointment['reg_nr']);
                        $algus_aeg = htmlspecialchars($appointment['algus_aeg']);
                        $lopp_aeg = htmlspecialchars($appointment['lopp_aeg']);
                        $kirjeldus = htmlspecialchars($appointment['kirjeldus']);
                        $kasutajanimi = htmlspecialchars($appointment['kasutajanimi']);
                        $kalendri_id = htmlspecialchars($appointment['kalendri_id']);

                        $start_hour = (int) explode(":", $appointment['algus_aeg'])[0];
                        $end_hour = (int) explode(":", $appointment['lopp_aeg'])[0];
                        $duration = $end_hour - $start_hour;

                        echo "<tr>";
                        echo "<td>$algus_aeg kuni $lopp_aeg</td>";
                        echo "<td><strong>$kliendi_nimi</strong> ($reg_nr)<br>$kirjeldus<br>Lisanud: $kasutajanimi<br><a href=\"muuda_aega.php?kalendri_id=$kalendri_id\"> <i class='fa-solid fa-pen-to-square fa-lg muuda-icon'></i></a> | <a href=\"kustuta_aeg.php?kalendri_id=$kalendri_id\" \"><i class='fa-solid fa-trash fa-lg kustuta-icon'></i></a></td>";
                        echo "</tr>";
                        $hour += $duration;
                    } else {
                        // Output an empty row for times without an appointment
                        $current_time = sprintf('%02d:00', $hour);
                        echo "<tr>";
                        echo "<td>$current_time kuni " . sprintf('%02d:00', $hour + 1) . "</td>";
                        echo "<td>Vaba aeg</td>";
                        echo "</tr>";
                        // Move to the next hour
                        $hour++;
                    }
                }
                ?>
            </table>
        </div>
    <?php endif; ?>

    <footer>
        <p>Rõngu Auto OÜ</p>
        <p>&copy;
            <script>document.write(new Date().getFullYear())</script>
        </p>
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