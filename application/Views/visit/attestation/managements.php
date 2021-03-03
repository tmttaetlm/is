<div class="container">
    <div class="box-2" id="teachersListA">
        <select id="selectTeacherA" size="9"></select>
        <!--<button id="deleteTeacherA">Удалить</button>-->
    </div>
    <div class="box-1">
        <div class="autocomplete" data-position-left="150px" data-position-top="45px">
            <label for="personForAManagement">Преподаватель:</label>
            <input type="text" id="personForAManagement" name="personForAManagement" placeholder="Поиск..." /><br />
        </div>
        <div style="margin-top: 15px;"><strong>Наблюдатели</strong></div>
        <div style="float: left; width: 51%; margin-top: 10px;">
            <div class="autocomplete" data-position-left="5px" data-position-top="60px">
                <label for="personForAPlannig">Фокус "Планирование":</label><br />
                <input type="text" id="personForAPlannig" name="personForAPlannig" placeholder="Выбрать..." /><br />
            </div>
            <div class="autocomplete" data-position-left="5px" data-position-top="61px">
                <label for="personForATeaching">Фокус "Преподавание":</label><br />
                <input type="text" id="personForATeaching" name="personForATeaching" placeholder="Выбрать..." />
            </div>
            <div class="autocomplete" data-position-left="5px" data-position-top="60px">
                <label for="personForAEvaluating">Фокус "Оценивание учебных достижений":</label><br />
                <input type="text" id="personForAEvaluating" name="personForAEvaluating" placeholder="Выбрать..." />
            </div>
            <div class="autocomplete" data-position-left="5px" data-position-top="61px">
                <label for="personForAComplex">Комплексный анализ урока:</label><br />
                <input type="text" id="personForAComplex" name="personForAComplex" placeholder="Выбрать..."/><br />
            </div>
        </div>
        <div style="float: right; width: 48%; margin-top: 10px;">
            <label for="pAttestationDateFrom">Посещение с:</label>
            <label for="pAttestationDateTo" style="margin-left: 55px">по:</label><br />
            <input type="date" id="pAttestationDateFrom" name="pAttestationDateFrom" style="margin-right: 2px;"/>
            <input type="date" id="pAttestationDateTo" name="pAttestationDateTo" style="margin-top: 5px; margin-bottom: 15px;"/><br />
            <label for="tAttestationDateFrom">Посещение с:</label>
            <label for="tAttestationDateTo" style="margin-left: 55px">по:</label><br />
            <input type="date" id="tAttestationDateFrom" name="tAttestationDateFrom" style="margin-right: 2px;"/>
            <input type="date" id="tAttestationDateTo" name="tAttestationDateTo" style="margin-top: 5px; margin-bottom: 15px;"/><br />
            <label for="eAttestationDateFrom">Посещение с:</label>
            <label for="eAttestationDateTo" style="margin-left: 55px">по:</label><br />
            <input type="date" id="eAttestationDateFrom" name="eAttestationDateFrom" style="margin-right: 2px;"/>
            <input type="date" id="eAttestationDateTo" name="eAttestationDateTo" style="margin-top: 5px; margin-bottom: 15px;"/><br />
            <label for="cAttestationDateFrom">Посещение с:</label>
            <label for="cAttestationDateTo" style="margin-left: 55px">по:</label><br />
            <input type="date" id="cAttestationDateFrom" name="cAttestationDateFrom" style="margin-right: 2px;"/>
            <input type="date" id="cAttestationDateTo" name="cAttestationDateTo" style="margin-top: 5px;"/>
        </div>
        <button name="saveSynods" id="saveSynods">Назначить</button>
    </div>
</div>