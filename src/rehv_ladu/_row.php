<?php
// Renders one <tr> for the Rehvi_Ladu table.
// Requires $row to be in the enclosing scope.
?>
<tr>
    <td><?php echo htmlspecialchars($row["RegNr"]); ?></td>
    <td><?php echo htmlspecialchars($row["Omanik"]); ?></td>
    <td><?php echo htmlspecialchars($row["Kogus"]); ?> tk</td>
    <td><?php echo htmlspecialchars($row["Hooaeg"]); ?></td>
    <td><?php echo htmlspecialchars($row["Date"]); ?></td>
    <td>
        <a href="edit_rehv_ladu.php?id=<?php echo $row["ladustamise_id"]; ?>">
            <i class="fa-solid fa-pen-to-square fa-lg muuda-icon"></i>
        </a>
    </td>
</tr>
