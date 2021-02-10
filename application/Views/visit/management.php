<?php
    function getTexts($section,$property) {
        $ini_params = parse_ini_file('/home/developer/Code/PHP/is/public/texts/'.$_COOKIE["lang"].'-lang.ini',true);
        return $ini_params[$section][$property];
    }
?>

<div class="autocomplete addNewTeacher" data-position-left="145px" data-position-top="45px">
    <label for="personForManagement">Преподаватель:</label>
    <input type="text" id="personForManagement" name="personForManagement" placeholder="Добавить нового преподавателя" />
    <button id="addNewTeacher">Добавить</button>
</div>
<div class="container">
    <div class="box-2" id="teachersList">
        <select id="selectTeacher" size="9"></select>
        <button id="deleteTeacher">Удалить</button>
    </div>
    <div class="box-1">
        <label for="teachersPurpose">Цель профессионального развития:</label><br />
        <textarea rows="5" maxlength="1000" id="teachersPurpose" name="teachersPurpose" placeholder="Пусто" style="margin-bottom: 10px;"></textarea><br />
        <label for="teachersPurpose">Текущий УПМ:</label><br />
        <select id="selectCurLevel" class="selectLevel">
            <option value="" selected></option>    
            <option value="intern"><?php echo getTexts('TEACHERS_LEVELS', 'intern') ?></option>
            <option value="teacher"><?php echo getTexts('TEACHERS_LEVELS', 'teacher') ?></option>
            <option value="moderator"><?php echo getTexts('TEACHERS_LEVELS', 'moderator') ?></option>
            <option value="expert"><?php echo getTexts('TEACHERS_LEVELS', 'expert') ?></option>
            <option value="researcher"><?php echo getTexts('TEACHERS_LEVELS', 'researcher') ?></option>
            <option value="master"><?php echo getTexts('TEACHERS_LEVELS', 'master') ?></option>
            <option value="basic"><?php echo getTexts('TEACHERS_LEVELS', 'basic') ?></option>
            <option value="first"><?php echo getTexts('TEACHERS_LEVELS', 'first') ?></option>
        </select><br />
        <label for="teachersPurpose">Заявляемый УПМ:</label><br />
        <select id="selectUpLevel" class="selectLevel">
            <option value="" selected></option>    
            <option value="intern"><?php echo getTexts('TEACHERS_LEVELS', 'intern') ?></option>
            <option value="teacher"><?php echo getTexts('TEACHERS_LEVELS', 'teacher') ?></option>
            <option value="moderator"><?php echo getTexts('TEACHERS_LEVELS', 'moderator') ?></option>
            <option value="expert"><?php echo getTexts('TEACHERS_LEVELS', 'expert') ?></option>
            <option value="researcher"><?php echo getTexts('TEACHERS_LEVELS', 'researcher') ?></option>
            <option value="master"><?php echo getTexts('TEACHERS_LEVELS', 'master') ?></option>
            <option value="basic"><?php echo getTexts('TEACHERS_LEVELS', 'basic') ?></option>
            <option value="first"><?php echo getTexts('TEACHERS_LEVELS', 'first') ?></option>
        </select>
        <div class="vmButtons">
            <!--<button name="showPersonManagements" id="showPersonManagements">Показать</button>-->
            <button name="savePurpose" id="savePurpose">Сохранить</button>
        </div>
    </div>
</div>
<hr style="margin-top: 15px; margin-bottom: 15px; height: 1px; background-color: #ddd;" color="#dddddd">
<div>
    <label>Первое полугодие:</label>
    <input type="date" id="firstPeriodStart" name="setPeriod" style="margin-right: 5px" />
    <label style="margin-right: 5px">-</label>
    <input type="date" id="firstPeriodEnd" name="setPeriod" />
    <label  style="margin-left: 5px">Второе полугодие:</label>
    <input type="date" id="secondPeriodStart" name="setPeriod" style="margin-right: 5px" />
    <label style="margin-right: 5px">-</label>
    <input type="date" id="secondPeriodEnd" name="setPeriod" />
</div>