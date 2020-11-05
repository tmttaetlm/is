<?php
    function getTexts($section,$property) {
        $ini_params = parse_ini_file('/home/developer/Code/PHP/is/public/texts/'.$_COOKIE["lang"].'-lang.ini',true);
        return $ini_params[$section][$property];
    }
?>
<table class="results">
<caption><?php echo getTexts('TABLE_HEADERS', 'caption')?></caption>
    <tr>
        <th style="width: 20%;"><?php echo getTexts('TABLE_HEADERS', 'date')?></th>
        <th style="width: 20%;"><?php echo getTexts('TABLE_HEADERS', 'grade')?></th>
        <th style="width: 20%;"><?php echo getTexts('TABLE_HEADERS', 'subject')?></th>
        <th style="width: 40%;"><?php echo getTexts('TABLE_HEADERS', 'theme')?></th>
    </tr>
    <tr>
        <td><?php print(date("d.m.Y", strtotime($data['data'][0]['visitDate']))) ?></td>
        <td><?php print($data['data'][0]['grade']) ?></td>
        <td><?php print($data['data'][0]['lessonName']) ?></td>
        <td><?php print($data['data'][0]['theme']) ?></td>
    </tr>
    <tr>
        <th colspan="2"><?php echo getTexts('TABLE_HEADERS', 'whoWasVisited') ?></th>
        <td colspan="2" style="font-size: 18px; text-align: center;"><?php print($data['data'][0]['whoWasVisited']) ?></td>
    </tr>
    <tr>
        <th colspan="2"><?php echo getTexts('TABLE_HEADERS', 'whoVisited')?></th>
        <td colspan="2" style="font-size: 18px; text-align: center;"><?php print($data['data'][0]['whoVisited']) ?></td>
    </tr>
    <tr><th colspan="4"><?php echo getTexts('TABLE_HEADERS', 'purpose')?></th></tr>
    <tr><td colspan="4"><?php print($data['data'][0]['purpose']) ?></td></tr>
    <tr><th colspan="4"><?php echo getTexts('TABLE_HEADERS', 'feedback')?></th></tr>
    <?php for ($i = 0; $i <= 15; $i++):?>
        <tr id="<?php echo "row".$data['def'][$i]['num'] ?>">
            <?php !is_null($data['def'][$i]['d1']) ? print("<th rowspan=".$data['def'][$i]['rs'].">".getTexts('CRITERIAS',$data['def'][$i]['d1'])."</th>") : print("") ?>
            <td><?php echo getTexts('CRITERIAS',$data['def'][$i]['d2']) ?></td>
            <td style="text-align: center;">
                <?php
                    substr($data['data'][0]['evaluates'],$i,1) != '0' ? print(substr($data['data'][0]['evaluates'],$i,1)) : print("");
                ?>
            </td>
            <td colspan="2">
                <?php
                    switch (substr($data['data'][0]['evaluates'],$i,1)) {
                        case '1':
                            echo getTexts('DESCRIPTIONS',$data['crit'][$i]['one']);
                            break;
                        case '2':
                            echo getTexts('DESCRIPTIONS',$data['crit'][$i]['two']);
                            break;
                        case '3':
                            echo getTexts('DESCRIPTIONS',$data['crit'][$i]['three']);
                            break;
                        case '4':
                            echo getTexts('DESCRIPTIONS',$data['crit'][$i]['four']);
                            break;
                        case '5':
                            echo getTexts('DESCRIPTIONS',$data['crit'][$i]['five']);
                            break;
                        default:
                            print("");
                            break;
                    }
                ?>
            </td>
        </tr>
    <?php endfor;?>
    <tr><th colspan="4"><?php echo getTexts('TABLE_HEADERS', 'recommendation')?></th></tr>
    <tr><td colspan="4"><?php print($data['data'][0]['recommendation']) ?></td></tr>
    <tr><th colspan="4"><?php echo getTexts('TABLE_HEADERS', 'purpose_feedback')?></th></tr>
    <tr><td colspan="4"><?php print($data['data'][0]['purpose_review']) ?></td></tr>
</table>
<div class="visitBut">
    <button name="confirmResults" id="presenterSide"
        <?php substr($data['data'][0]['confirmations'],0,1) == "0" || substr($data['data'][0]['confirmations'],1,1) == "1" ? print("disabled") : print('style="background-color: #3d93cc !important;"'); ?>>
        <?php echo getTexts('INTERFACE', 'confirm')?>
    </button>
    <button name="closePattern"><?php echo getTexts('INTERFACE', 'close')?></button>
</div>