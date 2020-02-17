const disableButtonOnClick = (form) => {

  for (let i = 0; i < form.length; i++) {

      form[i].addEventListener('click', function disableButton(e) {

          if (e.target.nodeName == 'BUTTON') {
              if (e.target.getAttribute('data-prevent-double-click')) {
                  e.target.disabled = true;
                  document.forms[form[i].name].submit();
              }
          }
      })
  }
};

export default disableButtonOnClick;
