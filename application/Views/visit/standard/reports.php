<div class="container">
    <div id="params" class="autocomplete box-1" data-position-left="165px" data-position-top="95px" style="text-align: center">
        <label for="visitSelectStartDay">Период с:</label>
        <input type="date" id="visitSelectStartDay" />
        <label for="visitSelectEndDay">по:</label>
        <input type="date" id="visitSelectEndDay" />
        <br>
        <label for="personForReport">Преподаватель:</label>
        <input type="text" id="personForReport" name="personForReport" placeholder="Поиск..." />
        <br>
        <input type="radio" checked="checked" id="WhoVisited" name="visitType" value="WhoVisited"/>
        <label for="WhoVisited">Как наблюдатель</label>
        <input type="radio" id="WhoWasVisited" name="visitType" value="WhoWasVisited"/>
        <label for="WhoWasVisited">Как посещаемый</label>
        <br>
        <button name="showVisitReport" class="sendQuery">Показать</button>
        <hr color="#dddddd" >
        <button name="saveVisitReports" id="report1" class="but">Отчет по критериям выбранного учителя</button>
        <button name="saveVisitReports" id="report2" class="but">Отчет по критериям в разрезе предметов</button>
    </div>
    <div id='numberOfVisits' class='commonInfo box-2'>
    </div>
</div>
<form id="dumpVisitReports" method="post" action="/visit/getReportsDump">
    <input type="hidden" name="whoWasVisited">
</form>