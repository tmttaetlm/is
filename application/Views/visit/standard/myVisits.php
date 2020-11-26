<div class="autocomplete" data-position-left="370px" data-position-top="45px" style="margin-bottom: 10px;">
    <label for="visitSelectDay">Дата:</label>
    <input type="date" id="visitSelectDay" />
    <label for="personForVisit">Преподаватель:</label>
    <input type="text" id="personForVisit" name="personForVisit" placeholder="Поиск..." />
    <label for="visitSelectLesson">Урок:</label>
    <select id="visitSelectLesson" name="visitSelectLesson">
        <option value="1">1</option>
        <option value="2">2</option>
        <option value="3">3</option>
        <option value="4">4</option>
        <option value="5">5</option>
        <option value="6">6</option>
        <option value="7">7</option>
        <option value="8">8</option>
        <option value="9">9</option>
        <option value="10">10</option>
    </select>
    <button name="addVisit">Добавить</button>
    <br/>
    <div id="visitInfo" style="text-align: center;" class="hide">
        <span class="visitInfo hide">К выбранному учителю в выбранную дату есть посещения.</span>
    </div>
</div>