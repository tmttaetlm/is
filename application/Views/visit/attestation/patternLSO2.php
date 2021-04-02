<?php
    function getTexts($section,$property) {
        $ini_params = parse_ini_file('/home/developer/Code/PHP/is/public/texts/'.$_COOKIE["lang"].'-lang.ini',true);
        return $ini_params[$section][$property];
    }
?>

<table class="results" id="lsoHeader">
    <caption>ЛИСТ ШКОЛЬНОГО ОЦЕНИВАНИЯ</caption>
    <tr>
        <td colspan="2" style="width: 15%;"><?php echo getTexts('LSO_TABLE_HEADER', 'fio')?></td>
        <td colspan="2" style="width: 15%;"><?php echo $data[0]['whoWasVisited']?></td>
    </tr>
    <tr>
        <td colspan="2" style="width: 15%;"><?php echo getTexts('LSO_TABLE_HEADER', 'job_info')?></td>
        <td colspan="2" style="width: 15%;">
            <input type="text" class="visitInputs" id="LSO_job" name="LSO_job" value="<?php echo $data[0]['position']?>">
        </td>
    </tr>
    <tr>
        <td colspan="2" style="width: 15%;"><?php echo getTexts('LSO_TABLE_HEADER', 'cur_level')?></td>
        <td colspan="2" style="width: 15%;">
            <input type="text" class="visitInputs" id="LSO_curLevel" name="LSO_curLevel" value="<?php echo getTexts('TEACHERS_LEVELS', $data[0]['cur_level']) ?>">
        </td>
    </tr>
    <tr>
        <td colspan="2" style="width: 15%;"><?php echo getTexts('LSO_TABLE_HEADER', 'up_level')?></td>
        <td colspan="2" style="width: 15%;">
            <input type="text" class="visitInputs" id="LSO_upLevel" name="LSO_upLevel" value="<?php echo getTexts('TEACHERS_LEVELS', $data[0]['up_level']) ?>">
        </td>
    </tr>
</table>
<table class="results" id="lsoTable">
    <tr><td>
        <p class="lsoHeader">
            <?php
                $today = getdate();
                $years = $today['mon'] > 5 ? ($today['year'].' - '.($today['year']+1)) : (($today['year']-1).' - '.$today['year']);
                $text = str_replace('*year*', $years, getTexts('LSO_TABLE_HEADER', 'purpose'));
                echo $text;
            ?>
        </p>
        <textarea rows="5" maxlength="2000" id="lsoPurpose" name="lsoPurpose" class="lso_textarea" readOnly><?php print($data[0]['purpose']) ?></textarea>
    </td></tr>
    <tr><td>
        <p class="lsoHeader"><?php echo getTexts('LSO_TABLE_HEADER', $data[0]['half_year'])?></p>
        <textarea rows="5" maxlength="2000" id="lso2planning" name="lso2planning" class="lso_textarea" placeholder="<?php echo getTexts('PLACEHOLDERS', 'lso2planning')?>" readOnly><?php print($data[0]['planning_lesson_review']) ?></textarea>
    </td></tr>
    <tr><td>
        <textarea rows="5" maxlength="2000" id="lso2teaching" name="lso2teaching" class="lso_textarea" placeholder="<?php echo getTexts('PLACEHOLDERS', 'lso2teaching')?>" readOnly><?php print($data[0]['teaching_lesson_review']) ?></textarea>
    </td></tr>
    <tr><td>
        <textarea rows="5" maxlength="2000" id="lso2evaluating" name="lso2evaluating" class="lso_textarea" placeholder="<?php echo getTexts('PLACEHOLDERS', 'lso2evaluating')?>" readOnly><?php print($data[0]['evaluating_lesson_review']) ?></textarea>
    </td></tr>
    <tr><td>
        <textarea rows="5" maxlength="2000" id="lso2complex" name="lso2complex" class="lso_textarea" placeholder="<?php echo getTexts('PLACEHOLDERS', 'lso2complex')?>" readOnly><?php print($data[0]['complex_lesson_review']) ?></textarea>
    </td></tr>
    <tr><td>
        <textarea rows="5" maxlength="2000" id="lso2summary" name="lso2summary" class="lso_textarea" placeholder="<?php echo getTexts('PLACEHOLDERS', 'lso2summary')?>"><?php print($data[0]['second_recommendation']) ?></textarea>
    </td></tr>
    <tr><td>
        <textarea rows="2" maxlength="2000" id="lso2comment" name="lso2comment" class="lso_textarea" placeholder="<?php echo getTexts('PLACEHOLDERS', 'lso2comment')?>"><?php print($data[0]['second_comment']) ?></textarea>
            <?php for ($i=0; $i < 3; $i++) { ?>
                <table>
                    <caption><?php echo getTexts('LSO_QUESTIONS', 'caption'.($i+1))?></caption>
                    <tr>
                        <th></th>
                        <?php for ($j=1; $j <= 5; $j++) { ?>
                            <th><?php echo getTexts('LSO_QUESTIONS', 'q'.($j+($i*5)))?></th>
                        <?php } ?>
                    </tr>
                    <?php for ($k=1; $k <= 4; $k++) { ?>
                        <tr>
                            <td><?php echo getTexts('LSO_QUESTIONS', 'ans'.$k)?></td>
                            <?php $cents = $data[0]['q'.($i*4+$k)] != '' ? explode('|', $data[0]['q'.($i*4+$k)]) : [];
                            for ($j=1; $j <= 5; $j++) { ?>
                                <td>
                                    <input type="text" class="visitInputs" id="<?php echo 'visitAnswer'.($j*$k)?>" name="visitAnswers" maxlength="5"
                                    value="<?php if (!empty($cents)) { echo $cents[$j-1]; } ?>">
                                </td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                    <tr>
                        <td><?php echo getTexts('LSO_QUESTIONS', 'cnt')?></td>
                        <td colspan = '5'>
                            <input type="text" class="visitInputs" id="<?php echo 'visitAnswerCount'.($i)?>" name="visitAnswerCount" maxlength="5"
                            value="<?php if (!empty($cents)) { echo $cents[5]; } ?>">
                        </td>
                    </tr>
                </table>
            <?php } ?>
        <textarea rows="2" maxlength="2000" id="lso2correction" name="lso2correction" class="lso_textarea" placeholder="<?php echo getTexts('PLACEHOLDERS', 'lso2correction')?>"><?php print($data[0]['second_correction']) ?></textarea>
    </td></tr>
    <tr><td>
        <textarea rows="5" maxlength="2000" id="lso2recommendation" name="lso2recommendation" class="lso_textarea" placeholder="<?php echo getTexts('PLACEHOLDERS', 'lso2recommendation')?>"><?php print($data[0]['all_recommendation']) ?></textarea>
    </td></tr>
</table>
<div class="visitBut">
    <button name="saveLSO"><?php echo getTexts('INTERFACE', 'save')?></button>
    <button name="closePattern"><?php echo getTexts('INTERFACE', 'close')?></button>
</div>