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
        <textarea rows="5" maxlength="2000" id="lso1planning" name="lso1planning" class="lso_textarea" placeholder="<?php echo getTexts('PLACEHOLDERS', 'lso1planning')?>" readOnly><?php print($data[0]['planning_lesson_review']) ?></textarea>
    </td></tr>
    <tr><td>
        <textarea rows="5" maxlength="2000" id="lso1teaching" name="lso1teaching" class="lso_textarea" placeholder="<?php echo getTexts('PLACEHOLDERS', 'lso1teaching')?>" readOnly><?php print($data[0]['teaching_lesson_review']) ?></textarea>
    </td></tr>
    <tr><td>
        <textarea rows="5" maxlength="2000" id="lso1evaluating" name="lso1evaluating" class="lso_textarea" placeholder="<?php echo getTexts('PLACEHOLDERS', 'lso1evaluating')?>" readOnly><?php print($data[0]['evaluating_lesson_review']) ?></textarea>
    </td></tr>
    <tr><td>
        <textarea rows="5" maxlength="2000" id="lso1complex" name="lso1complex" class="lso_textarea" placeholder="<?php echo getTexts('PLACEHOLDERS', 'lso1complex')?>" readOnly><?php print($data[0]['complex_lesson_review']) ?></textarea>
    </td></tr>
    <tr><td>
        <textarea rows="5" maxlength="2000" id="lso1summary" name="lso1summary" class="lso_textarea" placeholder="<?php echo getTexts('PLACEHOLDERS', 'lso1summary')?>"><?php print($data[0]['first_recommendation']) ?></textarea>
    </td></tr>
    <tr><td>
        <textarea rows="5" maxlength="2000" id="lso1correction" name="lso1correction" class="lso_textarea" placeholder="<?php echo getTexts('PLACEHOLDERS', 'lso1correction')?>"><?php print($data[0]['first_correction']) ?></textarea>
    </td></tr>
</table>
<div class="visitBut">
    <button name="saveLSO"><?php echo getTexts('INTERFACE', 'save')?></button>
    <button name="closePattern"><?php echo getTexts('INTERFACE', 'close')?></button>
</div>