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

        // Days of the current month
        for ($day = 1; $day <= $days_in_month; $day++) {
            $current_date = "$year-$month-" . str_pad($day, 2, '0', STR_PAD_LEFT);

            // Query to get the number of appointments for the whole day
            $sql = "SELECT COUNT(*) AS total FROM Kalender WHERE broneeritud_aeg = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $current_date);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $appointment_count = $row['total'];

            // Determine if all slots between 09:00 and 18:00 are filled
            $time_slots = [];
            for ($hour = 9; $hour < 18; $hour++) {
                $time_slots[] = sprintf('%02d:00:00', $hour);
            }

            $all_slots_filled = true;
            foreach ($time_slots as $slot) {
                $sql = "SELECT COUNT(*) AS slot_count 
            FROM Kalender 
            WHERE broneeritud_aeg = ? 
            AND algus_aeg <= ? 
            AND lopp_aeg >= ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", $current_date, $slot, $slot);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $slot_count = $row['slot_count'];

                if ($slot_count == 0) {
                    $all_slots_filled = false;
                    break; // Exit early if any slot is not filled
                }
            }

            // Determine border class based on slots filled
            $border_class = ($all_slots_filled >= 9) ? 'red-border' : (($appointment_count > 0) ? 'green-border' : '');

            echo '<div class="calendar-day ' . $border_class . '" style="background-color: ' . ($border_class == 'red-border' ? 'white' : '#89CEEE') . ';" onclick="openDay(\'' . $current_date . '\')">';
            echo $day;
            if ($appointment_count > 0) {
                echo "<br><span class='appointment-count'>$appointment_count broneeringut</span>";
            }
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
            <?php
            $stmt = $conn->prepare("SELECT Kalender.*, Login.kasutajanimi FROM Kalender 
                         LEFT JOIN Login ON Kalender.user_id = Login.ID 
                         WHERE broneeritud_aeg = ? ORDER BY algus_aeg");
            $stmt->bind_param("s", $selected_date);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<p><strong>" . htmlspecialchars($row['kliendi_nimi']) . "</strong> (" . htmlspecialchars($row['reg_nr']) . ") - " . htmlspecialchars($row['algus_aeg']) . " kuni " . htmlspecialchars($row['lopp_aeg']) . "<a href='muuda_aega.php?kalendri_id=" . htmlspecialchars($row['kalendri_id']) . "'>
                    <i class='fa-solid fa-pen-to-square fa-lg muuda-icon'></i>
                    </a>" . "<br>" . htmlspecialchars($row['kirjeldus']) . "<br> Lisas kasutaja: " . htmlspecialchars($row['kasutajanimi']) . "</p>";
                }
            } else {
                echo "<p>Broneeringud puuduvad.</p>";
            }

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