/*

function search(){

    var allParams = getAllUrlParams();
    console.log(allParams);

    var selectedFieldsSearch = document.getElementById('selectedFieldsSearch');

    var selectedFieldValues = Array.from(selectedFieldsSearch).map(field => field.value);
    var selectedFieldsString = selectedFieldValues.join(',');
    
    // Aggiungi i campi selezionati ai parametri dell'URL
    allParams.selectedFields = selectedFieldsString;
    
    // Imposta il valore dell'input nascosto
    //selectedFieldsInput.value = selectedFieldsString;

    console.log(allParams);

    // Invia il form
    document.getElementById('surveyForm').submit();
}





function getAllUrlParams(url) {
    var queryString = url ? url.split('?')[1] : window.location.search.slice(1);
    var obj = {};

    if (queryString) {
        queryString = queryString.split('#')[0]; // Remove fragment identifier
        var arr = queryString.split('&');

        for (var i = 0; i < arr.length; i++) {
            var a = arr[i].split('=');

            var paramName = a[0];
            var paramValue = typeof a[1] === 'undefined' ? true : a[1];

            paramName = paramName.replace(/\+/g, ' ');
            paramValue = paramValue.replace(/\+/g, ' ');

            paramName = decodeURIComponent(paramName);
            paramValue = decodeURIComponent(paramValue);

            if (paramValue === 'true' || paramValue === 'false') {
                paramValue = JSON.parse(paramValue);
            }

            obj[paramName] = paramValue;
        }
    }

    return obj;
}
*/