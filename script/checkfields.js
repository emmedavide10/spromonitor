function checkradiobutton(event) {
    event.preventDefault();

    const choice1 = document.getElementById('choice1').checked;
    const choice2 = document.getElementById('choice2').checked;
    const sproid = document.getElementById("sproid").value;
    const courseid = document.getElementById("courseid").value;

    if (choice1) {
        window.location.href = `index.php?sproid=${encodeURIComponent(sproid)}&courseid=${encodeURIComponent(courseid)}`;
    } else if (choice2) {
        window.location.href = `form.php?sproid=${encodeURIComponent(sproid)}&courseid=${encodeURIComponent(courseid)}&updaterow=1`;
        console.log('Mostra il templateparams.mustache');
    } else {
        console.log('Devi selezionare un radio button prima di procedere.');
    }
}
