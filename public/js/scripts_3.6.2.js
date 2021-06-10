/* 
 * Main Javascript file
 */
'use strict'

//Create a storage object
var isStorage = {},
    timerID = 0,
    today = getInputDate();

window.onload = function() {
    
    //Catches clicks and send to handler
    document.addEventListener("click", function (event) {
        clickHandler(event.target);
    });
    
    //Catches changes and send to handler
    document.addEventListener("change", function (event) {
        changeHandler(event.target);
    });

    //Catches getting focus
    document.addEventListener("focusin", function (event) {
        focusInHandler(event.target);
        mask(event);
    });

    document.addEventListener('focusout', function (event) {
        mask(event);
    });

    document.addEventListener('mouseover', function (event) {
        mouseoverHandler(event.target);
    });

    /*//Catches keyboard input and send to handler
    document.addEventListener("onkeypress", function (event) {
        keyHandler(event.target);
    });*/    

    //Отлавливает ввод с клавиатуры и передает в обработчик
    document.body.onkeyup = function(event) {
        var obj=event.target||event.srcElement;
        keyHandler(obj,event);
        mask(event);
    };

    //MTdev
    document.body.onkeydown = function(event) {
        //var obj=event.target||event.srcElement;
        //keyDownHandler(obj,event);
        //mask(event);
    };

    //Set active first tab in Tabs
    var tabs = document.querySelectorAll('.ui-tabs');
    tabs.forEach(tab => {
        tab.firstElementChild.checked = true;
    });
    
    
    //Настройка календарей
    var minDateStudent = getInputDate(-31);
    var minDatePerson = getInputDate(-150);

    if (document.getElementById('selectDay')){
        document.getElementById('selectDay').value = today;
        document.getElementById('selectDay').setAttribute("max", today);
        document.getElementById('selectDayStart').value = today;
        document.getElementById('selectDayStart').setAttribute("min", minDateStudent);
        document.getElementById('selectDayStart').setAttribute("max", today);
        document.getElementById('selectDayEnd').value = today;
        document.getElementById('selectDayEnd').setAttribute("min", minDateStudent);
        document.getElementById('selectDayEnd').setAttribute("max", today);
    }
    if (document.getElementById('staffSelectDay')){
        document.getElementById('staffSelectDay').setAttribute("max", today);
        document.getElementById('staffSelectDay').value = today;
        document.getElementById('personSelectDayStart').setAttribute("min", minDatePerson);
        document.getElementById('personSelectDayEnd').setAttribute("min", minDatePerson);
        document.getElementById('personSelectDayStart').setAttribute("max", today);
        document.getElementById('personSelectDayEnd').setAttribute("max", today);
        document.getElementById('personSelectDayStart').value = today;
        document.getElementById('personSelectDayEnd').value = today;
    }
    if (document.getElementById('visitSelectDay')){
        document.getElementById('visitSelectDay').value = today;
    }
    if (document.getElementById('visitMeSelectDay')){
        document.getElementById('visitMeSelectDay').value = today;
    }

    if (document.getElementById('visitSelectStartDay')){
        let now = new Date();
        if (now.getMonth() < 9) { 
            var start = (now.getFullYear()-1)+'-09-01';
            var end = (now.getFullYear())+'-05-25';
        } else { 
            var start = (now.getFullYear())+'-09-01'; 
            var end = (now.getFullYear()+1)+'-05-25';

        }
        document.getElementById('visitSelectStartDay').value = start;
        document.getElementById('visitSelectEndDay').value = end;
        document.getElementById('AvisitSelectStartDay').value = start;
        document.getElementById('AvisitSelectEndDay').value = end;
    }

    if (document.getElementById('detailsDateField')) {
        document.getElementById('detailsDateField').value = today; 
    }
    if (document.getElementById('detailsDateFieldA')) {
        document.getElementById('detailsDateFieldA').value = today; 
    }

    if (document.getElementById('selectDivision')){
        ajax('/skd/getDivisionList', function(data){document.getElementById('selectDivision').innerHTML =  data;});
    }

    //MTDev
    if (document.getElementById('personForVisit')){
        let seachField = document.getElementById("personForVisit");
        seachField.value = '';
        
        ajaxJson('/visit/getStaffList', function(data){
            autocomplete(seachField, data);
        });
    }

    if (document.getElementById('personForEvaluate')){
        let seachField = document.getElementById("personForEvaluate");
        seachField.value = '';
        
        ajaxJson('/visit/getStaffList', function(data){
            autocomplete(seachField, data);
        });
    }

    if (document.getElementById('personForReport')){
        let seachField = document.getElementById("personForReport");
        seachField.value = '';
        
        ajaxJson('/visit/getStaffList', function(data){
            autocomplete(seachField, data);
        });
    }

    if (document.getElementById('personForAReport')){
        let seachField = document.getElementById("personForAReport");
        seachField.value = '';
        
        ajaxJson('/visit/getStaffList', function(data){
            autocomplete(seachField, data);
        });
    }

    if (document.getElementById('personForManagement')){
        let seachField = document.getElementById("personForManagement");
        seachField.value = '';
        
        ajaxJson('/visit/getStaffList', function(data){
            autocomplete(seachField, data);
        });
    }

    if (document.getElementById('personForLso')){
        let seachField = document.getElementById("personForLso");
        seachField.value = '';
        
        ajaxJson('/visit/getStaffList', function(data){
            autocomplete(seachField, data);
        });
    }

    //Инициализация слайдера
    var slides = document.querySelectorAll('#slides .slide');
    if (slides.length>0) {
        var currentSlide = 0;
        var slideInterval = setInterval(slider, 5000);

        document.getElementById('skdItem').addEventListener('mouseover',function (event) {
            clearInterval(slideInterval);
            slides[0].className = 'slide showing';
            slides[1].className = 'slide ';
            slides[2].className = 'slide ';
        });
        
        document.getElementById('fasItem').addEventListener('mouseover',function (event) {
            clearInterval(slideInterval);
            slides[0].className = 'slide';
            slides[1].className = 'slide showing';
            slides[2].className = 'slide ';
        });

        document.getElementById('visitItem').addEventListener('mouseover',function (event) {
            clearInterval(slideInterval);
            slides[0].className = 'slide';
            slides[1].className = 'slide ';
            slides[2].className = 'slide showing';
        });

        document.getElementById('skdItem').addEventListener('mouseout',function (event) {
            slideInterval = setInterval(slider, 5000);
        });
        document.getElementById('fasItem').addEventListener('mouseout',function (event) {
            slideInterval = setInterval(slider, 5000);
        });
        document.getElementById('visitItem').addEventListener('mouseout',function (event) {
            slideInterval = setInterval(slider, 5000);
        });

    }

    //Переключение слайдов
    function slider(){
        slides[currentSlide].className = 'slide';
        currentSlide = (currentSlide+1)%slides.length;
        slides[currentSlide].className = 'slide showing';
    }

    //Язык листа наблюдения
    let matches = document.cookie.match(new RegExp("(?:^|; )" + 'lang'.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"));
    let lang = matches ? decodeURIComponent(matches[1]) : undefined;
    //console.log(lang);
    if (lang == undefined) {
        document.cookie = "lang=ru;samesite=strict";
    } else {
        if (lang == 'ru') {
            if (document.getElementById('rus-lang')) {document.getElementById('rus-lang').checked = true};
        } else {
            if (document.getElementById('kaz-lang')) {document.getElementById('kaz-lang').checked = true};
        }
    }

    if (document.getElementById('personForAManagement')){
        let seachField = document.getElementById("personForAManagement");
        seachField.value = '';
        
        ajaxJson('/visit/getStaffList', function(data){
            autocomplete(seachField, data);
        });
    }
    if (document.getElementById('personForAPlannig')){
        let seachField = document.getElementById("personForAPlannig");
        seachField.value = '';
        
        ajaxJson('/visit/getStaffList', function(data){
            autocomplete(seachField, data);
        });
    }
    if (document.getElementById('personForATeaching')){
        let seachField = document.getElementById("personForATeaching");
        seachField.value = '';
        
        ajaxJson('/visit/getStaffList', function(data){
            autocomplete(seachField, data);
        });
    }
    if (document.getElementById('personForAEvaluating')){
        let seachField = document.getElementById("personForAEvaluating");
        seachField.value = '';
        
        ajaxJson('/visit/getStaffList', function(data){
            autocomplete(seachField, data);
        });
    }
    if (document.getElementById('personForAComplex')){
        let seachField = document.getElementById("personForAComplex");
        seachField.value = '';
        
        ajaxJson('/visit/getStaffList', function(data){
            autocomplete(seachField, data);
        });
    }

    if (document.getElementById('firstPeriodStart') && document.getElementById('firstPeriodEnd') && document.getElementById('secondPeriodStart') && document.getElementById('secondPeriodEnd')){
        ajax('/visit/getHalfYearPeriods', function(data){
            let fp = data.substr(0, data.indexOf('|'));
            let sp = data.substr(data.indexOf('|')+1, data.length);
            document.getElementById("firstPeriodStart").value = fp.substr(0,fp.indexOf(';'));
            document.getElementById("firstPeriodEnd").value = fp.substr(fp.indexOf(';')+1, fp.length);
            document.getElementById("secondPeriodStart").value = sp.substr(0,sp.indexOf(';'));
            document.getElementById("secondPeriodEnd").value = sp.substr(sp.indexOf(';')+1, sp.length);

            if (today >= fp.substr(0,fp.indexOf(';')) && today <= fp.substr(fp.indexOf(';')+1, fp.length)) {
                document.getElementById("pAttestationDateFrom").value = fp.substr(0,fp.indexOf(';'));
                document.getElementById("pAttestationDateTo").value = fp.substr(fp.indexOf(';')+1, fp.length);
                document.getElementById("tAttestationDateFrom").value = fp.substr(0,fp.indexOf(';'));
                document.getElementById("tAttestationDateTo").value = fp.substr(fp.indexOf(';')+1, fp.length);
                document.getElementById("eAttestationDateFrom").value = fp.substr(0,fp.indexOf(';'));
                document.getElementById("eAttestationDateTo").value = fp.substr(fp.indexOf(';')+1, fp.length);
                document.getElementById("cAttestationDateFrom").value = fp.substr(0,fp.indexOf(';'));
                document.getElementById("cAttestationDateTo").value = fp.substr(fp.indexOf(';')+1, fp.length);
            } else if (today >= sp.substr(0,sp.indexOf(';')) && today <= sp.substr(sp.indexOf(';')+1, sp.length)) {
                document.getElementById("pAttestationDateFrom").value = sp.substr(0,sp.indexOf(';'));
                document.getElementById("pAttestationDateTo").value = sp.substr(sp.indexOf(';')+1, sp.length);
                document.getElementById("tAttestationDateFrom").value = sp.substr(0,sp.indexOf(';'));
                document.getElementById("tAttestationDateTo").value = sp.substr(sp.indexOf(';')+1, sp.length);
                document.getElementById("eAttestationDateFrom").value = sp.substr(0,sp.indexOf(';'));
                document.getElementById("eAttestationDateTo").value = sp.substr(sp.indexOf(';')+1, sp.length);
                document.getElementById("cAttestationDateFrom").value = sp.substr(0,sp.indexOf(';'));
                document.getElementById("cAttestationDateTo").value = sp.substr(sp.indexOf(';')+1, sp.length); 
            }
        }, '');
    }
    
    if (document.getElementById('teachersList')){
        ajax('/visit/getAllSavedTeachers', function(data){
            let selList = document.getElementById("teachersList");
            selList.innerHTML = data;
        }, '');
    }

    if (document.getElementById('teachersListA')){
        ajax('/visit/getAllTeachersWithSynod', function(data){
            let selList = document.getElementById("teachersListA");
            selList.innerHTML = data;
        }, '');
    }

    if (document.getElementById('allHY')){ document.getElementById('allHY').checked = true; }

    //if (document.getElementById('monitoring').checked) { resizeWrapper('1366px'); }

}

//Keyboard focusInHandler
function focusInHandler(obj){
    if (obj.classList.contains('inv-select-location')){
        if (obj.length == 1){
            let newOption;
            isStorage.inventoryRooms.forEach(row => {
                newOption = new Option(row.location, row.locationCode);
                obj.appendChild(newOption);
            });

        }
    };
    if (obj.classList.contains('inv-select-comment')){
        if (obj.length == 1){
            let newOption;
            newOption = new Option('-нет-', 0);
            obj.appendChild(newOption);
            isStorage.inventoryComments.forEach(row => {
                newOption = new Option(row.comment, row.id);
                obj.appendChild(newOption);
            });
        }
    }
}

//Keyboard hanler
function keyHandler(obj,_event)
{
    /*if ((obj.id == 'selectDayEnd') || (obj.id == 'selectDayStart') || (obj.id == 'selectDay') || (obj.id == 'staffSelectDay') || (obj.id == 'personSelectDayStart') || (obj.id == 'personSelectDayEnd') || (obj.id == 'bypassSelectDay')){
        var today = new Date();
        today = today.toISOString().substring(0, 10);
        obj.value = today;
    }*/
}

//Change Handler
function changeHandler(obj)
{

    clearInterval(timerID);

    if (obj.name == "fasSeachType")
    {
        var seachBy = obj.options[obj.selectedIndex].dataset.type;
        var seachField = document.getElementById("seachField");
        var ob = {"seachBy":seachBy};
        var fasSeachList = [] , person;
        
        seachField.value = '';
        
        if (seachBy == 'person') {
            ajaxJson('/fas/getPeopleList', function(data){
                 autocomplete(seachField, data);
            }, ob);
        }
        if (seachBy == 'invNumber') {
            autocomplete(seachField, []);
        }
        
        if (seachBy == 'barcode') {
            autocomplete(seachField, []);
        }        
        
        if (seachBy == 'location') {
            ajaxJson('/fas/getLocationList', function(data){
                 autocomplete(seachField, data);
            }, ob);
        }        
        
        if (seachBy == 'fixedAsset') {
            ajaxJson('/fas/getFixedAssetList', function(data){
                 autocomplete(seachField, data);
            }, ob);
        }        
        
        
    }

    if (obj.name == "invSeachType")
    {
        let seachBy = obj.options[obj.selectedIndex].dataset.type;
        let seachField = document.getElementById("invSeachField");
        let ob = {"seachBy":seachBy};
        let invSeachList = [] , person;
        seachField.value = '';
        
        if (seachBy == 'person') {
            ajaxJson('/fas/getInvPeopleList', function(data){
                 autocomplete(seachField, data);
            }, ob);
        }

        if (seachBy == 'invNumber') {
            autocomplete(seachField, []);
        }
        
        if (seachBy == 'location') {
            ajaxJson('/fas/getInvLocationList', function(data){
                 autocomplete(seachField, data);
            }, ob);
        }        
        
        if (seachBy == 'fixedAsset') {
            ajaxJson('/fas/getInvFixedAssetList', function(data){
                 autocomplete(seachField, data);
            }, ob);
        }        

    }
    
    if (obj.name == "role")
    {
        var param = 'iin='+obj.dataset.iin + '&' + 'role='+obj.value;
        ajax('/admin/changeUserRole', function(data){alert('Роль присвоена');}, param);
    }
    
    if (obj.name == "grade")
    {
       if (getSelectedRadio('reportType')=='studentByPeriod')
       {
           getStudentsList();
       }
    }
    
    if (obj.name == "litera")
    {
       if (getSelectedRadio('reportType')=='studentByPeriod')
       {
           getStudentsList();
       }
    }
    
    if (obj.name == "selectDivision")
    {
       if (getSelectedRadio('staffReportType')=='personByPeriod')
       {
           getStaffList();
       }
    }
    
    if (obj.name == "reportType")
    {
        if (obj.value == 'whoIsAtSchool')
        {
            hideReportElements();
        }
        
        if (obj.value == 'entranceExit')
        {
            hideReportElements();
            document.getElementById('selectDay').classList.remove('hide');
        }
        
        if (obj.value == 'studentByPeriod')
        {
            hideReportElements();
            getStudentsList();
            //document.getElementById('studentByPeriodcal').classList.remove('hide');
            document.getElementById('studentSet').classList.remove('hide');
        }
    }
    
    if (obj.name == "userReportType"){
        document.body.querySelector('.userControl').querySelector('.results').innerHTML ="";
    }
    
    //admin roleSettings
    if (obj.id == "skdCanBrowseStudentsLogs" || obj.id == "skdCanBrowseStaffLogs"|| obj.id == "skdCanBrowseGeneralControl" || 
    obj.id == "fasCanSeach" || obj.id == "adminPanel" || obj.id =="skdGeneralControlCanEditComments" || obj.id =="skdCanAddParentContact" || 
    obj.id =="fasInvControl" || obj.id =="fasInvStart" || obj.id =="visitReportAccess" || obj.id =="visitManagementAccess"
    || obj.id =="visitLSOAccess" || obj.id =="visitPDOAccess"){
        var roleList = document.getElementById('roleList');
        if (roleList.selectedIndex ==-1) {
            alert('Выберите роль, для которой настраиваете права!');
            obj.checked =false;
            return false;
        }
        var roleId = roleList.options[roleList.selectedIndex].dataset.id;
        var privId = obj.dataset.id;
        
        if (obj.checked) {
            var param ='privId='+privId +'&roleId='+roleId+'&mode=enable';
            ajax('/admin/setPrivToRole', function(data){alert('Привелегия добавлена');}, param);
        }
        else {
            var param ='privId='+privId +'&roleId='+roleId+'&mode=disable';
            ajax('/admin/setPrivToRole', function(data){alert('Привелегия снята');}, param);
        }
    }

    if (obj.id == "roleList") {
        var roleList = document.getElementById('roleList');
        var param ='roleId='+roleList.options[roleList.selectedIndex].dataset.id;
        
        var cbs = document.getElementsByClassName('perms');
        for(var i = 0; i < cbs.length; i++) {
            cbs[i].checked = false;
        }

        ajax('/admin/getPermissionsByRole', function(data){
            var permissions = JSON.parse(data);
            permissions.forEach(element => {
                document.getElementById(element).checked = true;
            });
        }, param);
    }
    
    //SkdGeneralControl тут записывается коммент
    if (obj.name == "comment")
    {
        var param = 'id='+obj.dataset.id + '&' + 'comment='+obj.value;
        ajax('/skd/Writecomment', function(data){
            if (data!='ok'){
                alert('Комментарий не сохранен!');
            }
            }, 
        param);
    }
    
    if (obj.name == "tab"){
        if (obj.id == 'generalControl'){
            getPeopleCount();
        }
        if (obj.id == 'inventory'){
            resizeWrapper('1366px');
            getInventoryRooms();
            getInventoryComments();
            resetTimer(timerID);
        }
        if (obj.id == 'inventoryControl'){
            getInventoryPeople();
            resizeWrapper('1366px');
            resetTimer(timerID);
        }
        if (obj.id == 'monitoring' || obj.id == 'seach'){
            resizeWrapper('1366px');
            let article = document.getElementsByClassName('seach');
            if (article[0].children[2].children[0] && obj.id == 'seach') { resizeWrapper('1366px') };
        }
        if (obj.id == 'Reports'){
            getNumberOfVisits();
        }
    }

    if (obj.name == "tab2"){
        if (obj.id == 'AReports'){
            getNumberOfAVisits();
        }
    }

    //Inventory change location and comments
    if (obj.classList.contains('inv-select-location')){
        var param = 'locationCode='+obj.value + '&id='+obj.dataset.id;
        ajax('/fas/inventoryChangeLocation', function(data){
            if (data != 'true'){
                alert('Ошибка, изменения не сохранены');
            }
        }, param);
    }
    if (obj.classList.contains('inv-select-comment')){
        if (obj.dataset.id == 0){
            obj.value = '';
        } 
        var param = 'commentId='+obj.value + '&id='+obj.dataset.id;
        ajax('/fas/inventoryChangeComment', function(data){
            if (data != 'true'){
                alert('Ошибка, изменения не сохранены');
            }
        }, param);
    }
    
    //MTdev
    if (obj.name == "contact1" || obj.name == "contact2")
    {
        var param = 'id='+obj.dataset.id+'&'+'contact='+obj.value+'&'+'name='+obj.name;
        ajax('/skd/Writecontact', function(data){
            if (data!='ok'){
                //alert('Номер телефона не сохранен!');
                alert(data);
                obj.value = '';
            }
            }, 
        param);
    }

    if (obj.name == "selectEvaluation") { 
        let row = document.getElementById("row"+obj.id.substr(2));
        let param = 'criteria='+obj.id.substr(2,obj.id.length-2)+'&mark='+obj.value;
        ajax('/visit/getCriteriaDiscription', function(data){
            row.lastElementChild.textContent = data;
        }, param);
    }

    if (obj.id == 'visitSelectStartDay' || obj.id == 'visitSelectEndDay') {
        if (obj.value != '') {
            getNumberOfVisits();
        };
    }

    if (obj.id == 'AvisitSelectStartDay' || obj.id == 'AvisitSelectEndDay') {
        if (obj.value != '') {
            getNumberOfAVisits();
        };
    }

    if (obj.id == 'personForAManagement') {
        let fp = document.getElementById("firstPeriodStart").value+';'+document.getElementById("firstPeriodEnd").value;
        let sp = document.getElementById("secondPeriodStart").value+';'+document.getElementById("secondPeriodEnd").value;
        if (today >= fp.substr(0,fp.indexOf(';')) && today <= fp.substr(fp.indexOf(';')+1, fp.length)) {
            document.getElementById("pAttestationDateFrom").value = fp.substr(0,fp.indexOf(';'));
            document.getElementById("pAttestationDateTo").value = fp.substr(fp.indexOf(';')+1, fp.length);
            document.getElementById("tAttestationDateFrom").value = fp.substr(0,fp.indexOf(';'));
            document.getElementById("tAttestationDateTo").value = fp.substr(fp.indexOf(';')+1, fp.length);
            document.getElementById("eAttestationDateFrom").value = fp.substr(0,fp.indexOf(';'));
            document.getElementById("eAttestationDateTo").value = fp.substr(fp.indexOf(';')+1, fp.length);
            document.getElementById("cAttestationDateFrom").value = fp.substr(0,fp.indexOf(';'));
            document.getElementById("cAttestationDateTo").value = fp.substr(fp.indexOf(';')+1, fp.length);
        } else if (today >= sp.substr(0,sp.indexOf(';')) && today <= sp.substr(sp.indexOf(';')+1, sp.length)) {
            document.getElementById("pAttestationDateFrom").value = sp.substr(0,sp.indexOf(';'));
            document.getElementById("pAttestationDateTo").value = sp.substr(sp.indexOf(';')+1, sp.length);
            document.getElementById("tAttestationDateFrom").value = sp.substr(0,sp.indexOf(';'));
            document.getElementById("tAttestationDateTo").value = sp.substr(sp.indexOf(';')+1, sp.length);
            document.getElementById("eAttestationDateFrom").value = sp.substr(0,sp.indexOf(';'));
            document.getElementById("eAttestationDateTo").value = sp.substr(sp.indexOf(';')+1, sp.length);
            document.getElementById("cAttestationDateFrom").value = sp.substr(0,sp.indexOf(';'));
            document.getElementById("cAttestationDateTo").value = sp.substr(sp.indexOf(';')+1, sp.length); 
        }
        document.getElementById('personForAPlannig').value = '';
        document.getElementById('personForATeaching').value = '';
        document.getElementById('personForAEvaluating').value = ''; 
        document.getElementById('personForAComplex').value = '';
        document.getElementById('selectTeacherA').selectedIndex = -1;
        document.getElementById('saveSynods').innerText = 'Назначить';
    }

    if (obj.id == 'visitSelectDay' || obj.id == 'personForVisit') {
        let param = 'person='+document.getElementById('personForVisit').value+'&date='+document.getElementById('visitSelectDay').value; 
        ajax('/visit/getVisitCount', function(data){
            let msg = document.getElementById('visitInfo');
            if (data != '0') {
                msg.classList.remove('hide');
            } else {
                msg.classList.add('hide');
                document.body.querySelector('.myVisits').querySelector('.results').innerHTML = '';
            }
        }, param);
    }

    if (obj.id == 'firstPeriodStart' || obj.id == 'firstPeriodEnd') {
        let firstPeriodStart = document.getElementById('firstPeriodStart').value;
        let firstPeriodEnd = document.getElementById('firstPeriodEnd').value;
        if (firstPeriodStart != '' && firstPeriodEnd != '') {
            param = 'firstPeriodStart='+firstPeriodStart+'&firstPeriodEnd='+firstPeriodEnd+'&period=1';
            ajax('/visit/setHalfYearPeriods', function(data){ console.log(data) }, param);
        }
    }
    if (obj.id == 'secondPeriodStart' || obj.id == 'secondPeriodEnd') {
        let secondPeriodStart = document.getElementById('secondPeriodStart').value;
        let secondPeriodEnd = document.getElementById('secondPeriodEnd').value;
        if (secondPeriodStart != '' && secondPeriodEnd != '') {
            param = 'secondPeriodStart='+secondPeriodStart+'&secondPeriodEnd='+secondPeriodEnd+'&period=2';
            ajax('/visit/setHalfYearPeriods', function(data){ console.log(data) }, param);
        }
    }
}

//MouseEnter handler
function mouseoverHandler(obj) {
    if (obj.className == 'fixRowHeight') {
        var text = obj.textContent,
            span = document.createElement('span');

        span.textContent = text;
        obj.innerHTML = '';
        obj.appendChild(span);

        //console.log(span.getClientRects().length);
        obj.style.setProperty('--my-height', (20*(span.getClientRects().length))+'px');
    }
}

//Обработчик кликов на странице
function clickHandler(obj)
{
    getSelectedRadio('tab')
    if (obj.id == "userControl") {
        clearInterval(timerID);
        param ='null'
        ajax('/admin/updateUserList', function(data){document.body.querySelector('#results').innerHTML =  data;},param);
    }
    
    if (obj.id == "personByPeriod")
    {
        staffHideReportElements();
        document.getElementById('personSet').classList.remove('hide');
        getStaffList();
    }

    if (obj.id == "staffWhoIsAtSchool")
    {
        staffHideReportElements();
    }
    
    if (obj.id == "staffEntranceExit")
    {
        staffHideReportElements();
        document.getElementById('staffSelectDay').classList.remove('hide');
    }
    
    if (obj.name == "showUserList")
    {
        ajax('/admin/userlist', function(data){document.body.querySelector('#results').innerHTML =  data;});
    }
    
    //Админ - Удалить пользователя
    if (obj.name == "deleteUser"){
        var param = 'iin='+obj.dataset.iin;
        ajax('/admin/deleteUser', function(data){alert('Пользователь удален'); document.body.querySelector('#results').innerHTML =  data;}, param);
    } 
    
    //Get user logs
    if (obj.name == "skdUserLogs") {
        if (getSelectedRadio('userReportType')=='userEntranceExit'){
            ajax('/skd/UserEntranceExit', function(data){document.body.querySelector('.userControl').querySelector('.results').innerHTML =  data;});
        }
        if (getSelectedRadio('userReportType')=='userLogs'){
            ajax('/skd/UserLogs', function(data){document.body.querySelector('.userControl').querySelector('.results').innerHTML =  data;});
        }
    }  
    
    //Get students logs
    if (obj.name == "getStudentsLogs")
    {
        //Добавляем класс в параметры
        var params = 'grade='+document.getElementById('grade').value + document.getElementById('litera').value;
        
        if (getSelectedRadio('reportType')=='entranceExit'){
            if (document.getElementById('selectDay').value){
                //Добавляем дату в параметры
                params += '&date='+document.getElementById('selectDay').value;
            }
            else {
              alert('Укажите дату');
              return false;
            }
            
        }

        //Добавляем тип отчета в параметры
        params += '&reportType='+ getSelectedRadio('reportType');
        if (getSelectedRadio('reportType')=='studentByPeriod'){
            sl = document.getElementById('selectStudent');
            params += '&studentID='+sl.options[sl.selectedIndex].dataset.id;
            
            if (document.getElementById('selectDayStart').value && document.getElementById('selectDayEnd').value){
                //Добавляем startDate и endDate в параметры
                params += '&startDate='+document.getElementById('selectDayStart').value;
                params += '&endDate='+document.getElementById('selectDayEnd').value;
                
            }
            else 
            {
                alert('Укажите дату');
                return false;
            }
            
        }
        ajax('/skd/getStudentsLogs', function(data){document.body.querySelector('.studentControl').querySelector('.results').innerHTML =  data;},params);
    }
    
    //Get staff logs
    if (obj.name == "getStaffLogs")
    {
        //Добавляем тип отчета в параметры
        var reportType = getSelectedRadio('staffReportType'); 
        params = 'staffReportType='+ reportType;
        
        if (reportType=='staffEntranceExit')
        {
            if (document.getElementById('staffSelectDay').value)
            {
                params += '&date='+document.getElementById('staffSelectDay').value;
                var sl = document.getElementById('selectDivision');
                if(sl.options[sl.selectedIndex].dataset.id)
                {
                    params+= '&divisionId='+sl.options[sl.selectedIndex].dataset.id;
                    ajax('/skd/getStaffLogs', function(data){document.body.querySelector('.staffControl').querySelector('.results').innerHTML =  data;},params);
                }
                else
                {
                    ajax('/skd/getStaffLogs', function(data){document.body.querySelector('.staffControl').querySelector('.results').innerHTML =  data;},params);
                }
            
                
            }
            else 
            {
                alert('Укажите дату');
                return false;
            }
        }
        
        if (reportType=='staffWhoIsAtSchool')
        {
            var sl = document.getElementById('selectDivision');
            if(sl.options[sl.selectedIndex].dataset.id)
            {
                params+= '&divisionId='+sl.options[sl.selectedIndex].dataset.id;
                ajax('/skd/getStaffLogs', function(data){document.body.querySelector('.staffControl').querySelector('.results').innerHTML =  data;},params);
            }
            else
            {
                ajax('/skd/getStaffLogs', function(data){document.body.querySelector('.staffControl').querySelector('.results').innerHTML =  data;},params);
            }
        }
        
        
        if (reportType=='personByPeriod')
        {
            var sl = document.getElementById('selectPerson');
            if (sl.innerHTML != '')
            {
                params += '&personID='+sl.options[sl.selectedIndex].dataset.id;
            }
            else
            {
                alert('Выберите подразделение!');
                return false;
            }
            
            if (document.getElementById('personSelectDayStart').value && document.getElementById('personSelectDayEnd').value){
                //Добавляем startDate и endDate в параметры
                params += '&startDate='+document.getElementById('personSelectDayStart').value;
                params += '&endDate='+document.getElementById('personSelectDayEnd').value;
                params += '&typePersonByPeriod='+getSelectedRadio('typePersonByPeriod');
                ajax('/skd/getStaffLogs', function(data){document.body.querySelector('.staffControl').querySelector('.results').innerHTML =  data;},params);
                
            }
            else 
            {
                alert('Укажите дату');
                return false;
            }
            
        }
        
        
    }
    
    //General control clicks
    /*if (obj.name == "generalControlGetData"){
        getPeopleCount();
    }*/
    if (obj.name == "generalControlGetReport"){
        params = 'option1='+getSelectedRadio('gcReportType') + '&option2=' + getSelectedRadio('gcReportType2');
        ajax('/skd/getgeneralcontrolreport', function(data){
            document.body.querySelector('.generalControl').querySelector('.results').innerHTML =data;
        },params);
    }
    
    if (obj.name == "getDumpButton"){
        let dumpForm = document.getElementById('dump');
        dumpForm.who.value = getSelectedRadio('gcReportType');
        dumpForm.where.value = getSelectedRadio('gcReportType2');
        dumpForm.submit();
    }
    
    if (obj.name == "deleteOldEntries"){
        let r = confirm("Вы уверены что хотите удалить все записи записи до " + document.getElementById('deleteOldEntriesDate').value + "?");
        if (r == true){
            params = 'date='+document.getElementById('deleteOldEntriesDate').value;
            ajax('/admin/deleteOldEntries', function(data){
               if  (data = 'NULL'){
                   alert('Запрос выполнен успешно');
               }
               else{
                   alert('Во время выполнения запроса возникла ошибка, запрос не был выполнен');
               };
            },params);
        }

    }
    
    //MTdev
    if (obj.name == "addParentContact")
    {
        //Добавляем класс в параметры
        var params = 'grade='+document.getElementById('gradeM').value + document.getElementById('literaM').value;
        
        //Добавляем тип отчета в параметры
        params += '&reportType=contactList';
        
        ajax('/skd/getStudentsLogs', function(data){
            document.body.querySelector('.addParentContact').querySelector('.results').innerHTML =  data;
        },params);
    
        //resizeWrapper('1300px');
    }
    
    //FAS clicks
    if (obj.name == "fasSeach")
    {
        var seachType = document.getElementById('fasSeachType');
        seachType = seachType.options[seachType.selectedIndex].dataset.type;
        
        if (seachType=='person') {
            param = 'person=' + document.getElementById('seachField').value;
            ajax('/fas/seach', function(data){document.getElementById('results').innerHTML = data}, param);
        }
       
        if (seachType=='invNumber') {
            param = 'invNumber=' + document.getElementById('seachField').value;
            ajax('/fas/seach', function(data){document.getElementById('results').innerHTML = data}, param);
        }
        
        if (seachType=='serialNumber') {
            
            param = 'serialNumber=' + document.getElementById('seachField').value;
            ajax('/fas/seach', function(data){document.getElementById('results').innerHTML = data}, param);
        }
        
        if (seachType=='barcode') {
            param = 'barcode=' + document.getElementById('seachField').value;
            ajax('/fas/seach', function(data){document.getElementById('results').innerHTML = data}, param);
        }
        
        if (seachType=='location') {
            param = 'location=' + document.getElementById('seachField').value;
            ajax('/fas/seach', function(data){document.getElementById('results').innerHTML = data}, param);
        }
        
        if (seachType=='fixedAsset') {
	    param = 'fixedAsset=' + encodeURIComponent(document.getElementById('seachField').value);
            ajax('/fas/seach', function(data){document.getElementById('results').innerHTML = data}, param);
        }

        resizeWrapper('1366px');
    }

    //Inventory clicks
    if (obj.name == "invSeach"){
        loadInvData();
        resizeWrapper('1366px');                
    }

    if (obj.name == "inventoryUpdate"){
        ajax('/fas/getInventoryData', function(data){document.getElementById('inventoryResults').innerHTML = data},param);
        param = 'person=';
        ajax('/fas/CheckInventoryFinish', function(invCheck){document.getElementById('inventoryFinish').disabled = invCheck}, param);
    }

    if (obj.name == "inventoryFinish"){
        let txt = "Вы уверены, что хотите завершить инвентаризацию? \nЗавершая инвентаризацию, Вы подтверждаете, что все основные средства находятся у Вас в кабинете, в Вашем распоряжении, в полной комплектности и исправном состоянии. После завершения у Вас закроется доступ на редактирование таблицы инвентаризации, а также вход в мобильное приложение \"NIS Barcode Scanner\"";
        let r = confirm(txt);
        if (r == true) {
            param='person=';
            ajax('/fas/InventoryFinish', function(data){
                if (data == true) {
                    alert('Поздравляем! Вы завершили инвентаризацию.');
                    ajax('/fas/getInventoryData', function(data){document.getElementById('inventoryResults').innerHTML = data},param);
                    obj.disabled = true;
                }
                else{
                    alert('Для завершения инвентаризации необходимо отсканировать все основные средства!');
                };
            },param);
        };
    }

    //MTdev
    if (obj.name == "cancelFas"){
        param = 'person=' + document.getElementById('invSeachField').value;
        ajax('/fas/CancelInventoryFinish', function(data){
            if (data == true) {
                alert('Завершение инвентаризации отменено.');
                obj.disabled = true;
                document.getElementById('inventoryFinishForPerson').disabled = false;
            }
            else{
                alert('Для отмены завершения инвентаризации необходимо ее завершить!');
            };
        },param);                
    }

    if (obj.name == "inventoryFinishForPerson") {
        let txt = "Вы уверены, что хотите завершить инвентаризацию? \nЗавершая инвентаризацию, Вы подтверждаете, что все основные средства находятся у сотрудника в личном распоряжении, в полной комплектности и исправном состоянии. После завершения у сотрудника закроется доступ на редактирование таблицы инвентаризации, а также вход в мобильное приложение \"NIS Barcode Scanner\"\nЗавершение инвентаризации за сотрудника производится в крайних случаях!";
        let r = confirm(txt);
        if (r == true) {
            let person = document.getElementById('invSeachField').value;
            param = 'person='+person;
            ajax('/fas/InventoryFinish', function(data){
                if (data == true) {
                    alert('Инвентаризация сотрудника '+person+' завершена.');
                    obj.disabled = true;
                    document.getElementById('cancelFas').disabled = false;
                }
                else{
                    alert('Для завершения инвентаризации сотрудника необходимо отсканировать все его основные средства!');
                };
            },param);
        };
    }

    if (obj.name == "invSaveChanges"){
        let newOwner = document.getElementById('invChangeOwner').value;
        
        if (isStorage.inventoryPeople.indexOf(newOwner) != -1){
            let params = 'invNumber=' + isStorage.invCurrentInventoryNum;
            params += '&newOwner=' + newOwner;
            ajax('/fas/invChangeOwner', function(data){
                loadInvData();}, 
                params);
            document.getElementById('dialogWindowBackground').style.display = 'none';;
        } else{
            alert('Сотрудник не найден');
        }
    }

    if (obj.classList.contains('invLink')){
        let dialogWindowBackground = document.getElementById('dialogWindowBackground');
        dialogWindowBackground.style.display = 'block';
        isStorage.invCurrentInventoryNum = obj.innerText;
    }
    
    if (obj.id == 'closeDialogWindow'){
        let dialogWindowBackground = document.getElementById('dialogWindowBackground');
        dialogWindowBackground.style.display = 'none';
    }
    if (obj.id == 'transferAssets'){
        let invTransmittingPerson = document.getElementById('invTransmittingPerson').value;
        let invReceivingPerson = document.getElementById('invReceivingPerson').value;
        let r = confirm("Вы уверены что хотите выполнить передачу основных средств?");
        if (r == true) {
            let param = 'invTransmittingPerson=' + invTransmittingPerson + '&invReceivingPerson=' + invReceivingPerson;
            ajax('/fas/invTransmitAssets', function(data){
                if (data=='1'){
                    document.getElementById('invResults').innerHTML ='';
                    alert('Передача ОС выполнена успешно');   
                }else{
                    alert('При выполнении запроса возникла ошибка');
                }
            }, param);
        }
        
    }
    
    //Barcode
    if (obj.classList.contains('invCheckbox')){
        let r = confirm("Вы уверены что хотите изменить статус?");
        if (r == false) {
            obj.checked = !obj.checked;
        }else{
            param = 'id=' + obj.value + '&status='+obj.checked;
            ajax('/fas/invChangeScannedStatus', function(data){}, param);
        }
    }
    
    //Admin clicks
    if (obj.name == "adminAddRole") {
        var newRole = document.getElementById('newRole');
        if (newRole.value.length == 0) {
            return false;
        }
        var param = 'role=' + newRole.value;
        ajax('/admin/addRole', function(data){alert(data); getRoles();}, param);
        newRole.value ='';
    }
    
    if (obj.id == "roleSettings") {
        getRoles();
    }
    
    if (obj.name == "adminDeleteRole") {
        var sl = document.getElementById('roleList');
        var param = 'id=' + sl.options[sl.selectedIndex].dataset.id;

        var cbs = document.getElementsByClassName('perms');
        for(var i = 0; i < cbs.length; i++) {
            cbs[i].checked = false;
        }

        ajax('/admin/deleteRole', function(data){alert(data); getRoles();}, param);
    }
    
    //inventory control
    if (obj.id == "getInvExport"){
        let invExportForm = document.getElementById('invExportForm');
        invExportForm.submit();
    }

    if (obj.id == "startInventory"){
        let param = {};
        let r = confirm("Вы уверены что хотите начать новую инвентаризацию? ВСЕ ТАБЛИЦЫ ПРЕДЫДУЩЕЙ ИНВЕНТАРИЗАЦИИ ОЧИСТЯТСЯ!");
        if (r == true) {
            ajax('/fas/startInventory', function(data){
                if(data =='1'){
                    alert('Вы начали новую инвентаризацию! Вкладка Инвентаризация доступна пользователям.');
                }
            }, param);
        }
        
    }

    if (obj.id == "stopInventory"){
        let param = {};
        let r = confirm("Вы уверены что хотите завершить инвентаризацию? В этом случае никто не сможет проводить инвентаризацию вплоть до начала новой.");
        if (r == true){
            ajax('/fas/stopInventory', function(data){
                if(data =='1'){
                alert('Вы завершили инвентаризацию! ');
            }}, param);
        }
    }

    //visiting clicks
    if (obj.name == "addVisit"){
        let person = document.getElementById('personForVisit');
        let date = document.getElementById('visitSelectDay');
        let lesson = document.getElementById('visitSelectLesson');

        if (person.value != '') {
            let param = 'visitDate='+date.value+'&whoWasVisited='+person.value+'&lessonNum='+lesson.value;
            ajax('/visit/addVisit', function(data){
                if (data == 'me') { alert("Невозможно добавить в график указанного учителя."); }
                else if (data == '') { alert("Указанный преподаватель не найден в базе данных."); }
                else { document.body.querySelector('.myVisits').querySelector('.visitResults').innerHTML = data; }
            }, param);
            ajax('/visit/sendEmailNotification', function(data){ console.log(data); }, param);
            person.value = '';
        } else {
            alert('Преподаватель не выбран!');
        }
    }

    if (obj.name == "saveResults" || obj.name == "saveAResults") {
        saveVisitResults();
        closePattern();
    }

    if (obj.name == "saveLSO") {
        saveLSO();
        closePattern();
    }

    if (obj.name == "closePattern") {
        closePattern();
    }

    if (obj.name == "deleteResults") {
        let q = confirm('Данные будут удалены безвозвратно!');
        if (q) {
            let row = obj.parentNode.parentNode;
            let param = 'rowId='+row.dataset.rowId+'&mode='+(row.offsetParent.className=='visitResults'?'standart':'attestation');
            ajax('/visit/deleteVisit', function(data){
                if (row.offsetParent.className=='visitResults') {
                    document.body.querySelector('.myVisits').querySelector('.visitResults').innerHTML = data;
                } else {
                    document.body.querySelector('.myAVisits').querySelector('.visitAResults').innerHTML = data;
                }
            }, param);
            showNotification('Запись о посещении удалена.');
        }
    }
    
    if (obj.name == 'saveToPDF') {
        var prevRow = obj.parentNode.parentNode;
        var dumpForm = document.getElementById('dumpVisitResults');
        dumpForm.rowId.value = prevRow.dataset.rowId;
        dumpForm.focus.value = prevRow.dataset.focus;
        if (prevRow.offsetParent.className=='lsoTable') {
            if (prevRow.dataset.period != 0) { dumpForm.mode.value = prevRow.dataset.period; }
            else { alert('Невозможно выгрузить ЛШО. Не указаны периоды для полугодии.'); }
        } else {
            dumpForm.mode.value = prevRow.offsetParent.className=='visitResults'?'standart':'attestation';
        }
        if (dumpForm.mode.value != '') { dumpForm.submit(); }
    }
    if (obj.name == 'saveToPDFforRSh') {
        var prevRow = obj.parentNode.parentNode;
        var dumpForm = document.getElementById('dumpVisitResults');
        dumpForm.rowId.value = prevRow.dataset.rowId;
        dumpForm.focus.value = prevRow.dataset.focus;
        dumpForm.mode.value = 'standart';
        dumpForm.submit();
    }
    
    if (obj.parentElement.localName == 'tr') { // нажатие на строку таблицы с посещениями
        if (obj.localName != 'th' && obj.parentElement.className == 'allowed') { // проверка на доступность для редактирования
            let row = obj.parentNode;
            let className = row.offsetParent.parentElement.className;
            if (obj.offsetParent.className == 'visitResults') {
                let param = 'rowId='+row.dataset.rowId+'&className='+className;
                ajax('/visit/getVisitResults', function(data){ // получение из БД данных о посещении
                    if (data.indexOf('content-login') >= 0) { location.reload(true) };
                    if (document.getElementById('tempRow')) {
                        let tempRow = document.getElementById('tempRow');
                        if (className == 'myVisits') {
                            let tempRowId = tempRow.parentNode.children[tempRow.rowIndex-1].dataset.rowId;
                            // если повторно нажали на строку, то
                            saveVisitResults(); // происходит сохранение данных
                            closePattern(); // закрытие шаблона
                            if (tempRowId == row.dataset.rowId) { return; }
                        } else {
                            // тоже самое, но с моими листами оценки
                            if (tempRow.parentNode.children[tempRow.rowIndex-1].dataset.rowId == row.dataset.rowId) { 
                                closePattern();
                                return; }
                            else { closePattern(); }
                        }
                    }
                    let table = document.body.querySelector('.'+className).querySelector('.visitResults');  //
                    let new_tr = table.insertRow(row.rowIndex+1);                                           //
                    new_tr.id = "tempRow";                                                                  //
                    let cell = new_tr.insertCell(0);                                                        // открытие шаблона для заполнения
                    cell.colSpan = 6;                                                                       //
                    cell.id = 'subject_review';                                                             //
                    cell.innerHTML = data;                                                                  //
                }, param);
            }
            // тот же алгорит, что выше, но с листами наблюдения для аттестации
            if (obj.offsetParent.className == 'visitAResults') {
                let param = 'rowId='+row.dataset.rowId+'&className='+className;
                ajax('/visit/getAttestationVisitResults', function(data){
                    if (data.indexOf('content-login') >= 0) { location.reload(true) };
                    if (document.getElementById('tempRow')) {
                        let tempRow = document.getElementById('tempRow');
                        if (className == 'myAVisits') {
                            let tempRowId = tempRow.parentNode.children[tempRow.rowIndex-1].dataset.rowId;
                            saveVisitResults();
                            closePattern();
                            if (tempRowId == row.dataset.rowId) { return; }
                        } else {
                            if (tempRow.parentNode.children[tempRow.rowIndex-1].dataset.rowId == row.dataset.rowId) { 
                                closePattern();
                                return; }
                            else { closePattern(); }
                        }
                    }
                    let table = document.body.querySelector('.'+className).querySelector('.visitAResults');
                    let new_tr = table.insertRow(row.rowIndex+1);
                    new_tr.id = "tempRow";
                    let cell = new_tr.insertCell(0);
                    cell.colSpan = 6;
                    cell.id = 'a_subject_review'
                    cell.innerHTML = data;
                }, param);
            }
            // тот же алгорит, что выше, но с листами школьного оценивания
            if (className == 'LSO') {
                if (row.dataset.period != 0) {
                    let param = 'rowId='+row.dataset.rowId+'&period='+row.dataset.period;
                    ajax('/visit/getLSOResults', function(data){
                        if (data.indexOf('content-login') >= 0) { location.reload(true) };
                        if (document.getElementById('tempRow')) {
                            let tempRow = document.getElementById('tempRow');
                            let tempRowId = tempRow.parentNode.children[tempRow.rowIndex-1].dataset.rowId;
                            saveLSO();
                            closePattern();
                            if (tempRowId == row.dataset.rowId) { return; }
                        }
                        let table = document.body.querySelector('.'+className).querySelector('.lsoTable');
                        let new_tr = table.insertRow(row.rowIndex+1);
                        new_tr.id = "tempRow";
                        let cell = new_tr.insertCell(0);
                        cell.colSpan = 8;
                        cell.id = 'lso_review'
                        cell.innerHTML = data;
                    }, param);
                } else { alert('Невозможно сформировать ЛШО. Не указаны периоды для полугодии.'); }
            }
        }
    }

    if (obj.name == 'confirmResults' || obj.name == 'confirmAResults') {
        let tempRow = document.getElementById('tempRow');
        let prevRow = tempRow.parentNode.children[tempRow.rowIndex-1];
        let params = 'rowId='+prevRow.dataset.rowId + (obj.name == 'confirmResults' ? '&mode=standart' : '&mode=attestation');
        ajax('/visit/checkEvaluates', function(data){
            if (data != 'none') {
                let q = confirm('Убедитесь в корректности заполненных данных. После подтверждения редактирование данных невозможно!');
                if (q) {
                    obj.style = "";
                    obj.disabled = true;
                    //let mode = obj.name == 'confirmResults' ? 5 : 4;
                    if (obj.id == 'watcherSide' && obj.name == 'confirmResults') { prevRow.children[5].children[1].disabled = true; }
                    
                    let params = 'rowId='+prevRow.dataset.rowId+(obj.id=='watcherSide'?'&side=watcher':'&side=presenter')+(obj.name=='confirmResults'?'&mode=standart':'&mode=attestation');
                    ajax('/visit/setConfirmation', function(){}, params);

                    ajax('/visit/checkEvaluates', function(data){
                        //let mode = obj.name == 'confirmResults' ? 4 : 3;
                        switch (data) {
                            case 'confirmed':
                                prevRow.children[4].lastChild.innerText = 'Подтверждено';
                                prevRow.children[4].lastChild.className = 'status confirmed'
                                break;
                            case 'half-confirmed':
                                prevRow.children[4].lastChild.innerText = 'На подтверждении';
                                prevRow.children[4].lastChild.className = 'status on_confirmation';
                                break;
                            case 'non-confirmed':
                                prevRow.children[4].lastChild.innerText = 'Ожидает подтверждения';
                                prevRow.children[4].lastChild.className = 'status on_waiting';
                                break;
                            default:
                                prevRow.children[4].lastChild.innerText = 'На оценивании';
                                prevRow.children[4].lastChild.className = 'status on_evaluating';
                                break;
                        }
                    }, params);
                    
                    closePattern();
                }
            } else {
                alert("Данные заполнены не полностью!");
            }
        }, params);
    }

    if (obj.id == 'reportDetails' || obj.id == 'reportDetailsLabel') {
        if (obj.checked) {
            document.getElementById('detailsDateLabel').classList.remove('hide');
            document.getElementById('detailsDate').classList.remove('hide');
        } else {
            document.getElementById('detailsDateLabel').classList.add('hide');
            document.getElementById('detailsDate').classList.add('hide');
        }
    }
    if (obj.id == 'detailsDate' || obj.id == 'detailsDateLabel') {
        if (obj.checked) {
            document.getElementById('detailsDateField').classList.remove('hide');
        } else {
            document.getElementById('detailsDateField').classList.add('hide');
        }
    }
    if (obj.id == 'reportDetailsA' || obj.id == 'reportDetailsLabelA') {
        if (obj.checked) {
            document.getElementById('detailsDateLabelA').classList.remove('hide');
            document.getElementById('detailsDateA').classList.remove('hide');
        } else {
            document.getElementById('detailsDateLabelA').classList.add('hide');
            document.getElementById('detailsDateA').classList.add('hide');
        }
    }
    if (obj.id == 'detailsDateA' || obj.id == 'detailsDateLabelA') {
        if (obj.checked) {
            document.getElementById('detailsDateFieldA').classList.remove('hide');
        } else {
            document.getElementById('detailsDateFieldA').classList.add('hide');
        }
    }

    if (obj.name == "showVisitReport") {
        if (obj.id == 'report0') {
            if (document.getElementById('personForReport').value != '') {
                let now = new Date();
                if (now.getMonth() < 9) { var start = (now.getFullYear()-1)+'-09-01'; }
                else { var start = (now.getFullYear())+'-09-01'; }
                let params = 'teacher='+document.getElementById('personForReport').value+'&visitType='+getSelectedRadio('visitType')+
                             '&details='+(document.getElementById('reportDetails').checked ? '1' : '0')+
                             '&detailsDate='+(document.getElementById('detailsDate').checked ? '1' : '0')+
                             '&date='+document.getElementById('detailsDateField').value+
                             '&dateStart='+start+'&dateEnd='+getInputDate();
                ajax('/visit/getPersonalVisits', function(data){
                    document.body.querySelector('.Reports').querySelector('.results').innerHTML = data;
                }, params);
            } else {
                alert("Учитель не выбран!");
            }
        }
        if (obj.id == 'report3') {
            let params = 'visitPeriodStart='+document.getElementById('visitSelectStartDay').value+'&visitPeriodEnd='+document.getElementById('visitSelectEndDay').value;;
            ajax('/visit/getAllVisits', function(data){
                document.body.querySelector('.Reports').querySelector('.results').innerHTML = data;
            }, params);
        }
        if (obj.id == 'report4') {
            let params = 'visitPeriodStart='+document.getElementById('AvisitSelectStartDay').value+'&visitPeriodEnd='+document.getElementById('AvisitSelectEndDay').value;
            ajax('/visit/getAllAttestationVisits', function(data){
                document.body.querySelector('.AReports').querySelector('.results').innerHTML = data;
            }, params);
        }
        if (obj.id == 'report5') {
            if (document.getElementById('personForAReport').value != '') {
                let now = new Date();
                if (now.getMonth() < 9) { var start = (now.getFullYear()-1)+'-09-01'; }
                else { var start = (now.getFullYear())+'-09-01'; }
                let params = 'teacher='+document.getElementById('personForAReport').value+'&visitType='+getSelectedRadio('AvisitType')+
                             '&details='+(document.getElementById('reportDetailsA').checked ? '1' : '0')+
                             '&detailsDate='+(document.getElementById('detailsDateA').checked ? '1' : '0')+
                             '&date='+document.getElementById('detailsDateFieldA').value+
                             '&dateStart='+start+'&dateEnd='+getInputDate();
                console.log(params);
                ajax('/visit/getPersonalAttestationVisits', function(data){
                    document.body.querySelector('.AReports').querySelector('.results').innerHTML = data;
                }, params);
            } else {
                alert("Учитель не выбран!");
            }
        }
    }

    if (obj.id == 'showExistedVisits') {
        let now = new Date();
        if (now.getMonth() < 9) { var start = (now.getFullYear()-1)+'-09-01'; }
        else { var start = (now.getFullYear())+'-09-01'; }
        let params = 'teacher='+document.getElementById('personForVisit').value+'&visitType=WhoWasVisited'+
                     '&details=1'+'&detailsDate=1'+'&date='+document.getElementById('visitSelectDay').value;
        ajax('/visit/getPersonalVisits', function(data){
            document.body.querySelector('.myVisits').querySelector('.results').innerHTML = data;
        }, params);
    }

    if (obj.name == 'saveVisitReports') {
        let dumpForm = document.getElementById('dumpVisitReports');
        if (obj.id == 'report1') {
            if (document.getElementById('personForReport').value != '') {
                dumpForm.whoWasVisited.value = document.getElementById('personForReport').value;
            } else {
                alert("Учитель не выбран!");
                return;
            } 
        }
        if (obj.id == 'report2') {
            dumpForm.whoWasVisited.value = '';
            switch (getSelectedRadio('periodForReports')) {
                case 'forDay':
                    dumpForm.params.value = document.getElementById('reportSelectDay').value;
                    break;
                case 'forMonth':
                    dumpForm.params.value = document.getElementById('reportSelectMonth').value;
                    break;
                case 'forPeriod':
                    dumpForm.params.value = document.getElementById('reportSelectStartDay').value+'|'+document.getElementById('reportSelectEndDay').value;
                    break;
                case 'forAllTime':
                    dumpForm.params.value = '';
                    break;
            }
            dumpForm.mode.value = getSelectedRadio('periodForReports');
        }
        dumpForm.submit();
    }

    /*if (obj.id == 'showPersonManagements') {
        let person = document.getElementById('personForManagement');
        let param = 'person='+person.value;
        ajax('/visit/managePersonPurpose', function(data){
            if (data != 'empty') {
                document.getElementById('teachersPurpose').value = data;                    
            } else {
                alert('Нет заданных целей профессионального развития для выбранного учителя.');
            }
        }, param);
    }*/

    if (obj.id == 'saveSynods') {
        let person = document.getElementById('personForAManagement');
        let p_date_from = document.getElementById('pAttestationDateFrom');
        let p_date_to = document.getElementById('pAttestationDateTo');
        let planning = document.getElementById('personForAPlannig');
        let t_date_from = document.getElementById('tAttestationDateFrom');
        let t_date_to = document.getElementById('tAttestationDateTo');
        let teaching = document.getElementById('personForATeaching');
        let e_date_from = document.getElementById('eAttestationDateFrom');
        let e_date_to = document.getElementById('eAttestationDateTo');
        let evaluating = document.getElementById('personForAEvaluating');
        let c_date_from = document.getElementById('cAttestationDateFrom');
        let c_date_to = document.getElementById('cAttestationDateTo');
        let complex = document.getElementById('personForAComplex');
        let id = document.getElementById('selectTeacherA');

        if (person.value != '' && planning.value != '' && teaching.value != '' && evaluating.value != '' && complex.value != '' &&
            p_date_from.value != '' && p_date_to.value != '' && t_date_to.value != '' && t_date_to.value != '' &&
            e_date_to.value != '' && e_date_to.value != '' && c_date_to.value != '' && c_date_to.value != '') {
            let param = 'person='+person.value+
                        '&p_date_from='+p_date_from.value+'&p_date_to='+p_date_to.value+'&p_person='+planning.value+
                        '&t_date_from='+t_date_from.value+'&t_date_to='+t_date_to.value+'&t_person='+teaching.value+
                        '&e_date_from='+e_date_from.value+'&e_date_to='+e_date_to.value+'&e_person='+evaluating.value+
                        '&c_date_from='+c_date_from.value+'&c_date_to='+c_date_to.value+'&c_person='+complex.value;
            let notif = 'Сохранено. Уведомление отправлено на почту.';
            if (id.selectedOptions.length != 0) {
                param = param+'&id='+id.selectedOptions[0].dataset.oid;
                notif = 'Сохранено.';
            }
            ajax('/visit/saveSynod', function(data){ /*console.log(data);*/ showNotification(notif); }, param);
            if (id.selectedOptions.length == 0) { ajax('/visit/sendEmailNotificationA', function(data){}, param) };
        } else {
            if (person.value == '') { alert('Не выбран преподаватель для посещения'); return; }
            if (planning.value == '') { alert('Не выбран наблюдатель в фокусе "Планирование"'); return; }
            if (teaching.value == '') { alert('Не выбран наблюдатель в фокусе "Преподавание"'); return; }
            if (evaluating.value == '') { alert('Не выбран наблюдатель в фокусе "Оценивание учебных достижений"'); return; }
            if (complex.value == '') { alert('Не выбран наблюдатель для комплексного анализа урока'); return; }
            if (p_date_from.value == '' || p_date_to.value == '') { alert('Не указан период посещения в фокусе "Планирование"'); return; }
            if (t_date_from.value == '' || t_date_to.value == '') { alert('Не указан период посещения в фокусе "Преподавание"'); return; }
            if (e_date_from.value == '' || e_date_to.value == '') { alert('Не указан период посещения в фокусе "Оценивание учебных достижений"'); return; }
            if (c_date_from.value == '' || c_date_to.value == '') { alert('Не указан период посещения для комплексного анализа урока'); return; }
        }
    }

    /////////

    //change visit language
    if (obj.parentElement.className == 'language-panel') {
        let matches = document.cookie.match(new RegExp("(?:^|; )" + 'lang'.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"));
        let lang = matches ? decodeURIComponent(matches[1]) : undefined;
        if (obj.id == 'rus-lang' && lang != 'ru') {
            document.cookie = "lang=ru;samesite=strict";
        }
        if (obj.id == 'kaz-lang' && lang != 'kz') {
            document.cookie = "lang=kz;samesite=strict";
        }
        var subject_review = document.getElementById('subject_review');
        if (subject_review) {
            let className = subject_review.offsetParent.parentElement.className;
            let rowId = subject_review.offsetParent.children[1].children[subject_review.parentElement.rowIndex-1].dataset.rowId;
            let param = 'rowId='+rowId+'&className='+className;
            
            ajax('/visit/getVisitResults', function(data){
                if (data.indexOf('content-login') >= 0) { location.reload(true); return false; };
                subject_review.innerHTML = data;
            }, param);
        }
        var a_subject_review = document.getElementById('a_subject_review');
        if (a_subject_review) {
            let className = a_subject_review.offsetParent.parentElement.className;
            let rowId = a_subject_review.offsetParent.children[1].children[a_subject_review.parentElement.rowIndex-1].dataset.rowId;
            let param = 'rowId='+rowId+'&className='+className;
            
            ajax('/visit/getAttestationVisitResults', function(data){
                if (data.indexOf('content-login') >= 0) { location.reload(true); return false; };
                a_subject_review.innerHTML = data;
            }, param);
        }
    }

    if (obj.parentNode.id == 'personForVisitautocomplete-list') {
        let param = 'person='+obj.innerText+'&date='+document.getElementById('visitSelectDay').value; 
        ajax('/visit/getVisitCount', function(data){
            let msg = document.getElementById('visitInfo');
            if (data != '0') {
                msg.classList.remove('hide');
            } else {
                msg.classList.add('hide');
                document.body.querySelector('.myVisits').querySelector('.results').innerHTML = '';
            }
        }, param);
    }

    if (obj.name == 'searchLSO') {
        var param = 'person='+document.getElementById('personForLso').value+'&period='+getSelectedRadio('period');
        ajax('/visit/getLSOSearchResults', function(data){
            //console.log(param);
            document.body.querySelector('.LSO').querySelector('.lsoTable').innerHTML = data;
        }, param);
    }

    if (obj.id == 'savePurpose') {
        let id = document.getElementById('selectTeacher');
        let purpose = document.getElementById('teachersPurpose');
        let cur_level = document.getElementById('selectCurLevel');
        let up_level = document.getElementById('selectUpLevel');
        let param = 'id='+id.selectedOptions[0].dataset.oid+'&person='+id.selectedOptions[0].text+'&purpose='+purpose.value+'&cur_level='+cur_level.value+'&up_level='+up_level.value+'&mode=old';
        ajax('/visit/savePersonPurpose', function(data){
            showNotification('Цель и уровни сохранены');
        }, param);
    }
    
    if (obj.parentElement.id == "selectTeacher")
    {
        var param = 'id='+obj.dataset.oid;
        ajax('/visit/managePersonPurpose', function(data){
            if (data != 'empty') {
                var purposes = data.split(';');
                document.getElementById('teachersPurpose').value = purposes[0];
                document.getElementById('selectCurLevel').value = purposes[1];
                document.getElementById('selectUpLevel').value = purposes[2];
            } else {
                alert('Нет заданных целей профессионального развития для выбранного учителя.');
            }
        }, param);
    }

    if (obj.parentElement.id == "selectTeacherA")
    {
        var param = 'person='+obj.value;
        ajax('/visit/getSynod', function(data){
            document.getElementById('saveSynods').innerText = 'Сохранить';
            document.getElementById('personForAManagement').value = obj.value;
            var arr = data.split('|');
            //console.log(arr);
            for (let i = 0; i < 19; i=i+5) {
                switch (arr[i]) {
                    case 'planning':
                        document.getElementById('pAttestationDateFrom').value = arr[i+1];
                        document.getElementById('pAttestationDateTo').value = arr[i+2];
                        document.getElementById('personForAPlannig').value = arr[i+3];
                        //document.getElementById('personForAPlannig').dataset.iin = arr[i+4];
                        break;
                    case 'teaching':
                        document.getElementById('tAttestationDateFrom').value = arr[i+1];
                        document.getElementById('tAttestationDateTo').value = arr[i+2];
                        document.getElementById('personForATeaching').value = arr[i+3];
                        //document.getElementById('personForATeaching').dataset.iin = arr[i+4];
                        break;
                    case 'evaluating':
                        document.getElementById('eAttestationDateFrom').value = arr[i+1];
                        document.getElementById('eAttestationDateTo').value = arr[i+2];
                        document.getElementById('personForAEvaluating').value = arr[i+3];
                        //document.getElementById('personForAEvaluating').dataset.iin = arr[i+4];
                        break;
                    case 'complex':
                        document.getElementById('cAttestationDateFrom').value = arr[i+1];
                        document.getElementById('cAttestationDateTo').value = arr[i+2]; 
                        document.getElementById('personForAComplex').value = arr[i+3];
                        //document.getElementById('personForAComplex').dataset.iin = arr[i+4];
                        break;
                    default:
                        break;
                }
            }
        }, param);
    }

    if (obj.id == 'addNewTeacher') {
        var person = document.getElementById('personForManagement');
        var param = 'person='+person.value+'&mode=new';
        ajax('/visit/savePersonPurpose', function(data){
            ajax('/visit/getAllSavedTeachers', function(data){
                let selList = document.getElementsByName("teachersList");
                selList.forEach(element => {
                    element.innerHTML = data; 
                });
                person.value = '';
            }, 'person='+person.value);
            showNotification('Преподаватель добавлен в список');
        }, param);
    }

    if (obj.id == 'deleteTeacher') {
        let person = document.getElementById('selectTeacher');
        let param = 'id='+person.selectedOptions[0].dataset.oid;
        ajax('/visit/deletePersonPurpose', function(data){
            ajax('/visit/getAllSavedTeachers', function(data){
                let selList = document.getElementsByName("teachersList");
                selList.forEach(element => {
                    element.innerHTML = data; 
                });
            }, '');
            showNotification('Преподаватель удалён из списка');
        }, param);
    }

    if (obj.name == 'periodForReports') {
        switch (getSelectedRadio('periodForReports')) {
            case 'forDay':
                document.getElementById('reportForDay').style = "display: block;"
                document.getElementById('reportForMonth').style = "display: none;"
                document.getElementById('reportForPeriod').style = "display: none;"
                break;
            case 'forMonth':
                document.getElementById('reportForDay').style = "display: none;"
                document.getElementById('reportForMonth').style = "display: block;"
                document.getElementById('reportForPeriod').style = "display: none;"
                break;
            case 'forPeriod':
                document.getElementById('reportForDay').style = "display: none;"
                document.getElementById('reportForMonth').style = "display: none;"
                document.getElementById('reportForPeriod').style = "display: block;"
                break;
            case 'forAllTime':
                document.getElementById('reportForDay').style = "display: none;"
                document.getElementById('reportForMonth').style = "display: none;"
                document.getElementById('reportForPeriod').style = "display: none;"
                break;
        }
    }
}

function showPermissions() {
    alert();
}

function staffHideReportElements() {
    document.getElementById('staffSelectDay').classList.add('hide');
    document.getElementById('personSet').classList.add('hide');
    document.body.querySelector('.staffControl').querySelector('.results').innerHTML ="";
}

function hideReportElements() {
    document.getElementById('selectDay').classList.add('hide');
    document.getElementById('studentSet').classList.add('hide');
    //document.getElementById('selectStudent').classList.add('hide');
    //document.getElementById('studentByPeriodcal').classList.add('hide');
    document.body.querySelector('.studentControl').querySelector('.results').innerHTML ="";
}

function getInputDate(offset){
    offset = offset || 0;
    var date = new Date();
    date.setDate(date.getDate() + offset);
    return date.toISOString().substring(0, 10);
}

function saveVisitResults(){
    let tempRow = document.getElementById('tempRow');
    let prevRow = tempRow.parentNode.children[tempRow.rowIndex-1];
    if (tempRow.parentElement.parentElement.parentElement.className == 'myVisits') {
        let subject = document.getElementById('visitSubject').value;
        let topic = document.getElementById('visitTopic').value;
        let grade = document.getElementById('visitGrade').value+document.getElementById('visitLitera').value;
        let recommendation = document.getElementById('visitRecommendation').value;
        let purpose_review = document.getElementById('visitPurposeRecommendation').value;
        let mkgroup = tempRow.querySelectorAll('.selectEvaluation');
        let marks = '';
        mkgroup.forEach(element => {
            marks += element.selectedIndex;
        });
        let param = 'rowId='+prevRow.dataset.rowId+
                    '&subject='+subject+'&topic='+topic+'&grade='+grade+
                    '&recommendation='+recommendation+'&marks='+marks+
                    '&purpose_review='+purpose_review;
        ajax('/visit/setVisitResults', function(data){ console.log(data) }, param);
        param = 'rowId='+prevRow.dataset.rowId+'&mode=standart';
        ajax('/visit/checkEvaluates', function(data){
            switch (data) {
                case 'confirmed':
                    prevRow.children[4].lastChild.innerText = 'Подтверждено';
                    prevRow.children[4].lastChild.className = 'status confirmed'
                    break;
                case 'half-confirmed':
                    prevRow.children[4].lastChild.innerText = 'На подтверждении';
                    prevRow.children[4].lastChild.className = 'status on_confirmation';
                    break;
                case 'non-confirmed':
                    prevRow.children[4].lastChild.innerText = 'Ожидает подтверждения';
                    prevRow.children[4].lastChild.className = 'status on_waiting';
                    break;
                default:
                    prevRow.children[4].lastChild.innerText = 'На оценивании';
                    prevRow.children[4].lastChild.className = 'status on_evaluating';
                    break;
            }
        }, param);
    }
    if (tempRow.parentElement.parentElement.parentElement.className == 'myAVisits') {
        let date = document.getElementById('visitDate').value;
        let subject = document.getElementById('visitSubject').value;
        let topic = document.getElementById('visitTopic').value;
        let grade = document.getElementById('visitGrade').value+document.getElementById('visitLitera').value;
        let lesson_review = document.getElementById('visitLessonReview').value;
        let purpose_review = document.getElementById('visitPurposeReview').value;
        let mkgroup = tempRow.querySelectorAll('.custom-checkbox-input');
        let marks = '';
        mkgroup.forEach(element => {
            if (element.checked) { marks += '1' } else { marks += '0' };
        });
        let param = 'rowId='+prevRow.dataset.rowId+'&visitDate='+date+
                    '&subject='+subject+'&topic='+topic+'&grade='+grade+'&marks='+marks+
                    '&lesson_review='+lesson_review+'&purpose_review='+purpose_review;
        ajax('/visit/setAttestationVisitResults', function(){}, param);
        param = 'rowId='+prevRow.dataset.rowId+'&mode=attestation';
        ajax('/visit/checkEvaluates', function(data){
            switch (data) {
                case 'confirmed':
                    prevRow.children[4].lastChild.innerText = 'Подтверждено';
                    prevRow.children[4].lastChild.className = 'status confirmed'
                    break;
                case 'half-confirmed':
                    prevRow.children[4].lastChild.innerText = 'На подтверждении';
                    prevRow.children[4].lastChild.className = 'status on_confirmation';
                    break;
                case 'non-confirmed':
                    prevRow.children[4].lastChild.innerText = 'Ожидает подтверждения';
                    prevRow.children[4].lastChild.className = 'status on_waiting';
                    break;
                default:
                    prevRow.children[4].lastChild.innerText = 'На оценивании';
                    prevRow.children[4].lastChild.className = 'status on_evaluating';
                    break;
            }
        }, param);
    }
}

function saveLSO(){
    let tempRow = document.getElementById('tempRow');
    let prevRow = tempRow.parentNode.children[tempRow.rowIndex-1];
    let job = document.getElementById('LSO_job').value;
    let param = 'rowId='+prevRow.dataset.rowId+'&period='+prevRow.dataset.period;
    if (prevRow.dataset.period == 1) {
        var summary = document.getElementById('lso1summary').value;
        var correction = document.getElementById('lso1correction').value;
        param = param+'&job='+job+'&summary='+summary+'&correction='+correction;
    } else if (prevRow.dataset.period == 2) {
        var summary = document.getElementById('lso2summary').value;
        var correction = document.getElementById('lso2correction').value;
        var comment = document.getElementById('lso2comment').value;
        var recommendation = document.getElementById('lso2recommendation').value;
        var q = document.getElementsByName('visitAnswers');
        var a = document.getElementsByName('visitAnswerCount');
        var cnt = 0;
        q.forEach(e => { if (e.value == '') { cnt++ } });
        param = param+'&job='+job+'&summary='+summary+'&correction='+correction+'&comment='+comment+'&recommendation='+recommendation;
        if (cnt != 60) {
            param = param+'&q1='+q[0].value+'|'+q[1].value+'|'+q[2].value+'|'+q[3].value+'|'+q[4].value;
            param = param+'&q2='+q[5].value+'|'+q[6].value+'|'+q[7].value+'|'+q[8].value+'|'+q[9].value;
            param = param+'&q3='+q[10].value+'|'+q[11].value+'|'+q[12].value+'|'+q[13].value+'|'+q[14].value;
            param = param+'&q4='+q[15].value+'|'+q[16].value+'|'+q[17].value+'|'+q[18].value+'|'+q[19].value+'|'+a[0].value;
            param = param+'&q5='+q[20].value+'|'+q[21].value+'|'+q[22].value+'|'+q[23].value+'|'+q[24].value;
            param = param+'&q6='+q[25].value+'|'+q[26].value+'|'+q[27].value+'|'+q[28].value+'|'+q[29].value;
            param = param+'&q7='+q[30].value+'|'+q[31].value+'|'+q[32].value+'|'+q[33].value+'|'+q[34].value;
            param = param+'&q8='+q[35].value+'|'+q[36].value+'|'+q[37].value+'|'+q[38].value+'|'+q[39].value+'|'+a[1].value;
            param = param+'&q9='+q[40].value+'|'+q[41].value+'|'+q[42].value+'|'+q[43].value+'|'+q[44].value;
            param = param+'&q10='+q[45].value+'|'+q[46].value+'|'+q[47].value+'|'+q[48].value+'|'+q[49].value;
            param = param+'&q11='+q[50].value+'|'+q[51].value+'|'+q[52].value+'|'+q[53].value+'|'+q[54].value;
            param = param+'&q12='+q[55].value+'|'+q[56].value+'|'+q[57].value+'|'+q[58].value+'|'+q[59].value+'|'+a[2].value;
        }
        //console.log(param);
    }
    ajax('/visit/saveLSO', function(data){ /*console.log(data);*/ }, param);
}

function closePattern() {
    var elem = document.getElementById('tempRow');
    elem.remove();
}

function getRoles() {
    ajax('/admin/getRoles', function(data){
        var roleList = document.getElementById('roleList');
        roleList.innerHTML = data;
    }, []);

}

//Check selected elemenent in Radio
function getSelectedRadio(radioElem)
{
   var m = document.getElementsByName(radioElem);
        for (var i = 0; i < m.length; i++)
        {
            if (m[i].checked)
            {
                return m[i].value;
            }
        } 
}

function getStaffList()
{
    var sl = document.getElementById('selectDivision');
    var params = 'divisionId='+sl.options[sl.selectedIndex].dataset.id;
    ajax('/skd/getStaffList', function(data){
        document.getElementById('selectPerson').innerHTML=data;
    }, params);
}

function getStudentsList()
{
    var elem = document.getElementById('selectStudent');
    elem.classList.remove('hide');
    
    //Добавляем класс в параметры
    var params = 'grade='+document.getElementById('grade').value + document.getElementById('litera').value;
    ajax('/skd/getStudentsList', function(data){elem.innerHTML=data; }, params);
}

function getInventoryRooms(){
    let ob = {};
    ajaxJson('/fas/getFasRooms', function(data){
        isStorage.inventoryRooms = data;
    }, ob);
}

function getInventoryComments(){
    let ob = {};
    ajaxJson('/fas/getFasComments', function(data){
        isStorage.inventoryComments = data;
    }, ob);
}

function getInventoryPeople(){
    let ob = {};
    ajaxJson('/fas/getInvPeopleList', function(data){
        isStorage.inventoryPeople = data;
        let invChangeOwner = document.getElementById('invChangeOwner');
        autocomplete(invChangeOwner,isStorage.inventoryPeople);
        fillTransmittingFields();
    }, ob);
}

function fillTransmittingFields(){
    let invTransmittingPerson = document.getElementById('invTransmittingPerson');
    let invReceivingPerson = document.getElementById('invReceivingPerson');
    let newOption;
    isStorage.inventoryPeople.forEach(row => {
        newOption = new Option(row);
        invTransmittingPerson.appendChild(newOption);
    });
    isStorage.inventoryPeople.forEach(row => {
        newOption = new Option(row);
        invReceivingPerson.appendChild(newOption);
    });
}

function loadInvData(){
    let seachType = document.getElementById('invSeachType');
    let param;
    seachType = seachType.options[seachType.selectedIndex].dataset.type;
    if (seachType=='person') {
        param = 'person=' + document.getElementById('invSeachField').value;
        ajax('/fas/invSeach', function(data){document.getElementById('invResults').innerHTML = data}, param);
        ajax('/fas/CheckInventoryFinish',
            function(data){
                document.getElementById('cancelFas').disabled = !data;
                document.getElementById('inventoryFinishForPerson').disabled = data;
            }
            , param);
        param = '';
        ajax('/fas/getInventoryStatus', function(data){
            if (data != 0) { document.getElementById('userFasControl').classList.remove('hide'); }
        }, param);
    }

    if (seachType=='invNumber') {
        param = 'invNumber=' + document.getElementById('invSeachField').value;
        ajax('/fas/invSeach', function(data){document.getElementById('invResults').innerHTML = data}, param);
        param = '';
        ajax('/fas/getInventoryStatus', function(data){
            if (data != 0) { document.getElementById('userFasControl').classList.add('hide'); }
        }, param);
    }

    if (seachType=='location') {
        param = 'location=' + document.getElementById('invSeachField').value;
        ajax('/fas/invseach', function(data){document.getElementById('invResults').innerHTML = data}, param);
        param = '';
        ajax('/fas/getInventoryStatus', function(data){
            if (data != 0) { document.getElementById('userFasControl').classList.add('hide'); }
        }, param);
    }
    
    if (seachType=='fixedAsset') {
        param = 'fixedAsset=' + encodeURIComponent(document.getElementById('invSeachField').value);
        ajax('/fas/invseach', function(data){document.getElementById('invResults').innerHTML = data}, param);
        param = '';
        ajax('/fas/getInventoryStatus', function(data){
            if (data != 0) { document.getElementById('userFasControl').classList.add('hide'); }
        }, param);
    }

}

//Функция отправки Ajax запроса на сервер
function ajax(queryString, callback, params)
{
    var f = callback||function(data){};
    var request = new XMLHttpRequest();
    request.onreadystatechange = function()
    {
            if (request.readyState == 4 && request.status == 200)
            {
                f(request.responseText);
            }
    }
    request.open('POST', queryString);
    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    request.send(params);
}

//Функция отправки Ajax запроса на сервер +JSON
function ajaxJson(queryString, callback, dataObject)
{
    var f = callback||function(data){};
    var data = 'data='+JSON.stringify(dataObject);
    var request = new XMLHttpRequest();
    request.onreadystatechange = function()
    {
            if (request.readyState == 4 && request.status == 200)
            {
                f(JSON.parse(request.responseText));
            }
    }
    
    request.open('POST', queryString);
    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    request.send(data);
}

//Autocomplete function
function autocomplete(inp, arr) {
  /*the autocomplete function takes two arguments,
  the text field element and an array of possible autocompleted values:*/
  var currentFocus;
  /*execute a function when someone writes in the text field:*/
  inp.addEventListener("input", function(e) {
      var a, b, i, index, val = this.value;
      /*close any already open lists of autocompleted values*/
      closeAllLists();
      if (!val) { return false;}
      currentFocus = -1;
      /*create a DIV element that will contain the items (values):*/
      a = document.createElement("DIV");
      a.setAttribute("id", this.id + "autocomplete-list");
      a.setAttribute("class", "autocomplete-items");
      let pLeft = this.parentNode.dataset.positionLeft;
      let pTop = this.parentNode.dataset.positionTop;
      a.setAttribute("style", "left: "+pLeft+";"+(pTop != undefined ? " top: "+pTop : ""));
      /*append the DIV element as a child of the autocomplete container:*/
      this.parentNode.appendChild(a);
      /*for each item in the array...*/
      for (i = 0; i < arr.length; i++) {
        /*check if the item starts with the same letters as the text field value:*/
        // CHANGED!!!   if (arr[i].substr(0, val.length).toUpperCase() == val.toUpperCase()) {
        if ((val.length>=3) && arr[i]) {
          index = arr[i].toUpperCase().indexOf(val.toUpperCase());
          if ( index > -1) {
            /*create a DIV element for each matching element:*/
            b = document.createElement("DIV");
            /*make the matching letters bold:*/
            
            // b.innerHTML = "<strong>" + arr[i].substr(0, val.length) + "</strong>";
            // b.innerHTML += arr[i].substr(val.length);
            b.innerHTML =  arr[i].substr(0,index) + "<strong>" + arr[i].substr(index,val.length) + "</strong>" + arr[i].substr(index+val.length);

            /*insert a input field that will hold the current array item's value:*/
            b.innerHTML += "<input type='hidden' value='" + arr[i] + "'>";
            inp.dataset.val=arr[i];
            /*execute a function when someone clicks on the item value (DIV element):*/
                b.addEventListener("click", function(e) {
                /*insert the value for the autocomplete text field:*/
                inp.value = this.getElementsByTagName("input")[0].value;
                /*close the list of autocompleted values,
                (or any other open lists of autocompleted values:*/
                closeAllLists();
            });
            a.appendChild(b);
          }
        }
      }
  });
  /*execute a function presses a key on the keyboard:*/
  inp.addEventListener("keydown", function(e) {
      var x = document.getElementById(this.id + "autocomplete-list");
      if (x) x = x.getElementsByTagName("div");
      if (e.keyCode == 40) {
        /*If the arrow DOWN key is pressed,
        increase the currentFocus variable:*/
        currentFocus++;
        /*and and make the current item more visible:*/
        addActive(x);
      } else if (e.keyCode == 38) { //up
        /*If the arrow UP key is pressed,
        decrease the currentFocus variable:*/
        currentFocus--;
        /*and and make the current item more visible:*/
        addActive(x);
      } else if (e.keyCode == 13) {
        /*If the ENTER key is pressed, prevent the form from being submitted,*/
        e.preventDefault();
        if (currentFocus > -1) {
          /*and simulate a click on the "active" item:*/
          if (x) x[currentFocus].click();
        }
      }
  });
  function addActive(x) {
    /*a function to classify an item as "active":*/
    if (!x) return false;
    /*start by removing the "active" class on all items:*/
    removeActive(x);
    if (currentFocus >= x.length) currentFocus = 0;
    if (currentFocus < 0) currentFocus = (x.length - 1);
    /*add class "autocomplete-active":*/
    x[currentFocus].classList.add("autocomplete-active");
  }
  function removeActive(x) {
    /*a function to remove the "active" class from all autocomplete items:*/
    for (var i = 0; i < x.length; i++) {
      x[i].classList.remove("autocomplete-active");
    }
  }
  function closeAllLists(elmnt) {
    /*close all autocomplete lists in the document,
    except the one passed as an argument:*/
    var x = document.getElementsByClassName("autocomplete-items");
    for (var i = 0; i < x.length; i++) {
      if (elmnt != x[i] && elmnt != inp) {
      x[i].parentNode.removeChild(x[i]);
    }
  }
}
/*execute a function when someone clicks in the document:*/
document.addEventListener("click", function (e) {
    closeAllLists(e.target);
});
} 

//Get people count
function updateData(){
    ajax('/skd/getgeneraldata', function(data){
        let dataObj = JSON.parse(data);
        let headers = {who:'',
                       inside:'В школе',
                       amount:'Из',
                      };
        let table = createTable(headers,dataObj);
        let title = document.createElement('caption');
        let cellText = document.createTextNode('Количество');
        title.appendChild(cellText);
        table.appendChild(title);
        document.body.querySelector('.generalControl').querySelector('#numberOfPeople').innerHTML ='';
        document.body.querySelector('.generalControl').querySelector('#numberOfPeople').appendChild(table);
        });
}

//Realtime refreshing people count
function getPeopleCount(){
    updateData();
    timerID = setInterval(updateData, 3000);
}

//Stop realtime refreshing
function resetTimer(timerID){
    clearInterval(timerID);
}

//Functions for SKD system, general control module
function createTable(headers,data){
    //create elements
    let table = document.createElement('table');
    let tableHeader = document.createElement('thead');
    let tableBody = document.createElement('tbody');
    let row = document.createElement('tr');
    
    //fill headers
    for(let key in headers) {
        let cell = document.createElement('th');
        let cellText = document.createTextNode(headers[key]);
        cell.appendChild(cellText);
        row.appendChild(cell);
    };
    tableHeader.appendChild(row);
    table.appendChild(tableHeader);

    //fill rows
    data.forEach(rowData =>{
        let row = document.createElement('tr');
        for(let key in headers) {
            let cell = document.createElement('td');
            let cellText = document.createTextNode(rowData[key]);
            cell.appendChild(cellText);
            row.appendChild(cell);
        }
        table.appendChild(row);
    })
    
    //return table
    return table;
}

function resizeWrapper($size){
    document.querySelectorAll('.wrapper')[1].style.width = $size;
    document.querySelector('.content').style.width = $size;
    document.querySelector('.ui-tabs').style.width = $size;
}

//MTdev
function getNumberOfVisits(){
    let start = document.getElementById('visitSelectStartDay').value;
    let end = document.getElementById('visitSelectEndDay').value;
    let params = 'start='+start+'&end='+end;
    ajax('/visit/getNumberOfVisits', function(data){
        if (data.indexOf('content-login') >= 0) { location.reload(true) };
        document.body.querySelector('.Reports').querySelector('#numberOfVisits').innerHTML = '';
        document.body.querySelector('.Reports').querySelector('#numberOfVisits').innerHTML = data;
        }, params);
}

function getNumberOfAVisits(){
    let start = document.getElementById('AvisitSelectStartDay').value;
    let end = document.getElementById('AvisitSelectEndDay').value;
    let params = 'start='+start+'&end='+end;
    ajax('/visit/getNumberOfAttestationVisits', function(data){
        if (data.indexOf('content-login') >= 0) { location.reload(true) };
        document.body.querySelector('.AReports').querySelector('#numberOfAVisits').innerHTML = '';
        document.body.querySelector('.AReports').querySelector('#numberOfAVisits').innerHTML = data;
        }, params);
}

function mask(event) {
    if (event.target.type == 'tel') {
        var pos = event.target.selectionStart;
        if (pos < 3) event.preventDefault();
        var matrix = "+7 (___) ___ ____",
            i = 0,
            def = matrix.replace(/\D/g, ""),
            val = event.target.value.replace(/\D/g, ""),
            new_value = matrix.replace(/[_\d]/g, function(a) {
                return i < val.length ? val.charAt(i++) || def.charAt(i) : a
            });
        i = new_value.indexOf("_");
        if (i != -1) {
            i < 5 && (i = 3);
            new_value = new_value.slice(0, i)
        }
        var reg = matrix.substr(0, event.target.value.length).replace(/_+/g,
            function(a) {
                return "\\d{1," + a.length + "}"
            }).replace(/[+()]/g, "\\$&");
        reg = new RegExp("^" + reg + "$");
        if (!reg.test(event.target.value) || event.target.value.length < 5 || event.keyCode > 47 && event.keyCode < 58) {
            event.target.value = new_value;
        };
        if (event.type == "focusout" && event.target.value.length < 5)  {
            event.target.value = "";
        }
    }
}

function showNotification(text, timer = 1500) {
    let notification = document.getElementById('custom-notification-text');
    notification.classList.remove("notification-hide");
    notification.classList.add("notification-show");
    notification.innerHTML = text;
    setTimeout(() => {
        notification.classList.remove("notification-show");
        notification.classList.add("notification-hide");
    }, timer);
}