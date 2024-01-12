document.addEventListener('DOMContentLoaded', function () {

    // Funzione per nascondere l'errore dopo un certo periodo di tempo
    function hideErrorMessage() {
        var errorFile = document.getElementById('error-file');
        errorFile.style.display = 'none';

        // Torna indietro di una pagina solo se non ci sono errori di JavaScript
        if (!window.jsError) {
            // Utilizza replaceState per evitare che la pagina corrente venga aggiunta alla cronologia
            history.replaceState(null, document.title, location.href);
            history.back(); // Torna indietro di una pagina
        }
    }

    // Nascondi il messaggio dopo 2 secondi (2000 millisecondi)
    setTimeout(hideErrorMessage, 2000);
    var csvButton = document.getElementById('csv');
    var downloadSection = document.getElementById('downloadSection');

    csvButton.addEventListener('click', function (event) {
        event.preventDefault(); // Prevent the default form submission behavior
        document.getElementById('csvbutton').style.display = 'none'; // Hide the csv generate button 
        downloadSection.style.display = 'block'; // Show the download section
    });
});

