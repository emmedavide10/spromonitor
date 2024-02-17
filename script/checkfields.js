function checkradiobutton(event) {
    event.preventDefault();

    const choice1 = document.getElementById('choice1').checked;
    const choice2 = document.getElementById('choice2').checked;
    const sproid = document.getElementById("sproid").value;
    const courseid = document.getElementById("courseid").value;
    const errorPopup = getElement("error-popup");

    if (choice1) {
        window.location.href = `index.php?sproid=${encodeURIComponent(sproid)}&courseid=${encodeURIComponent(courseid)}`;
    } else if (choice2) {
        window.location.href = `form.php?sproid=${encodeURIComponent(sproid)}&courseid=${encodeURIComponent(courseid)}&updaterow=1`;
    } else {
        // Show error popup
        showErrorMessage(errorPopup, exclamationIcons);
        setTimeout(() => {
            fadeout(errorPopup, 1);
        }, 4000);
    }
}
