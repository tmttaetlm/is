<?php
    function getTexts($section,$property) {
        $ini_params = parse_ini_file('/home/developer/Code/PHP/is/public/texts/'.$_COOKIE["lang"].'-lang.ini',true);
        return $ini_params[$section][$property];
    }
?>

<table class="results" id="visitResults" data-focus="<?php echo $data['data'][0]['focus'] ?>">
    <caption><?php
        switch ($data['data'][0]['focus']) {
            case 'planning':
                echo getTexts('ATTESTATION_TABLE_HEADERS', 'planning_caption');
                break;
            case 'teaching':
                echo getTexts('ATTESTATION_TABLE_HEADERS', 'teaching_caption');
                break;
            case 'evaluating':
                echo getTexts('ATTESTATION_TABLE_HEADERS', 'evaluating_caption');
                break;
            case 'complex':
                echo getTexts('ATTESTATION_TABLE_HEADERS', 'complex_caption');
                break;
        }
    ?></caption>
    <tr>
        <th colspan="2" style="width: 15%;"><?php echo getTexts('TABLE_HEADERS', 'date')?></th>
        <th colspan="2" style="width: 15%;"><?php echo getTexts('TABLE_HEADERS', 'grade')?></th>
        <th colspan="2" style="width: 25%;"><?php echo getTexts('TABLE_HEADERS', 'subject')?></th>
        <th colspan="2" style="width: 45%;"><?php echo getTexts('ATTESTATION_TABLE_HEADERS', 'theme')?></th>
    </tr>
    <tr>
        <td colspan="2" style="text-align: center;"><?php echo $data['data'][0]['visitDate'] ?></td>
        <td colspan="2" style="text-align: center;"><?php echo $data['data'][0]['grade'] ?></td>
        <td colspan="2" style="text-align: center;"><?php echo $data['data'][0]['lessonName'] ?></td>
        <td colspan="2" style="text-align: center;"><?php echo $data['data'][0]['theme'] ?></td>
    </tr>
    <tr>
        <th colspan="4"><?php echo getTexts('ATTESTATION_TABLE_HEADERS', 'whoVisited')?></th>
        <td colspan="4" style="font-size: 18px; text-align: center;"><?php echo $data['data'][0]['whoVisited'] ?></td>
    </tr>
    <tr>
        <th colspan="4"><?php echo getTexts('ATTESTATION_TABLE_HEADERS', 'whoWasVisited')?></th>
        <td colspan="4" style="font-size: 18px; text-align: center;"><?php echo $data['data'][0]['whoWasVisited'] ?></td>
    </tr>
    <tr><th colspan="8"><?php echo getTexts('TABLE_HEADERS', 'purpose')?></th></tr>
    <tr><td colspan="8" style="text-align: center;"><?php echo $data['data'][0]['purpose'] ?></td></tr>
    <tr><th colspan="8"><?php echo getTexts('TABLE_HEADERS', 'feedback')?></th></tr>
    <tr>
        <th style="width: 10%;">№</th>
        <th colspan="7" style="font-size: 18px; text-align: left;">
            <?php
                switch ($data['data'][0]['focus']) {
                    case 'planning':
                        echo getTexts('ATTESTATION_TABLE_HEADERS', 'planning_header');
                        break;
                    case 'teaching':
                        echo getTexts('ATTESTATION_TABLE_HEADERS', 'teaching_header');
                        break;
                    case 'evaluating':
                        echo getTexts('ATTESTATION_TABLE_HEADERS', 'evaluating_header');
                        break;
                    case 'complex':
                        echo getTexts('ATTESTATION_TABLE_HEADERS', 'complex_header');
                        break;
                }
            ?>
        </th>
    </tr>
    <?php $k = 0; $j = 0; for ($i = 0; $i < count($data['def']); $i++):
        if ($data['def'][$i]['criteria'] != '') { ?>
        <tr id="<?php echo "row".$data['def'][$i]['num'] ?>">
            <?php 
                if (!is_null($data['def'][$i]['rs'])) {
                    $k++;
                    print("<th rowspan=".$data['def'][$i]['rs']." id='criteria_numbers'>".($k)."</th>");
                }
            ?>
            <?php if ($data['def'][$i]['markable']) { ?>
                <td colspan="6" id="attestation_criterias"><?php echo getTexts('ATTESTATION_CRITERIAS', $data['def'][$i]['criteria']); ?></td>
                <td id="criteria_check" style="width: 10%;">
                    <label class="<?php if (substr($data['data'][0]['evaluates'],$j,1) == '1') { echo 'fas fa-check'; } ?>"></label>
                </td>
            <?php $j++;} else { ?>
                <td colspan="7" id="attestation_criterias"><?php echo getTexts('ATTESTATION_CRITERIAS', $data['def'][$i]['criteria']); ?></td>
            <?php }; ?>
        </tr>
    <?php }; endfor;?>
    <tr><th colspan="8"><?php echo getTexts('ATTESTATION_TABLE_HEADERS', 'lesson_feedback')?></th></tr>
    <tr> <td colspan="8"><?php echo $data['data'][0]['lesson_review'] ?></td> </tr>
    <tr><th colspan="8"><?php echo getTexts('TABLE_HEADERS', 'purpose_feedback')?></th></tr>
    <tr><td colspan="8"><?php echo $data['data'][0]['purpose_review'] ?> </td></tr>
</table>
<div class="visitBut">
    <button name="confirmAResults" id="presenterSide"
    <?php substr($data['data'][0]['confirmations'],0,1) == "0" || substr($data['data'][0]['confirmations'],1,1) == "1" ? print("disabled") : print('style="background-color: #3d93cc !important;"'); ?>>
        <?php echo getTexts('INTERFACE', 'confirm')?>
    </button>
    <button name="closePattern"><?php echo getTexts('INTERFACE', 'close')?></button>
</div>