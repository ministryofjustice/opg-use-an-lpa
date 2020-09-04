const showHidePassword = () => {
    if (document.getElementById('showHidePassword')) {

        var link = document.getElementById('showHidePassword');

        link.addEventListener('click', function showHideToggle(e) {

            e.preventDefault();
            var pwdConfirmParent = document.getElementById("password_confirm").parentElement;
            var passwordElement = document.getElementById("password");

            var pwd = this.getAttribute('data-for');
            var hideConfirm = this.getAttribute('data-hideConfirmPassword');

            //  Determine if we are showing or hiding the password confirm input
            var isShowing = (pwd === "password");

            if (isShowing) {
                if (hideConfirm) {
                    pwdConfirmParent.hidden = true;
                    passwordElement.setAttribute('type', 'text');
                    this.setAttribute('data-for', 'password_confirm');
                }
            } else {
                if (hideConfirm) {
                    pwdConfirmParent.hidden = false;
                    passwordElement.setAttribute('type', 'password');
                    this.setAttribute('data-for', 'password');
                }
            }

            //  Change the link text
            link.textContent = (isShowing ? 'Hide password' : 'Show password');

            return false;
        });
    }
}

export default showHidePassword;
