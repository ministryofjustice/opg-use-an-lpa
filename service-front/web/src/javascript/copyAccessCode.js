const copyAccessCode = () => {
    //TODO fix this to use event delegation
    let elements = document.querySelectorAll(".js-accesscodecopy");
    for (let i = 0; i < elements.length; i++) {
        let element = elements[i];
        let copyButton = element.querySelector('.js-accesscodecopy-button');
        let copyButtonValue = element.querySelector('.js-accesscodecopy-value');
        let copyDefaultMessage = element.querySelector('.js-accesscodecopy-default');
        let copySuccessMessage = element.querySelector('.js-accesscodecopy-success');
        copyButton.onclick = function () {
            navigator.clipboard.writeText(copyButtonValue.value);

            copyDefaultMessage.classList.toggle('hide', true)
            copySuccessMessage.classList.toggle('hide', false)
            setTimeout(
                function () {
                    copyDefaultMessage.classList.toggle('hide', false)
                    copySuccessMessage.classList.toggle('hide', true)
                }
                ,
                4000
            );
        };
    }
}
export default copyAccessCode;
