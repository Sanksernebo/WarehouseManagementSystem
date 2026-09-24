<?php
// Renders one <tr> for the Tehtud_tood table.
// Requires $row to be in the enclosing scope.
?>
<tr>
    <td>
        <?php echo htmlspecialchars($row["RegNr"]); ?>
        <a href="../../src/pdf_generaator/pdf_koostamine.php?too_id=<?php echo $row['too_id']; ?>" target="_blank">
            <i class="fa-solid fa-file-pdf fa-lg pdf-icon"></i>
        </a>
    </td>
    <td><?php echo htmlspecialchars($row["FormattedDate"]); ?></td>
    <td><?php echo htmlspecialchars($row["Odomeeter"]); ?> km</td>
    <td><?php echo htmlspecialchars($row["Tehtud_tood"]); ?></td>
    <td>
        <a href="../../src/tehtud_tood/edit-work-process.php?too_id=<?php echo $row["too_id"]; ?>">
            <i class="fa-solid fa-pen-to-square fa-lg muuda-icon"></i>
        </a>
    </td>
</tr>
