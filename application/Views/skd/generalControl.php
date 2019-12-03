<div class="container">
    <div class="box-1 paramSelect">
        <p>Тип отчета:</p>
        <div class="gcReportControls">
            <div class="but">
                <input type="radio" checked="checked" id="gcStaff" name="gcReportType" value="gcStaff"/>
                <label for="gcStaff">Сотрудники</label>
                <br/>
                <input type="radio" id="gcStudents" name="gcReportType" value="gcStudents"/>
                <label for="gcStudents">Учащиеся</label>
                <br/>
                <input type="radio" id="gcStaffStudents" name="gcReportType" value="gcStaffStudents"/>
                <label for="gcStaffStudents">Сотрудники и учащиеся</label>
            </div>
            <div class="but">
                <input type="radio" id="outside" name="gcReportType2" value="outside"/>
                <label for="outside">Отсутствующие</label>
                <br/>
                <input type="radio" id="inside" name="gcReportType2" value="inside"/>
                <label for="inside">В школе</label>
                <br/>
                <input type="radio" checked="checked" id="all" name="gcReportType2" value="all"/>
                <label for="all">Все</label>
            </div>
        </div>
        <div class="gcButtons">
            <button name="generalControlGetReport" class="but">Показать</button>
            <form id="dump" method="post"  action="/skd/getgcexport" class="but">
                <input type="hidden" name="who">
                <input type="hidden" name="where">
            </form>
            <button name="getDumpButton" >Выгрузить</button>

        </div>

    </div>
    <div  class="box-2 paramSelect">
        <div id='numberOfPeople'></div>
        <br/>
        <!--<button name="generalControlGetData" class="sendQuery">Обновить</button>-->
    </div>    
</div>






