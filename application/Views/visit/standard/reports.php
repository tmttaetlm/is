<div class="container">
    <div id="params" class="autocomplete box-1" data-position-left="165px" data-position-top="60px" style="text-align: center">      
        <label for="personForReport">Преподаватель:</label>
        <input type="text" id="personForReport" name="personForReport" placeholder="Поиск..." />
        <br>
        <input type="radio" id="WhoVisited" name="visitType" value="WhoVisited" checked="checked" />
        <label for="WhoVisited">Наблюдатель</label>
        <input type="radio" id="WhoWasVisited" name="visitType" value="WhoWasVisited"/>
        <label for="WhoWasVisited">Учитель</label>
        <br>
        <label for="details" id="reportDetailsLabel">Подробно</label>
        <input type="checkbox" id="reportDetails" style="margin-top: 20px; margin-right: 20px;">
        <label for="details" id="detailsDateLabel" class="hide">Выбрать дату</label>
        <input type="checkbox" id="detailsDate" class="hide" style="margin-top: 20px; margin-right: 20px;">
        <input type="date" id="detailsDateField" class="hide">
        <br>
        <button name="showVisitReport" id="report0" class="sendQuery">Показать</button>
        <hr color="#dddddd" style="margin-top: 15px;">
        <button name="saveVisitReports" id="report1" class="but">Отчет по критериям выбранного учителя</button>
        <button name="saveVisitReports" id="report2" class="but">Отчет по критериям в разрезе предметов</button>
    </div>
    <div class='commonInfo box-2'>
        <label>Количество посещении за период с </label>
        <input type="date" id="visitSelectStartDay" />
        <label> по </label>
        <input type="date" id="visitSelectEndDay" />
        <div id='numberOfVisits'></div>
        <hr color="#dddddd" style="margin-top: 15px; margin-bottom: 15px;">
        <button name="showVisitReport" id="report3" class="but">Отчет по всем посещениям</button>
    </div>
</div>
<form id="dumpVisitReports" method="post" action="/visit/getReportsDump">
    <input type="hidden" name="whoWasVisited">
</form>