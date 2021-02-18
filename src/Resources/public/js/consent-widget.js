(function (d) {
  if (d.readyState === 'loading') {
    d.addEventListener('DOMContentLoaded', handleConsentWidget);
  } else {
    handleConsentWidget();
  }

  function handleConsentWidget() {
    const form = d.forms['setono_sylius_cookie_consent_consent'];
    form.addEventListener('submit', (e) => {
      e.preventDefault();

      hideConsentContainer();

      const req = new XMLHttpRequest();

      req.onreadystatechange = () => {
        try {
          if (req.readyState !== XMLHttpRequest.DONE) {
            return;
          }

          if (req.status === 400) {
            // todo replace the consent form with the response text to show errors
            return;
          }

          if (req.status !== 204) {
            alert('An error occurred'); // todo make this better
            return;
          }
        } catch (e) {
          alert('An error occurred: ' + e.description); // todo make this better
        }
      };
      req.open('POST', form.action);
      req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      req.send(new URLSearchParams(new FormData(e.currentTarget)));
    });
  }

  function hideConsentContainer() {
    for (let el of d.querySelectorAll('.sscc-consent-container')){
      el.style.display = 'none';
    }
  }
})(document);
