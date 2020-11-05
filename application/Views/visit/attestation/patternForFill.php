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
        <td colspan="2">
            <input type="date" class="visitInputs" id="visitDate" name="visitDate" value="<?php print($data['data'][0]['visitDate']) ?>" 
            <?php substr($data['data'][0]['confirmations'],0,1) == "1" ? print("disabled") : print("") ?>>
        </td>
        <td colspan="2" style="text-align: center;">
            <select id="visitGrade" name="visitGrade" style="width: 55px;" 
            <?php substr($data['data'][0]['confirmations'],0,1) == "1" ? print("disabled") : print("") ?>>
                <option selected></option>
                <?php for ($i = 7; $i <= 12; $i++):?>
                    <option <?php substr($data['data'][0]['grade'],0, strlen($data['data'][0]['grade'])-1) == $i ? print('selected') : print('') ?>>
                        <?php echo $i ?>
                    </option>    
                <?php endfor;?>
            </select>
            <select id="visitLitera" name="visitLitera" style="width: 55px;" 
            <?php substr($data['data'][0]['confirmations'],0,1) == "1" ? print("disabled") : print("") ?>>
                <option selected></option>
                <?php for ($i = 0; $i <= count($data['grade'])-1; $i++):?>
                    <option <?php substr($data['data'][0]['grade'],-1) == $data['grade'][$i]['Litera'] ? print('selected') : print('') ?>>
                        <?php echo $data['grade'][$i]['Litera'] ?>
                    </option>
                <?php endfor;?>
            </select>
        </td>
        <td colspan="2">
            <select id="visitSubject" name="visitSubject" class="visitSelects" 
            <?php substr($data['data'][0]['confirmations'],0,1) == "1" ? print("disabled") : print("") ?>>
                <option selected></option>
                <?php for ($i = 0; $i <= count($data['subj'])-1; $i++):?>
                    <option <?php $data['data'][0]['lessonName'] == $data['subj'][$i]['subjects'] ? print('selected') : print('') ?>>
                        <?php echo $data['subj'][$i]['subjects'] ?>
                    </option>
                <?php endfor;?>
            </select>
        </td>
        <td colspan="2">
            <input type="text" class="visitInputs" id="visitTopic" name="visitTopic" value="<?php print($data['data'][0]['theme']) ?>" 
            <?php substr($data['data'][0]['confirmations'],0,1) == "1" ? print("disabled") : print("") ?>>
        </td>
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
    <tr><td colspan="8"><?php echo $data['data'][0]['purpose'] ?></td></tr>
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
                    <input type="checkbox" class="custom-checkbox-input" id="<?php echo "chk".$data['def'][$i]['num'] ?>"
                    <?php if (substr($data['data'][0]['evaluates'],$j,1) == '1') { echo 'checked'; } ?>
                    <?php substr($data['data'][0]['confirmations'],0,1) == "1" ? print("disabled") : print("") ?>>
                    <label for="<?php echo "chk".$data['def'][$i]['num'] ?>" class="custom-checkbox-label"></label>
                </td>
            <?php $j++;} else { ?>
                <td colspan="7" id="attestation_criterias"><?php echo getTexts('ATTESTATION_CRITERIAS', $data['def'][$i]['criteria']); ?></td>
            <?php }; ?>
        </tr>
    <?php }; endfor;?>
    <tr><th colspan="8"><?php echo getTexts('ATTESTATION_TABLE_HEADERS', 'lesson_feedback')?></th></tr>
    <tr>
        <td colspan="8">
            <textarea rows="5" maxlength="1000" id="visitLessonReview" name="visitLessonReview" class="big_textarea"
            <?php substr($data['data'][0]['confirmations'],0,1) == "1" ? print("disabled") : print("") ?>><?php print($data['data'][0]['lesson_review']) ?></textarea>
        </td>
    </tr>
    <tr><th colspan="8"><?php echo getTexts('TABLE_HEADERS', 'purpose_feedback')?></th></tr>
    <tr>
        <td colspan="8">
            <textarea rows="5" maxlength="500" id="visitPurposeReview" name="visitPurposeReview" class="big_textarea"
            <?php substr($data['data'][0]['confirmations'],0,1) == "1" ? print("disabled") : print("") ?>><?php print($data['data'][0]['purpose_review']) ?></textarea>
        </td>
    </tr>
</table>
<div class="visitBut">
    <button name="saveAResults"><?php echo getTexts('INTERFACE', 'save')?></button>
    <button name="confirmAResults" id="watcherSide"
    <?php substr($data['data'][0]['confirmations'],0,1) == "1" ? print("disabled") : print('style="background-color: #3d93cc !important;"') ?>>
        <?php echo getTexts('INTERFACE', 'confirm')?>
    </button>
    <button name="closePattern"><?php echo getTexts('INTERFACE', 'close')?></button>
</div>