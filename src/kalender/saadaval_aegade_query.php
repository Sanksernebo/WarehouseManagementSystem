<?php
include_once '../db/laoseis.php';

if (isset($_GET['date'])) {
    $selected_date = $_GET['date'];

    // Fetch all bookings for the selected date
    $stmt = $conn->prepare("SELECT algus_aeg, lopp_aeg FROM Kalender WHERE broneeritud_aeg = ?");
    $stmt->bind_param("s", $selected_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $booked_times = [];

    while ($row = $result->fetch_assoc()) {
        $booked_times[] = [
            'start' => $row['algus_aeg'],
            'end' => $row['lopp_aeg']
        ];
    }

    $stmt->close();

// Generate available times (from 09:00 to 18:00)
$available_times = [];
$start_time = strtotime("09:00");
$end_time = strtotime("18:00");

while ($start_time < $end_time) { // Ensure loop ends before 18:00
    $time_slot = date("H:i", $start_time);

    // Check if the time slot is booked
    $is_booked = false;
    foreach ($booked_times as $booking) {
        if ($time_slot >= $booking['start'] && $time_slot < $booking['end']) {
            $is_booked = true;
            break;
        }
    }

    // If the time slot is not booked, add it to available times
    if (!$is_booked) {
        $available_times[] = $time_slot;
    }

    // Increment by 1 hour
    $start_time = strtotime("+1 hour", $start_time);
}

    // Return available times as JSON
    echo json_encode($available_times);}