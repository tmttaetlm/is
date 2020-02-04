<table class="<?php echo $data['class']; ?>">
    <caption><?php echo $data['caption']; ?></caption>
    <tr>
    <?php foreach ($data['columns'] as $column=>$header):?>
        <th><?php echo $header;?></th>
    <?php endforeach;?>
    </tr>
    <?php foreach ($data['tableData'] as $row):?>
        <tr data-row-id="<?php echo $row['id']?>">
            <?php foreach ($data['columns'] as $column=>$header):?>
                <td><?php echo $row[$column]; ?></td>
            <?php endforeach; ?>
        </tr>
    <?php endforeach;?>
</table>