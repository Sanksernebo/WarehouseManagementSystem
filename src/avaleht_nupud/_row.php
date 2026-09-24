<?php
// Renders one <tr> for the Ladu (warehouse stock) table.
// Requires $row to be in the enclosing scope.
?>
<tr>
    <td><?php echo htmlspecialchars($row["Tootekood"]); ?></td>
    <td><?php echo htmlspecialchars($row["Nimetus"]); ?></td>
    <td><?php echo htmlspecialchars($row["Kogus"]); ?></td>
    <td><?php echo htmlspecialchars($row["Sisseost"]); ?></td>
    <td><?php echo htmlspecialchars($row["Jaehind"]); ?></td>
    <td><?php echo htmlspecialchars($row["Ost"]); ?></td>
    <td><?php echo htmlspecialchars($row["Olek"]); ?></td>
    <td>
        <a href="src/avaleht_nupud/update-process.php?ID=<?php echo $row["toote_id"]; ?>">
            <i class="fa-solid fa-pen-to-square fa-lg muuda-icon"></i>
        </a>
        <a href="src/avaleht_nupud/delete-process.php?ID=<?php echo $row["toote_id"]; ?>">
            <i class="fa-solid fa-trash fa-lg kustuta-icon"></i>
        </a>
    </td>
</tr>
