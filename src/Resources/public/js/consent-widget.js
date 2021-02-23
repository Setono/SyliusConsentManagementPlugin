(function (d, c) {
  const formName = 'setono_sylius_consent_management_consent';

  if (d.readyState === 'loading') {
    d.addEventListener('DOMContentLoaded', onLoad);
  } else {
    onLoad();
  }

  function onLoad() {
    addListeners();
    d.dispatchEvent(createEvent('sscmInitialConsent'));
    consentWidget();
  }

  function consentWidget() {
    const form = d.forms[formName];

    form.addEventListener('submit', (e) => {
      e.preventDefault();
      hideConsentContainer();

      const data = new URLSearchParams(new FormData(e.currentTarget));

      c.preferences = data.has(formName + '[preferences]');
      c.statistics = data.has(formName + '[statistics]');
      c.marketing = data.has(formName + '[marketing]');

      d.dispatchEvent(createEvent('sscmConsentUpdated'));

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
          }
        } catch (e) {
          alert('An error occurred: ' + e.description); // todo make this better
        }
      };
      req.open('POST', form.action);
      req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      req.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
      req.send(data);
    });
  }

  function addListeners() {
    d.addEventListener('sscmInitialConsent', runScriptTags);
    d.addEventListener('sscmConsentUpdated', runScriptTags);

    d.querySelector('.sscm-btn-more-information').addEventListener('click', (e) => {
      // show the detailed information about services
      d.querySelector('.sscm-more-information').style.display = '';

      // change the text on the submit button
      const submitBtn = d.querySelector('.sscm-btn-submit');
      submitBtn.innerHTML = submitBtn.getAttribute('data-secondary-caption').valueOf();

      e.currentTarget.remove();
    });
  }

  function runScriptTags() {
    const keys = ['preferences', 'statistics', 'marketing'];

    d.querySelectorAll('script[data-consent]').forEach((script) => {
      let consent = script.getAttribute('data-consent').valueOf();
      if(!keys.includes(consent)) {
        console.error('The consent "%s" is not valid. Use one of [%s]', consent, keys.join(', '));
        return;
      }

      if(!c[consent]) {
        return;
      }

      script.removeAttribute('type');
      script.removeAttribute('data-consent');
      eval(script.textContent);
    });
  }

  function createEvent(name)
  {
    return new CustomEvent(name, {
      bubbles: true,
      cancelable: false,
      detail: {
        consent: c
      }
    });
  }

  function hideConsentContainer() {
    for (let el of d.querySelectorAll('.sscm-consent-container')) {
      el.style.display = 'none';
    }
  }
})(document, sscmConsent);
