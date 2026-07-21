const disableButtonOnClick = (form) => {
  for (let i = 0; i < form.length; i++) {
    form[i].addEventListener('click', function disableButton(e) {
      if (e.target.nodeName == 'BUTTON' && e.target.getAttribute('data-prevent-double-click') && !(e.target.disabled)) {
        e.target.disabled = true;

        const timeout = e.target.dataset.reenableAfter;

        if (timeout) {
          setTimeout(() => {
            e.target.disabled = false;
          }, Number(timeout));
        }

        form[i].submit();
      }
    });
  }
};

export default disableButtonOnClick;
