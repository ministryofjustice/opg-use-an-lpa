const disableButtonOnClick = () => {
  var btn = document.getElementsByClassName('govuk-button');
  for (var i = 0; i < btn.length; i++) {

      btn[i].addEventListener('click', function disableButton() {
          this.disabled = true;
          document.forms[this.form.name].submit()

      })
  }
};

export default disableButtonOnClick;
