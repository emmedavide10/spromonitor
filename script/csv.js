// Ensure the DOM content is fully loaded before executing the code
document.addEventListener("DOMContentLoaded", function () {

    // Trigger the error message logic
    var errorPopup = document.getElementById("error-popup");
    var csvicon = document.querySelector(".fas.fa-exclamation-triangle");

    showErrorMessage(errorPopup, csvicon);

    setTimeout(function () {
        fadeout(errorPopup, 1);
    }, 4000);
});
