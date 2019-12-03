 <div class ="controls">
    <div class="paramSelect">
     <p>Выберите класс:</p>
        <div class="grades">
        <select id="grade" name="grade">
            <option value="7">7</option>
            <option value="8">8</option>
            <option value="9">9</option>
            <option value="10">10</option>
            <option value="11">11</option>
            <option value="12">12</option>
        </select>

        <select id="litera" name="litera">    
            <option value="A">A</option>
            <option value="B">B</option>
            <option value="C">C</option>
            <option value="D">D</option>
            <option value="E">E</option>
            <option value="F">F</option>
            <option value="G">G</option>
            <option value="H">H</option>
            <option value="I">I</option>
        </select>
        </div>    
    </div>
    <div class="paramSelect">
    <p>Укажите тип отчета:</p>
    <input type="radio" checked="checked" id="entranceExit" name="reportType" value="entranceExit"/>
    <label for="entranceExit">Вход-выход учащихся за день</label>
    <input type="date" id="selectDay">
    <br/>
    <input type="radio" id="whoIsAtSchool" name="reportType" value="whoIsAtSchool"/>
    <label for="whoIsAtSchool">Кто в школе</label>
    <br/>
    <input type="radio" id="studentByPeriod" name="reportType" value="studentByPeriod"/>
    <label for="studentByPeriod">По ученику за период</label>
    <div class="hide" id="studentSet">
        <SELECT type="select" id="selectStudent">
        </SELECT>
        <label id="studentByPeriodcal"> c: <input type="date" id="selectDayStart"> по:<input type="date" id="selectDayEnd"></label>
        <br/>
    </div>
    </div>
    <button name="getStudentsLogs" class="sendQuery">Отправить запрос</button>
 </div>