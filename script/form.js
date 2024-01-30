// Prevents the normal form submission behavior and initiates validation and submission of the question form
function validateandsubmitquestion(event) {
    event.preventDefault();

    // Retrieve parameters from the current URL
    var allparams = getallurlparams();
    console.log(allparams);

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
        // Submit the form
        document.getElementById('questionform').submit();
    }
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

// Retrieves all parameters from the URL
function getallurlparams() {
    var queryString = window.location.search.slice(1);
    var obj = {};

    if (queryString) {
        queryString = queryString.split('#')[0]; // Remove fragment identifier
        var arr = queryString.split('&');

        for (var i = 0; i < arr.length; i++) {
            var a = arr[i].split('=');

            var paramname = a[0];
            var paramvalue = typeof a[1] === 'undefined' ? true : a[1];

            paramname = paramname.replace(/\+/g, ' ');
            paramvalue = paramvalue.replace(/\+/g, ' ');

            paramname = decodeURIComponent(paramname);
            paramvalue = decodeURIComponent(paramvalue);

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
