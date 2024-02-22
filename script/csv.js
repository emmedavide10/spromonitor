// Ensure the DOM content is fully loaded before executing the code
document.addEventListener("DOMContentLoaded", function () {

    var csvButton = document.getElementById('csv');
    var downloadsection = document.getElementById('downloadsection');

    // Event listener for the CSV button
    csvButton.addEventListener('click', function (event) {
        event.preventDefault(); // Prevent the default form submission behavior
        document.getElementById('csvbutton').style.display = 'none'; // Hide the CSV generate button 
        downloadsection.style.display = 'block'; // Show the download section
    });
    
    // Trigger the error message logic
    var errorPopup = document.getElementById("error-popup");
    var csvicon = document.querySelector(".fas.fa-exclamation-triangle");

    showErrorMessage(errorPopup, csvicon);
    setTimeout(function () {
        fadeout(errorPopup, 1);
    }, 4000);

    
});