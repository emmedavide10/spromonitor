document.addEventListener('DOMContentLoaded', function () {
    var csvButton = document.getElementById('csv');
    var downloadSection = document.getElementById('downloadSection');

    csvButton.addEventListener('click', function (event) {
        event.preventDefault(); // Prevent the default form submission behavior
        document.getElementById('csvbutton').style.display = 'none'; // Hide the csv generate button 
        downloadSection.style.display = 'block'; // Show the download section
    });
});
