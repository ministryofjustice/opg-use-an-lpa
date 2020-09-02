const showHidePassword = (document) => {
    if (document.getElementById('showHidePassword')) {
        var forms = document.getElementsByTagName('form');
        var link = document.getElementById('showHidePassword');

        link.addEventListener('click', function showHideToggle(e) {

            var pwd = this.getAttribute('data-for');
            var hideConfirm = this.getAttribute('data-hideConfirmPassword');

            //  Determine if we are showing or hiding the password confirm input
            var isShowing = (pwd === "password");

            if (isShowing) {
                if (hideConfirm) {

                }

            }
        });
    }
}

export default showHidePassword;
