<?php
// Renders one <tr> for the Rehvi_myyk table.
// Requires $row to be in the enclosing scope.
?>
<tr>
    <td><?php echo htmlspecialchars($row["RegNr"]); ?></td>
    <td><?php echo htmlspecialchars($row["Moot"]); ?></td>
    <td><?php echo htmlspecialchars($row["Tootja"]); ?></td>
    <td><?php echo htmlspecialchars($row["Kogus"]); ?> tk</td>
    <td><?php echo htmlspecialchars($row["Hooaeg"]); ?></td>
    <td><?php echo htmlspecialchars($row["Tarnija"]); ?></td>
    <td><?php echo htmlspecialchars($row["FormattedDate"]); ?></td>
</tr>
