const disableButtonOnClick = (form) => {

  for (let i = 0; i < form.length; i++) {

      form[i].addEventListener('click', function disableButton(e) {

          if (e.target.nodeName == 'BUTTON') {
              e.target.disabled = true;
              document.forms[form[i].name].submit()
          }
      })
  }
};

export default disableButtonOnClick;
