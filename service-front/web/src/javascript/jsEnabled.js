const jsEnabled = element => {
  element.className += ' js-enabled' + ('noModule' in HTMLScriptElement.prototype ? ' govuk-frontend-supported' : '');
};

export default jsEnabled;
