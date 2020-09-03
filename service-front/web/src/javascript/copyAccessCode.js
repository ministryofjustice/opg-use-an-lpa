const copyAccessCode = (element) => {
  if (element) {
    element.onclick = function() {
      let copyText = document.getElementById("accesscodecopy");
      copyText.select();
      copyText.setSelectionRange(0, 99999);
      document.execCommand("copy");
      element.innerHTML = "Code copied";
      setTimeout(function(){ element.innerHTML = "Copy code"; }, 4000);
    }
  }
}

export default copyAccessCode;

