<?php
session_start();
// Check if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page
    header("Location: ../login/login.php");
    exit;
}
include_once '../db/laoseis.php';

// Function to generate the calendar (Monthly or Weekly)
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

        // Empty boxes before the first day of the month
        for ($i = 1; $i < $first_day_of_month; $i++) {
            echo '<div class="calendar-empty"></div>';
        }
        // Get the current date
        $current_date_today = date('Y-m-d');

        // Days of the current month
        for ($day = 1; $day <= $days_in_month; $day++) {
            $current_date = "$year-$month-" . str_pad($day, 2, '0', STR_PAD_LEFT);

            // Query to get the number of appointments for the whole day
            $sql = "SELECT algus_aeg, lopp_aeg FROM Kalender WHERE broneeritud_aeg = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $current_date);
            $stmt->execute();
            $result = $stmt->get_result();

            // Create a time map for 09:00 - 18:00 (9 hours, each hour as a slot)
            $time_slots = array_fill(9, 9, false); // false means available, true means booked

            // Mark booked slots
            while ($row = $result->fetch_assoc()) {
                $start_hour = (int) explode(":", $row['algus_aeg'])[0];
                $end_hour = (int) explode(":", $row['lopp_aeg'])[0];

                for ($hour = $start_hour; $hour < $end_hour; $hour++) {
                    if ($hour >= 9 && $hour < 18) {
                        $time_slots[$hour] = true; // Mark the slot as booked
                    }
                }
            }

            // Generate the visual bar for the booked times
            $bar_html = '<div class="time-bar">';
            foreach ($time_slots as $hour => $is_booked) {
                $bar_html .= '<div class="time-slot ' . ($is_booked ? 'booked' : 'available') . '"></div>';
            }
            $bar_html .= '</div>';

            // Highlight the current day by adding a 'current-day' class
            $day_class = ($current_date == $current_date_today) ? 'calendar-day current-day' : 'calendar-day';

            // Display the day with the time bar
            echo '<div class="' . $day_class . '" onclick="openDay(\'' . $current_date . '\')">';
            echo $day;
            echo $bar_html; // Append the time bar below the date
            echo '</div>';
        }

        echo '</div>';
    } elseif ($view == 'weekly') {
        // Array for Estonian day names (Monday to Sunday)
        $day_names = ['Esmaspäev', 'Teisipäev', 'Kolmapäev', 'Neljapäev', 'Reede', 'Laupäev', 'Pühapäev'];

        // Default selected date to today if not provided
        $selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

        // Get the start (Monday) and end (Sunday) of the week for the selected date
        $week_start = date("Y-m-d", strtotime("monday this week", strtotime($selected_date)));
        $week_end = date("Y-m-d", strtotime("sunday this week", strtotime($selected_date)));

        // SQL query to get all appointments for the week including user information
        $sql = "SELECT Kalender.*, Login.kasutajanimi 
                FROM Kalender 
                LEFT JOIN Login ON Kalender.user_id = Login.ID 
                WHERE broneeritud_aeg BETWEEN ? AND ?
                ORDER BY algus_aeg";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $week_start, $week_end);
        $stmt->execute();
        $result = $stmt->get_result();

        // Create an array to store appointments for each day
        $appointments_by_day = [];
        while ($row = $result->fetch_assoc()) {
            $appointments_by_day[$row['broneeritud_aeg']][] = $row;
        }

        // Close the statement
        $stmt->close();

        // Loop through each day of the week (Monday to Sunday)
        for ($i = 0; $i < 7; $i++) {
            $current_day = date("Y-m-d", strtotime("$week_start +$i days"));
            $formatted_date = date("d.m.Y", strtotime($current_day));

            // Display the day name and date
            echo "<h3>" . $day_names[$i] . " - " . $formatted_date . "</h3>";

            // Check if there are appointments for this day
            if (isset($appointments_by_day[$current_day])) {
                foreach ($appointments_by_day[$current_day] as $appointment) {
                    // Ensure 'kasutajanimi' is available in $appointment
                    $kasutajanimi = $appointment['kasutajanimi'] ?? 'Tundmatu kasutaja';
                    echo "<p>
                <strong>" . htmlspecialchars($appointment['kliendi_nimi'] ?? '') . "</strong>
                (" . htmlspecialchars($appointment['reg_nr'] ?? '') . ") - " . htmlspecialchars($appointment['algus_aeg'] ?? '') . " kuni " . htmlspecialchars($appointment['lopp_aeg'] ?? '') . "
                <a href='muuda_aega.php?kalendri_id=" . htmlspecialchars($appointment['kalendri_id'] ?? '') . "'>
                    <i class='fa-solid fa-pen-to-square fa-lg muuda-icon'></i>
                </a>
                <br>" . htmlspecialchars($appointment['kirjeldus'] ?? '') . "<br> Lisas kasutaja: " . htmlspecialchars($kasutajanimi) . "
              </p>";
                }
            } else {
                echo "<p>Broneeringud puuduvad.</p>";
            }
        }
    }

}

// Get current year and month or use parameters from URL
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$selected_date = isset($_GET['date']) ? $_GET['date'] : null;

// Default to monthly view if no view is set
$view = isset($_GET['view']) ? $_GET['view'] : 'monthly';

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
            <?php if ($view == 'monthly'): ?>
                <a
                    href="kalender.php?year=<?php echo $year; ?>&month=<?php echo ($month == 1) ? 12 : $month - 1; ?>&year=<?php echo ($month == 1) ? $year - 1 : $year; ?>">Eelmine
                    Kuu</a>
                <a
                    href="kalender.php?year=<?php echo $year; ?>&month=<?php echo ($month == 12) ? 1 : $month + 1; ?>&year=<?php echo ($month == 12) ? $year + 1 : $year; ?>">Järgmine
                    Kuu</a>
            <?php elseif ($view == 'weekly'): ?>
                <a href="kalender.php?view=weekly&date=<?php echo date('Y-m-d', strtotime("$selected_date -7 days")); ?>">Eelmine
                    Nädal</a>
                <a href="kalender.php?view=weekly&date=<?php echo date('Y-m-d', strtotime("$selected_date +7 days")); ?>">Järgmine
                    Nädal</a>
            <?php endif; ?>

            <!-- View switcher button -->
            <button class="view-switcher-button" onclick="toggleView()">
                <?php echo $view == 'monthly' ? 'Vaata nädala lõikes' : 'Vaata kuu lõikes'; ?>
            </button>
        </div>
    </div>

    <script>
        function changeView(view) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('view', view);
            window.location.search = urlParams.toString();
        }
    </script>


    <!-- Generate the calendar based on the view -->
    <?php generateCalendar($year, $month, $view, $conn, $selected_date); ?>

    <!-- Daily view if a date is selected -->
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
                // Define the time range from 09:00 to 18:00
                $start_time = 9;
                $end_time = 18;

                // Fetch all appointments for the selected day
                $stmt = $conn->prepare("SELECT Kalender.*, Login.kasutajanimi FROM Kalender 
                                 LEFT JOIN Login ON Kalender.user_id = Login.ID 
                                 WHERE broneeritud_aeg = ? ORDER BY algus_aeg");
                $stmt->bind_param("s", $selected_date);
                $stmt->execute();
                $result = $stmt->get_result();

                // Store appointments in an array, indexed by time slots
                $appointments = [];
                while ($row = $result->fetch_assoc()) {
                    $start_hour = (int) explode(":", $row['algus_aeg'])[0];
                    $end_hour = (int) explode(":", $row['lopp_aeg'])[0];
                    for ($hour = $start_hour; $hour < $end_hour; $hour++) {
                        $appointments[$hour] = $row;
                    }
                }

                $stmt->close();

                // Loop through each time slot between 09:00 and 18:00
                $hour = $start_time;
                while ($hour < $end_time) {
                    // Check if there's an appointment for the current time slot
                    if (isset($appointments[$hour])) {
                        $appointment = $appointments[$hour];
                        $kliendi_nimi = htmlspecialchars($appointment['kliendi_nimi']);
                        $reg_nr = htmlspecialchars($appointment['reg_nr']);
                        $algus_aeg = htmlspecialchars($appointment['algus_aeg']);
                        $lopp_aeg = htmlspecialchars($appointment['lopp_aeg']);
                        $kirjeldus = htmlspecialchars($appointment['kirjeldus']);
                        $kasutajanimi = htmlspecialchars($appointment['kasutajanimi']);
                        $kalendri_id = htmlspecialchars($appointment['kalendri_id']);

                        // Calculate the duration of the appointment
                        $start_hour = (int) explode(":", $appointment['algus_aeg'])[0];
                        $end_hour = (int) explode(":", $appointment['lopp_aeg'])[0];
                        $duration = $end_hour - $start_hour;

                        // Output the appointment row spanning the duration
                        echo "<tr>";
                        echo "<td>$algus_aeg kuni $lopp_aeg</td>";
                        echo "<td><strong>$kliendi_nimi</strong> ($reg_nr)<br>$kirjeldus<br>Lisas kasutaja: $kasutajanimi</td>";
                        echo "</tr>";

                        // Skip the hours covered by this appointment
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
        <p>Copyright &copy;
            <script>document.write(new Date().getFullYear())</script>
        </p>
    </footer>
    <script>
        function toggleView() {
            const currentView = "<?php echo $view; ?>";
            const urlParams = new URLSearchParams(window.location.search);

            if (currentView === 'monthly') {
                urlParams.set('view', 'weekly');
            } else {
                urlParams.set('view', 'monthly');
            }

            window.location.search = urlParams.toString();
        }
    </script>
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
</body>

</html>