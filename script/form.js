// Validates and submits the survey form
function validateandsubmitsurvey() {
    var capability = getValue("capability");
    var sproid = getValue("sproid");
    var courseid = getValue("courseid");
    var createrow = getValue("createrow");
    var updaterow = getValue("updaterow");

    var errorPopup = getElement("error-popup");

    if (getElement("sproid").options.length === 0 || sproid === "") {
        showErrorMessage(errorPopup);
    } else {
        fadeout(errorPopup, 1);
        if (!capability) {
            redirectToIndex(sproid, courseid);
        } else {
            var url = 'form.php?sproid=' + encodeURIComponent(sproid) +
                '&courseid=' + encodeURIComponent(courseid);
            if (updaterow == 1) {
                url += '&updaterow=' + encodeURIComponent(updaterow);
            } else if (createrow == 1) {
                url += '&createrow=' + encodeURIComponent(createrow);
            }
            window.location.href = url;
        }
    }
}

// Prevents the normal form submission behavior and initiates validation and submission of the question form
function validatequestions(event) {
    event.preventDefault();
    var sproid = getValue("sproid");
    var courseid = getValue("courseid");
    var updaterow = getValue("updaterow");
    var createrow = getValue("createrow");

    var checkboxes = document.querySelectorAll('input[name="question_ids[]"]');
    var checkboxesdate = document.querySelectorAll('input[name="date_ids[]"]');

    var checkboxesLength = checkboxesdate.length;

    var errorPopup = getElement("error-popup");
    var exclamationIcons = document.querySelectorAll('.fa-exclamation-circle');

    var selectedFieldValues = [];

    checkboxes.forEach(function (checkbox) {
        if (checkbox.checked) {
            selectedFieldValues.push(checkbox.value);
        }
    });

    if (selectedFieldValues.length === 0 || selectedFieldValues.length > 5) {
        showErrorMessage(errorPopup, exclamationIcons);
        setTimeout(function () {
            fadeout(errorPopup, 1);
        }, 4000);
    } else {
        fadeout(errorPopup, 1);
        var selectedFieldsString = selectedFieldValues.join(',');

        if (checkboxesLength > 0) {
            toggleContainers('datecontainer', 'questioncontainer', selectedFieldsString);
        } else {
            redirectToIndex(sproid, courseid, selectedFieldsString, updaterow, createrow);
        }
    }

}

// Prevents the normal form submission behavior and initiates validation and submission of the date
function validatedataandsubmit(event) {
    event.preventDefault();

    var checkboxes = document.querySelectorAll('input[name="date_ids[]"]');
    var errorPopup = getElement("error-data-popup");
    var exclamationIcons = document.querySelectorAll('.fa-exclamation-circle');
    var updaterow = getValue("updaterow");
    var createrow = getValue("createrow");
    var sproid = getValue("sproid");
    var courseid = getValue("courseid");
    var selectedFieldsString = getValue("selectedfieldsinput");

    var selectedDataValues = [];

    checkboxes.forEach(function (checkbox) {
        if (checkbox.checked) {
            selectedDataValues.push(checkbox.value);
        }
    });

    if (selectedDataValues.length === 0 || selectedDataValues.length > 1) {
        showErrorMessage(errorPopup, exclamationIcons);
        setTimeout(function () {
            fadeout(errorPopup, 1);
        }, 4000);
    } else {
        var selectedData = selectedDataValues[0];
        fadeout(errorPopup, 1);
        redirectToIndex(sproid, courseid, selectedFieldsString, updaterow, createrow, selectedData);
    }
}