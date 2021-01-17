<div class="container">
    <div id="params" class="autocomplete box-1" data-position-left="165px" data-position-top="60px" style="text-align: center">
        <label for="personForAReport">Преподаватель:</label>
        <input type="text" id="personForAReport" name="personForAReport" placeholder="Поиск..." />
        <br>
        <input type="radio" id="WhoVisitedA" name="AvisitType" value="WhoVisited" checked="checked" />
        <label for="WhoVisitedA">Наблюдатель</label>
        <input type="radio" id="WhoWasVisitedA" name="AvisitType" value="WhoWasVisited" />
        <label for="WhoWasVisitedA">Учитель</label>
        <br>
        <label for="reportDetailsA" id="reportDetailsLabelA">Подробно</label>
        <input type="checkbox" id="reportDetailsA" style="margin-top: 20px; margin-right: 20px;">
        <label for="detailsDateA" id="detailsDateLabelA" class="hide">Выбрать дату</label>
        <input type="checkbox" id="detailsDateA" class="hide" style="margin-top: 20px; margin-right: 20px;">
        <input type="date" id="detailsDateFieldA" class="hide">
        <br>
        <button id="report5" name="showVisitReport" class="sendQuery">Показать</button>
    </div>
    <div class='commonInfo box-2'>
        <label>Количество посещении за период с </label>
        <input type="date" id="AvisitSelectStartDay" />
        <label> по </label>
        <input type="date" id="AvisitSelectEndDay" />
        <div id='numberOfAVisits'></div>
        <hr color="#dddddd" style="margin-top: 15px; margin-bottom: 15px;">
        <button name="showVisitReport" id="report4" class="but">Отчет по всем посещениям</button>
    </div>
</div>
<form id="dumpVisitReportsA" method="post" action="/visit/getAReportsDump">
    <input type="hidden" name="whoWasVisited">
</form>