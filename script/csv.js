document.addEventListener('DOMContentLoaded', function () {

    // Function to hide the error message after a certain period of time
    function hideErrorMessage() {
        var errorfile = document.getElementById('error-file');
        errorfile.style.display = 'none';

        // Go back one page only if there are no JavaScript errors
        if (!window.jsError) {
            // Use replaceState to prevent the current page from being added to the browser history
            history.replaceState(null, document.title, location.href);
            history.back(); // Go back one page
        }
    }

    // Hide the message after 2 seconds (2000 milliseconds)
    setTimeout(hideErrorMessage, 2000);
    var csvButton = document.getElementById('csv');
    var downloadsection = document.getElementById('downloadsection');

    // Event listener for the CSV button
    csvButton.addEventListener('click', function (event) {
        event.preventDefault(); // Prevent the default form submission behavior
        document.getElementById('csvbutton').style.display = 'none'; // Hide the CSV generate button 
        downloadsection.style.display = 'block'; // Show the download section
    });
});
