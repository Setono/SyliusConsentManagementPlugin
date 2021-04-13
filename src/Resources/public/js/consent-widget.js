(function (w, d) {
  if(typeof sscmConsent !== "object") {
    console.error('The constant "sscmConsent" is not present or has a wrong value. Did you forget to include {{ sscm_consent_tag() }} in the <head> of your document?');
    return;
  }

  const options = Object.assign({
    'moreInformationClickHandler': function(e) {
      d.querySelector('.sscm-consent-container').style.display = 'none';
      $('.sscm-consent-modal')
        .modal({
          keyboardShortcuts: false,
          closable: false
        })
        .modal('show')
      ;
    },
    'closeModal': function(e) {
      $('.sscm-consent-modal').modal('hide');
    }
  }, w.hasOwnProperty('sscmOptions') ? w['sscmOptions'] : {});

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
    if(!(formName in d.forms)) {
      return;
    }

    const form = d.forms[formName];

    form.addEventListener('submit', (e) => {
      e.preventDefault();
      hideConsentModal();
      hideConsentContainer();

      const data = new URLSearchParams(new FormData(e.currentTarget));

      sscmConsent.preferencesGranted = data.has(formName + '[preferencesGranted]');
      sscmConsent.statisticsGranted = data.has(formName + '[statisticsGranted]');
      sscmConsent.marketingGranted = data.has(formName + '[marketingGranted]');

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
    d.addEventListener('sscmInitialConsent', fireDatalayerEvents);
    d.addEventListener('sscmConsentUpdated', runScriptTags);
    d.addEventListener('sscmConsentUpdated', fireDatalayerEvents);

    const moreInfoBtn = d.querySelector('.sscm-btn-more-information');

    if(null !== moreInfoBtn) {
      moreInfoBtn.addEventListener('click', (e) => {
        options.moreInformationClickHandler(e);
      });
    }
  }

  function fireDatalayerEvents(e) {
    if(!w.hasOwnProperty('dataLayer')) {
      return;
    }
    const dataLayer = w['dataLayer'];

    const consent = e.detail.consent;

    if(consent.marketingGranted) {
      dataLayer.push({event: 'marketingGranted'});
    }

    if(consent.preferencesGranted) {
      dataLayer.push({event: 'preferencesGranted'});
    }

    if(consent.statisticsGranted) {
      dataLayer.push({event: 'statisticsGranted'});
    }
  }

  function runScriptTags() {
    d.querySelectorAll('script[data-consent]').forEach((script) => {
      let consent = script.getAttribute('data-consent').valueOf() + 'Granted';

      if(!sscmConsent.hasOwnProperty(consent) || sscmConsent[consent] !== true) {
        return;
      }

      if(script.hasAttribute('src')) {
        loadExternalScript(script.getAttribute('src'));
        script.remove();
      } else {
        script.removeAttribute('type');
        script.removeAttribute('data-consent');

        window.eval(script.textContent); // should be called on the window to evaluate the script in the scope of window instead of local scope
      }
    });
  }

  function loadExternalScript(src) {
    const script = document.createElement('script');
    script.src = src;

    document.head.appendChild(script);
  }

  function createEvent(name)
  {
    return new CustomEvent(name, {
      bubbles: true,
      cancelable: false,
      detail: {
        consent: sscmConsent
      }
    });
  }

  function hideConsentContainer() {
    const consentContainer = d.querySelector('.sscm-consent-container');
    if(null === consentContainer) {
      return;
    }

    consentContainer.style.display = 'none';
  }

  function hideConsentModal() {
    options.closeModal();
  }
})(window, document);
