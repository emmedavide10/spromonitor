document.addEventListener("DOMContentLoaded", function () {
    function hideErrorMessage() {
        var errorFile = document.getElementById('error-file');
        errorFile.style.display = 'none';

        // Go back one page only if there are no JavaScript errors
        if (!window.jsError) {
            // Use replaceState to prevent the current page from being added to the browser history
            history.replaceState(null, document.title, location.href);
            history.back(); // Go back one page
        }
    }

    // Hide the message after 2 seconds (2000 milliseconds)
    setTimeout(hideErrorMessage, 2500);
});
