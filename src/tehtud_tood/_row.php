<?php
// Renders one <tr> for the Tehtud_tood table.
// Requires $row to be in the enclosing scope.
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/_arve.php';
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
    <td class="tegevus">
        <?php echo arve_ikoon($row["too_id"], $row["Arve_staatus"]); ?>
        <a href="../../src/tehtud_tood/edit-work-process.php?too_id=<?php echo $row["too_id"]; ?>">
            <i class="fa-solid fa-pen-to-square fa-lg muuda-icon"></i>
        </a>
    </td>
</tr>
