const disableButtonOnClick = (form) => {
  for (let i = 0; i < form.length; i++) {
    form[i].addEventListener('click', function disableButton(e) {
      if (e.target.nodeName == 'BUTTON' && e.target.getAttribute('data-prevent-double-click') && !(e.target.disabled)) {
        e.target.disabled = true;

        form[i].submit();
      }
    });
  }
};

export default disableButtonOnClick;
