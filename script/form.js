// Prevents the normal form submission behavior and initiates validation and submission of the question form
function validatequestions(event) {
    event.preventDefault();

    // Retrieve parameters from the current URL
    var allparams = getallurlparams();
    //console.log(allparams);

    var checkboxes = document.querySelectorAll('input[name="question_ids[]"]');
    var errorpopup = document.getElementById("error-popup");
    var selectedfieldsinput = document.getElementById('selectedfieldsinput');
    var exclamationicons = document.querySelectorAll('.fa-exclamation-circle');

    // Check if no checkbox is selected
    var selectedfieldvalues = [];

    checkboxes.forEach(function (checkbox) {
        if (checkbox.checked) {
            selectedfieldvalues.push(checkbox.value);
        }
    });

    // Show error popup if no checkbox is selected or more than 5 parameters are selected
    if (selectedfieldvalues.length === 0 || selectedfieldvalues.length > 5) {
        fadein(errorpopup, 0.25);
        exclamationicons.forEach(function (icon) {
            icon.style.display = 'inline';
        });

        // Set a 4-second timeout before hiding the popup
        setTimeout(function () {
            fadeout(errorpopup, 1); // Faster fade-out speed
        }, 4000);
    } else {
        fadeout(errorpopup, 1);
        var selectedfieldsstring = selectedfieldvalues.join(',');
        // Add the selected fields to the URL parameters
        allparams.selectedfields = selectedfieldsstring;
        
        // Set the value of the hidden input
        selectedfieldsinput.value = selectedfieldsstring;
        console.log(allparams);

        //document.getElementById('questionform').submit();

        //nascondi questo div e mostra quello delle date
        document.getElementById('questioncontainer').style.display = 'none';

        // Mostra il modulo con id "datecontainer"
        var dateContainer = document.getElementById('datecontainer');
        dateContainer.style.display = 'block'; // Puoi utilizzare 'flex' o 'inline-block' a seconda della tua esigenza
    }
}


// Prevents the normal form submission behavior and initiates validation and submission of the date
function validatedataandsubmit(event) {
    event.preventDefault();

    // Retrieve parameters from the current URL
    var allparams = getallurlparams();
    console.log(allparams);
        
    var checkboxes = document.querySelectorAll('input[name="date_ids[]"]');
    var errorpopup = document.getElementById("error-data-popup");
    var selecteddatainput = document.getElementById('selecteddatainput');
    var exclamationicons = document.querySelectorAll('.fa-exclamation-circle');

    // Check if no checkbox is selected
    var selecteddatavalues = [];

    checkboxes.forEach(function (checkbox) {
        if (checkbox.checked) {
            selecteddatavalues.push(checkbox.value);
        }
    });

    // Show error popup if no checkbox is selected or more than 5 parameters are selected
    if (selecteddatavalues.length === 0 || selecteddatavalues.length > 1) {
        fadein(errorpopup, 0.25);
        exclamationicons.forEach(function (icon) {
            icon.style.display = 'inline';
        });

        // Set a 4-second timeout before hiding the popup
        setTimeout(function () {
            fadeout(errorpopup, 1); // Faster fade-out speed
        }, 4000);
    } else {
        fadeout(errorpopup, 1);
        const data = selecteddatavalues[0];
        allparams.selecteddata = data;
        allparams.createrow = 1; // Add createrow parameter
        // Set the value of the hidden input
        selecteddatainput.value = data;
        console.log(allparams);
        // Submit the form
        document.getElementById('questionform').submit();
    }
}


// Restituisce tutti i parametri dall'URL come oggetto
function getallurlparams() {
    var queryString = window.location.search.slice(1);
    var obj = {};

    if (queryString) {
        queryString = queryString.split('#')[0]; // Rimuovi l'identificatore del frammento
        var arr = queryString.split('&');

        for (var i = 0; i < arr.length; i++) {
            var a = arr[i].split('=');

            var paramname = decodeURIComponent(a[0]);
            var paramvalue = typeof a[1] === 'undefined' ? true : decodeURIComponent(a[1]);

            if (paramvalue === 'true' || paramvalue === 'false') {
                paramvalue = JSON.parse(paramvalue);
            }

            obj[paramname] = paramvalue;
        }
    }

    return obj;
}


// Fades in an HTML element
function fadein(element, speed) {
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

// Fades out an HTML element
function fadeout(element, speed) {
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




// Validates and submits the survey form
function validateandsubmitsurvey() {
    var allparams = getallurlparams();
    console.log(allparams);

    var selectedsurvey = document.getElementById("sproid").value;
    console.log(selectedsurvey);
    var errorpopup = document.getElementById("error-popup");

    // Check if no options are available in the dropdown menu
    if (document.getElementById("sproid").options.length === 0) {
        fadein(errorpopup, 0.25);
        // Set a 5-second timeout before hiding the popup
        setTimeout(function () {
            fadeout(errorpopup, 1); // Faster fade-out speed
        }, 5000);
    } else {
        // Check if the selected option has an empty value
        if (selectedsurvey === "") {
            fadein(errorpopup, 0.25);
            // Set a 5-second timeout before hiding the popup
            setTimeout(function () {
                fadeout(errorpopup, 1); // Faster fade-out speed
            }, 5000);
        } else {
            fadeout(errorpopup, 1);
            // Add the value of the selected survey to the URL parameters
            allparams.selectedsurvey = selectedsurvey;

            console.log(allparams);

            // Submit the form
            document.getElementById('surveyform').submit();
        }
    }
}