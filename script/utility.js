// Fades in an HTML element
function fadein(element, speed) {
    transition(element, speed, true);
}

// Fades out an HTML element
function fadeout(element, speed, callback) {
    transition(element, speed, false, callback);
}

// Redirect to index.php
function redirectToIndex(sproid, courseid, selectedfieldsstring, updaterow, createrow) {
    var url = 'index.php?sproid=' + encodeURIComponent(sproid) +
        '&courseid=' + encodeURIComponent(courseid) +
        '&selectedfields=' + encodeURIComponent(selectedfieldsstring);

    if (updaterow == 1) {
        url += '&updaterow=' + encodeURIComponent(updaterow);
    } else if (createrow == 1) {
        url += '&createrow=' + encodeURIComponent(createrow);
    }
    alert(url);
    window.location.href = url;
}


// Show error popup and exclamation icons
function showErrorMessage(errorPopup, exclamationIcons) {
    fadein(errorPopup, 0.25);
    exclamationIcons.forEach(function (icon) {
        icon.style.display = 'inline';
    });
}

// Generic function to handle fade-in and fade-out transitions
function transition(element, speed, fadeIn, callback) {
    var targetOpacity = fadeIn ? 1 : 0;
    var currentOpacity = fadeIn ? 0 : 1;

    element.style.opacity = currentOpacity;
    element.style.display = 'block';

    var tick = function () {
        currentOpacity = fadeIn ? currentOpacity + speed : currentOpacity - speed;
        element.style.opacity = currentOpacity;

        if ((fadeIn && currentOpacity < targetOpacity) || (!fadeIn && currentOpacity > targetOpacity)) {
            (window.requestAnimationFrame && requestAnimationFrame(tick)) || setTimeout(tick, 16);
        } else {
            if (!fadeIn) {
                element.style.display = 'none';
            }
            if (callback) {
                callback();
            }
        }
    };

    tick();
}

// Generic function to get element by ID
function getElement(id) {
    return document.getElementById(id);
}

// Generic function to get value by ID
function getValue(id) {
    return getElement(id).value;
}

// Generic function to toggle visibility of containers
function toggleContainers(showId, hideId) {
    getElement(showId).style.display = 'block';
    getElement(hideId).style.display = 'none';
}
