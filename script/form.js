function validateAndSubmit() {
    // Utilizza la funzione per ottenere i parametri dall'URL corrente
    var allParams = getAllUrlParams();
    console.log(allParams);

    var selectedFields = document.querySelectorAll('input[name="question_ids[]"]:checked');
    var errorContainer = document.getElementById('errorContainer');
    var selectedFieldsInput = document.getElementById('selectedFieldsInput');

    if (selectedFields.length === 0) {
        errorContainer.textContent = 'Seleziona almeno un campo';
    } else {
        errorContainer.textContent = '';
        var selectedFieldValues = Array.from(selectedFields).map(field => field.value);
        var selectedFieldsString = selectedFieldValues.join(',');

        // Aggiungi i campi selezionati ai parametri dell'URL
        allParams.selectedFields = selectedFieldsString;

        // Imposta il valore dell'input nascosto
        selectedFieldsInput.value = selectedFieldsString;

        console.log(allParams);

        // Invia il form
        document.getElementById('surveyForm').submit();
    }
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



