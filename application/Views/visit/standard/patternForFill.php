<?php
    function getTexts($section,$property) {
        $ini_params = parse_ini_file(ROOT.'/public/texts/'.$_COOKIE["lang"].'-lang.ini',true);
        return $ini_params[$section][$property];
    }
?>

<table class="results" id="visitResults">
    <caption><?php echo getTexts('TABLE_HEADERS', 'caption')?></caption>
    <tr>
        <th style="width: 20%;"><?php echo getTexts('TABLE_HEADERS', 'date')?></th>
        <th style="width: 20%;"><?php echo getTexts('TABLE_HEADERS', 'grade')?></th>
        <th style="width: 20%;"><?php echo getTexts('TABLE_HEADERS', 'subject')?></th>
        <th style="width: 40%;"><?php echo getTexts('TABLE_HEADERS', 'theme')?></th>
    </tr>
    <tr>
        <td><?php echo date("d.m.Y", strtotime($data['data'][0]['visitDate'])) ?></td>
        <td style="text-align: center;">
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
        <td>
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
        <td>
            <input type="text" class="visitInputs" id="visitTopic" name="visitTopic" placeholder="максимум 120 символов..." value="<?php print($data['data'][0]['theme']) ?>" 
            <?php substr($data['data'][0]['confirmations'],0,1) == "1" ? print("disabled") : print("") ?>>
        </td>
    </tr>
    <tr>
        <th colspan="2"><?php echo getTexts('TABLE_HEADERS', 'whoWasVisited')?></th>
        <td colspan="2" style="font-size: 18px; text-align: center;"><?php echo $data['data'][0]['whoWasVisited'] ?></td>
    </tr>
    <tr>
        <th colspan="2"><?php echo getTexts('TABLE_HEADERS', 'whoVisited')?></th>
        <td colspan="2" style="font-size: 18px; text-align: center;"><?php echo $data['data'][0]['whoVisited'] ?></td>
    </tr>
    <tr><th colspan="4"><?php echo getTexts('TABLE_HEADERS', 'purpose')?></th></tr>
    <tr><td colspan="4"><?php echo $data['data'][0]['purpose'] ?></td></tr>
    <tr><th colspan="4"><?php echo getTexts('TABLE_HEADERS', 'feedback')?></th></tr>
    <?php for ($i = 0; $i <= 15; $i++):?>
        <tr id="<?php echo "row".$data['def'][$i]['num'] ?>">
            <?php !is_null($data['def'][$i]['d1']) ? print("<th rowspan=".$data['def'][$i]['rs'].">".getTexts('CRITERIAS',$data['def'][$i]['d1'])."</th>") : print("") ?>
            <td><?php echo getTexts('CRITERIAS',$data['def'][$i]['d2']) ?></td>
            <td style="text-align: center;">
                <select name="selectEvaluation" class="selectEvaluation" id="<?php echo "mk".$data['def'][$i]['num'] ?>"
                <?php substr($data['data'][0]['confirmations'],0,1) == "1" ? print("disabled") : print("") ?>>
                    <option value="" <?php substr($data['data'][0]['evaluates'],$i,1) == "0" ? print("selected") : print("") ?>></option>    
                    <option value="one" <?php substr($data['data'][0]['evaluates'],$i,1) == "1" ? print("selected") : print("") ?>>1</option>
                    <option value="two" <?php substr($data['data'][0]['evaluates'],$i,1) == "2" ? print("selected") : print("") ?>>2</option>
                    <option value="three" <?php substr($data['data'][0]['evaluates'],$i,1) == "3" ? print("selected") : print("") ?>>3</option>
                    <option value="four" <?php substr($data['data'][0]['evaluates'],$i,1) == "4" ? print("selected") : print("") ?>>4</option>
                    <option value="five" <?php substr($data['data'][0]['evaluates'],$i,1) == "5" ? print("selected") : print("") ?>>5</option>
                </select>
            </td>
            <td>
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
                            echo "";
                            break;
                    }
                ?>
            </td>
        </tr>
    <?php endfor;?>
    <tr><th colspan="4"><?php echo getTexts('TABLE_HEADERS', 'recommendation')?></th></tr>
    <tr>
        <td colspan="4">
            <textarea rows="5" maxlength="2000" id="visitRecommendation" name="visitRecommendation" class="big_textarea"
            <?php substr($data['data'][0]['confirmations'],0,1) == "1" ? print("disabled") : print("") ?>><?php print($data['data'][0]['recommendation']) ?></textarea>
        </td>
    </tr>
    <tr><th colspan="4"><?php echo getTexts('TABLE_HEADERS', 'purpose_feedback')?></th></tr>
    <tr>
        <td colspan="4">
            <textarea rows="5" maxlength="2000" id="visitPurposeRecommendation" name="visitPurposeRecommendation" class="big_textarea"
            <?php substr($data['data'][0]['confirmations'],0,1) == "1" ? print("disabled") : print("") ?>><?php print($data['data'][0]['purpose_review']) ?></textarea>
        </td>
    </tr>
</table>
<div class="visitBut">
    <button name="saveResults"><?php echo getTexts('INTERFACE', 'save')?></button>
    <button name="confirmResults" id="watcherSide"
    <?php substr($data['data'][0]['confirmations'],0,1) == "1" ? print("disabled") : print('style="background-color: #3d93cc !important;"') ?>>
        <?php echo getTexts('INTERFACE', 'confirm')?>
    </button>
    <button name="closePattern"><?php echo getTexts('INTERFACE', 'close')?></button>
</div>