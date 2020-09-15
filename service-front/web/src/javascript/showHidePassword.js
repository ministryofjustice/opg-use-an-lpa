const showHidePassword = () => {
    let elements = document.querySelectorAll(".js-showhidepassword");

    for (let i = 0; i < elements.length; i++) {
        let element = elements[i];
        let showHidePwdButton = element.querySelector('.js-showhidepassword-button');
        let showHidePwdInput = element.querySelector('.js-showhidepassword-input');
        let showPasswordText = showHidePwdButton.dataset.showpassword;
        let hidePasswordText = showHidePwdButton.dataset.hidepassword;

        showHidePwdButton.innerText = showPasswordText;

        showHidePwdButton.onclick = function () {

            //  Determine if we are showing or hiding the password confirm input
            let isShowing = (showHidePwdInput.getAttribute('type') === "password");

            if (isShowing) {
                showHidePwdInput.setAttribute('type', 'text');
            } else {
                showHidePwdInput.setAttribute('type', 'password');
            }

            //  Change the link text
            showHidePwdButton.innerText = (isShowing ? hidePasswordText : showPasswordText);
        }
    }

}

export default showHidePassword;
