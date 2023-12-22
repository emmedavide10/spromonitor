
/*
function validateForm(event) {

    // Verifica se almeno una checkbox è selezionata
    var checkboxes = 

    checkboxes.forEach(function(checkbox) {
        if (checkbox.checked) {
            atLeastOneChecked = true;
        }
    });

    // Mostra il popup di errore se nessuna checkbox è selezionata
    if (!atLeastOneChecked) {
        document.getElementById('error-popup').style.display = 'block';
    } else {
        // Altrimenti, invia il form
        document.getElementById('myForm').submit();
    }
}*/

function validateAndSubmitQuestion(event) {
    event.preventDefault(); // Previeni il normale comportamento del submit

    // Use the function to get parameters from the current URL
    var allParams = getAllUrlParams();
    console.log(allParams);

    var checkboxes = document.querySelectorAll('input[name="question_ids[]"]');
    var errorPopup = document.getElementById("error-popup");
    var selectedFieldsInput = document.getElementById('selectedFieldsInput');
    var exclamationIcons = document.querySelectorAll('.fa-exclamation-circle');



    // Verifica se nessun checkbox è stato selezionato
    var noCheckboxSelected = true;
    var selectedFieldValues = [];

    checkboxes.forEach(function (checkbox) {
        if (checkbox.checked) {
            noCheckboxSelected = false;
            selectedFieldValues.push(checkbox.value);
        }
    });


    if (noCheckboxSelected) {
        fadeIn(errorPopup, 0.25);
        exclamationIcons.forEach(function (icon) {
            icon.style.display = 'inline';
        });

        // Imposta un timeout di 7 secondi prima di nascondere il popup
        setTimeout(function () {
            fadeOut(errorPopup, 1); // Velocità di dissolvenza più veloce allo svanire
        }, 3000);
    } else {
        fadeOut(errorPopup, 1);
        var selectedFieldsString = selectedFieldValues.join(',');
        // Add the selected fields to the URL parameters
        allParams.selectedFields = selectedFieldsString;
        // Set the value of the hidden input
        selectedFieldsInput.value = selectedFieldsString;
        console.log(allParams);
        // Submit the form
        document.getElementById('questionForm').submit();
    }
}





function validateAndSubmitSurvey() {
    var allParams = getAllUrlParams();
    console.log(allParams);

    var selectedSurvey = document.getElementById("sproid").value;
    console.log(selectedSurvey);
    var errorPopup = document.getElementById("error-popup");

    // Verifica se non ci sono opzioni disponibili nel menu a tendina
    if (document.getElementById("sproid").options.length === 0) {
        fadeIn(errorPopup, 0.25);
        // Imposta un timeout di 7 secondi prima di nascondere il popup
        setTimeout(function () {
            fadeOut(errorPopup, 1); // Velocità di dissolvenza più veloce allo svanire
        }, 5000);
    } else {
        // Verifica se l'opzione selezionata ha un valore vuoto
        if (selectedSurvey === "") {
            fadeIn(errorPopup, 0.25);
            // Imposta un timeout di 7 secondi prima di nascondere il popup
            setTimeout(function () {
                fadeOut(errorPopup, 1); // Velocità di dissolvenza più veloce allo svanire
            }, 5000);
        } else {
            fadeOut(errorPopup, 1);
            // Add the value of the selected survey to the URL parameters
            allParams.selectedSurvey = selectedSurvey;

            console.log(allParams);

            // Submit the form
            document.getElementById('surveyForm').submit();
        }
    }
}





function getAllUrlParams() {
    var queryString = window.location.search.slice(1);
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



function fadeIn(element, speed) {
    element.style.opacity = 0;
    element.style.display = 'block';
    var tick = function () {
        element.style.opacity = +element.style.opacity + speed;

        if (+element.style.opacity < 1) {
            (window.requestAnimationFrame && requestAnimationFrame(tick)) || setTimeout(tick, 16);
        }
    };
    tick();
}

function fadeOut(element, speed) {
    element.style.opacity = 1;
    var tick = function () {
        element.style.opacity = +element.style.opacity - speed;

        if (+element.style.opacity > 0) {
            (window.requestAnimationFrame && requestAnimationFrame(tick)) || setTimeout(tick, 16);
        } else {
            element.style.display = 'none';
        }
    };
    tick();
}