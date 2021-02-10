<table class="<?php echo $data['class'];?>">
    <caption><?php echo $data['caption']; ?></caption>
    <tr>
    <?php foreach ($data['columns'] as $column=>$header):?>
        <th><?php echo $header;?></th>
    <?php endforeach;?>
    </tr>
    <?php foreach ($data['tableData'] as $row):?>
        <tr <?php array_key_exists('id', $row) ? print('data-row-id="'.$row['id'].'"') : print("");
                  array_key_exists('focus', $row) ? print('data-focus="'.$row['focus'].'"') : print("");
                  array_key_exists('period', $row) ? print('data-period="'.$row['period'].'"') : print("");
                  array_key_exists('class', $row) ? print('class="'.$row['class'].'"') : print("") ?>>
            <?php foreach ($data['columns'] as $column=>$header):?>
                <td><?php echo $row[$column]; ?></td>
            <?php endforeach; ?>
        </tr>
    <?php endforeach;?>
</table>