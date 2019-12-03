<div class="paramSelect">
     <p>Выберите подразделение:</p>
        <div class = "divisions">
        <select id="selectDivision" name="selectDivision">
            <option value="">Все подразделения</option>
        </select>
        </div>    
</div>    
<div class="paramSelect">
    <p>Укажите тип отчета:</p>
    <input type="radio" checked="checked" id="staffEntranceExit" name="staffReportType" value="staffEntranceExit"/>
    <label for="staffEntranceExit">Вход-выход работников за день</label>
    <input type="date" id="staffSelectDay" />
    <br/>
    <input type="radio" id="staffWhoIsAtSchool" name="staffReportType" value="staffWhoIsAtSchool"/>
    <label for="staffWhoIsAtSchool">Кто в школе</label>
    <br/>
    <input type="radio" id="personByPeriod" name="staffReportType" value="personByPeriod"/>
    <label for="personByPeriod">По сотруднику за период</label>
    <div class="hide" id="personSet">
        <SELECT type="select" id="selectPerson" >
        </SELECT>
        <label id="personByPeriodCal"> c: <input type="date" id="personSelectDayStart"> по:<input type="date" id="personSelectDayEnd"></label>
        <br/>
        <input type="radio" id="enEx" name="typePersonByPeriod" value="enEx" checked="checked"/>
        <label for="enEx">Вход/выход</label>
        <input type="radio" id="trace" name="typePersonByPeriod" value="trace"/>
        <label for="trace">Все проходы</label>
    </div>
</div>
<button name="getStaffLogs" class="sendQuery">Отправить запрос</button>