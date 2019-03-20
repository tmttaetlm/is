/* 
 * Main Javascript file
 */
'use strict'

window.onload = function() {
    
    //Catches clicks and send to handler
    document.addEventListener("click", function (event) {
        clickHandler(event.target);
    });
    
    //Catches changes and send to handler
    document.addEventListener("change", function (event) {
        changeHandler(event.target);
    });



/*    
    //Catches keyboard input and send to handler
    document.addEventListener("onkeypress", function (event) {
        keyHandler(event.target);
    });
*/    

    //Отлавливает ввод с клавиатуры и передает в обработчик
    document.body.onkeyup = function(event) {
        var obj=event.target||event.srcElement;
        keyHandler(obj);
    };

    //Set active first tab in Tabs
    var tabs = document.querySelector('.ui-tabs');
    if (tabs){
        tabs.firstElementChild.checked = true;
    }
    
    
    //Настройка календарей
    var selectDay = document.getElementById('selectDay');
    var staffSelectDay = document.getElementById('staffSelectDay')
    var today = getInputDate();
    if (selectDay){
        var today = getInputDate();
        selectDay.value = today;
        
        document.getElementById('selectDay').setAttribute("max", today);
        document.getElementById('selectDayStart').value = today;
        document.getElementById('selectDayStart').setAttribute("max", today);
        document.getElementById('selectDayEnd').value = today;
        document.getElementById('selectDayEnd').setAttribute("max", today);
        var minDateStudent = getInputDate(-31);
        var minDatePerson = getInputDate(-100);
        document.getElementById('selectDayStart').setAttribute("min", minDateStudent);
        document.getElementById('selectDayEnd').setAttribute("min", minDateStudent);
    }
    if (staffSelectDay){
    document.getElementById('staffSelectDay').setAttribute("max", today);
    document.getElementById('staffSelectDay').value = today;
    document.getElementById('personSelectDayStart').setAttribute("min", minDatePerson);
    document.getElementById('personSelectDayEnd').setAttribute("min", minDatePerson);
    document.getElementById('personSelectDayStart').setAttribute("max", today);
    document.getElementById('personSelectDayEnd').setAttribute("max", today);
    document.getElementById('personSelectDayStart').value = today;
    document.getElementById('personSelectDayEnd').value = today;
    }
    if (document.getElementById('selectDivision')){
    ajax('/skd/getDivisionList', function(data){document.getElementById('selectDivision').innerHTML =  data;});
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
        });
        
        document.getElementById('fasItem').addEventListener('mouseover',function (event) {
            clearInterval(slideInterval);
            slides[0].className = 'slide';
            slides[1].className = 'slide showing';
        });
        
        document.getElementById('skdItem').addEventListener('mouseout',function (event) {
            slideInterval = setInterval(slider, 5000);
        });
        document.getElementById('fasItem').addEventListener('mouseout',function (event) {
            slideInterval = setInterval(slider, 5000);
        });
        


    }
    //Переключение слайдов
    function slider(){
        slides[currentSlide].className = 'slide';
        currentSlide = (currentSlide+1)%slides.length;
        slides[currentSlide].className = 'slide showing';
    }

    
    
}



//Keyboard hanler
function keyHandler(obj)
{
    if ((obj.id == 'selectDayEnd') || (obj.id == 'selectDayStart') || (obj.id == 'selectDay') || (obj.id == 'staffSelectDay')|| (obj.id == 'personSelectDayStart')|| (obj.id == 'personSelectDayEnd')){
        var today = new Date();
        today = today.toISOString().substring(0, 10);
        obj.value = today;
    }
    
}

//Change Handler
function changeHandler(obj)
{
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
            document.getElementById('studentByPeriodcal').classList.remove('hide');
        }
    }
    
    if (obj.name == "userReportType"){
        document.body.querySelector('.userControl').querySelector('.results').innerHTML ="";
    }
    
    //admin roleSettings
    if (obj.id == "skdCanBrowseStudentsLogs"|| obj.id == "skdCanBrowseStaffLogs"|| obj.id == "skdCanBrowseGeneralControl" || obj.id == "fasCanSeach" || obj.id == "adminPanel" || obj.id =="skdGeneralControlCanEditComments"){
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
    
    //SkdGeneralControl
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
    document.getElementById('selectStudent').classList.add('hide');
    document.getElementById('studentByPeriodcal').classList.add('hide');
    document.body.querySelector('.studentControl').querySelector('.results').innerHTML ="";
}

function getInputDate(offset){
    offset = offset || 0;
    var date = new Date();
    date.setDate(date.getDate() + offset);
    return date.toISOString().substring(0, 10);
}

//Обработчик кликов на странице
function clickHandler(obj)
{  
    
    if (obj.id == "userControl") {
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
    if (obj.name == "generalControlGetData"){
            getPeopleCount();
    }
    
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
        
        document.querySelectorAll('.wrapper')[1].style.width = '1250px';
        document.querySelector('.content').style.width = '1250px';
        document.querySelector('.ui-tabs').style.width = '1250px';
        
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
    ajax('/skd/getStaffList', function(data){document.getElementById('selectPerson').innerHTML=data; }, params);
}


function getStudentsList()
{
    var elem = document.getElementById('selectStudent');
    elem.classList.remove('hide');
    
    //Добавляем класс в параметры
    var params = 'grade='+document.getElementById('grade').value + document.getElementById('litera').value;
    ajax('/skd/getStudentsList', function(data){elem.innerHTML=data; }, params);
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
function getPeopleCount(){
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


