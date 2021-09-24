/* eslint-disable max-len */

/**
 * Type defining the valid consent strings. Only used for JSDoc and IDE autocomplete
 * @typedef {'preferences'|'statistics'|'marketing'} sscmConsentAvailable
 */

/**
 * Type defining a key/value pair where the keys are any valid consent string and the value is a boolean, which shows
 * if that consent has been granted (true) or not (false). Only used for JSDoc and IDE autocomplete
 * @typedef {Object<sscmConsentAvailable, boolean>} sscmConsentGranted
 * */

/**
 * @callback sscmAnimationCallback
 * @param {CallableFunction} resolve
 * @param {HTMLElement} element
 */

/**
 * @callback sscmConsentCallback
 * @param {boolean} consent Is true when all the required consents are granted by the user.
 * @return {boolean} If the callback returns true, sscm will request the user to reload the page for changes to take
 * effect. This is only honored on subsequent calls to the callback after first init.
 */

/**
 * Used to store the 'resolve' callback for the init promise.
 * @type {Function}
 */
let initResolve;

/**
 * Defines the form name used in the Sylius plugin.
 * @type {string}
 */
const sscmFormName = 'setono_sylius_consent_management_consent';

/**
 * Array with all the consent types.
 * @type {Array<sscmConsentAvailable>}
 */
const sscmConsentTypes = ['preferences', 'statistics', 'marketing'];

const sscm = {
  /**
   * @type {Object}
   * @property {?sscmAnimationCallback} hideBanner
   * @property {?sscmAnimationCallback} openModal
   * @property {?sscmAnimationCallback} closeModal
   * @property {?sscmAnimationCallback} showPopup
   * @property {?sscmAnimationCallback} hidePopup
   */
  options: {
    hideBanner: null,
    openModal: null,
    closeModal: null,
    showPopup: null,
    hidePopup: null,
  },

  /**
   * If true the user has already submitted his consent choices.
   * @type {boolean}
   */
  decided: false,

  /**
   * If true an unhandleable error has occurred and no more actions (open or close modal and so on) are possible.
   * User must reload the page.
   * @type {boolean}
   */
  error: false,

  /** @type {sscmConsentGranted} */
  consent: Object.fromEntries(sscmConsentTypes.map((c) => [c, false])),

  /**
   * Stores all the consent subscribers, be it script tags or callbacks.
   * @type {Object<number, {needs: Array<sscmConsentAvailable>, init: boolean, target: sscmConsentCallback|HTMLScriptElement}>}
   */
  subscribers: {},

  /**
   * Used for giving each subscriber an id.
   * @type {number}
   */
  counter: 0,

  /** @type {Promise} */
  initPromise: new Promise((resolve) => {
    initResolve = resolve;
  }),

  /** @type {Promise} */
  actionPromise: Promise.resolve(),

  /**
   * Run this function to initialise the consent system.
   * @param {Object} options All the callback options are called when the specific event happens. Such as when the
   * banner is hidden. The callbacks are called with two arguments: #1 is a resolve callback, which you must run when
   * your code is done, #2 is the element in question.
   * @param {sscmAnimationCallback} [options.hideBanner] Optional callback which is called when the info banner is
   * hidden.
   * @param {sscmAnimationCallback} [options.openModal] Optional callback which is called when the settings modal is
   * shown.
   * @param {sscmAnimationCallback} [options.closeModal] Optional callback which is called when the settings modal is
   * hidden.
   * @param {sscmAnimationCallback} [options.showPopup] Optional callback which is called when the info popup is shown.
   * @param {sscmAnimationCallback} [options.hidePopup] Optional callback which is called when the info popup is hidden.
   */
  init(options = {}) {
    try {
      if (typeof options !== 'object') {
        throw (new Error('The sscmInit options are incorrect.'));
      }

      sscm.options = { ...sscm.options, ...options };

      sscm.elmWidget = document.getElementById('sscm-widget');

      sscm.decided = sscm.elmWidget.dataset.sscmDecided === '1';

      if (sscm.decided) {
        const consent = JSON.parse(sscm.elmWidget.dataset.sscmConsent);
        sscmConsentTypes.forEach((c) => {
          sscm.consent[c] = consent[`${c}Granted`] === true;
        });
      } else {
        sscm.setup();

        sscm.elmBanner = document.getElementById('sscm-w-banner');
        sscm.elmButtonMore = document.getElementById('sscm-wbbb-more');

        sscm.elmButtonMore.addEventListener('click', () => {
          sscm.doAction(() => sscm.hideBanner()
            .then(sscm.openModal)
            .then(() => {
              localStorage.setItem('sscmComs', JSON.stringify(['open']));
              localStorage.removeItem('sscmComs');
            }));
        }, { passive: true });
      }

      window.addEventListener('storage', (e) => {
        if(e.key !== 'sscmComs' || null === e.newValue || '' === e.newValue) {
          return;
        }

        const storageData = JSON.parse(e.newValue);
        // eslint-disable-next-line default-case
        switch (storageData[0]) {
          case 'saving':
            sscm.doAction(() => Promise.all([sscm.hideBanner(), sscm.closeModal()])
              .then(() => sscm.setPopup('save'))
              .then(sscm.showPopup));
            break;
          case 'success':
            sscm.doAction(() => Promise.all([sscm.hideBanner(), sscm.closeModal()])
              .then(() => sscm.submitSuccess(storageData[1], storageData[2], false)));
            break;
          case 'error':
            sscm.doAction(() => Promise.all([sscm.hideBanner(), sscm.closeModal()])
              .then(() => sscm.submitError(false)));
            break;
          case 'open':
            sscm.doAction(() => Promise.all([sscm.hideBanner(), sscm.hidePopup()])
              .then(sscm.openModal));
            break;
          case 'close':
            sscm.doAction(() => sscm.closeModal());
            break;
          case 'change':
            // eslint-disable-next-line no-case-declarations
            const input = document.getElementById(storageData[1]);
            if (input) {
              // eslint-disable-next-line prefer-destructuring
              input.checked = storageData[2];
            }
            break;
        }
      }, { passive: true });

      if (document.readyState === 'interactive') {
        sscm.findScriptTags();
      } else {
        window.addEventListener('DOMContentLoaded', sscm.findScriptTags, { passive: true });
      }

      sscm.fireDatalayerEvents();

      initResolve();

      document.dispatchEvent(sscm.createEvent('sscmInitialConsent'));
    } catch (err) {
      // eslint-disable-next-line no-console
      console.error('Setono Sylius Consent Management Plugin', err);
    }

    delete sscm.init;
  },

  /**
   * Internal function: Wrapper function used when doing "animations" such as showing or hiding modal, popup and son
   * on. It handles catching errors
   * @param {Function<Promise>} actions
   */
  doAction(actions) {
    if (!sscm.error) {
      sscm.actionPromise = sscm.actionPromise
        .then(actions)
        .catch(sscm.actionError);
    }
  },

  /**
   * Internal function: Handles errors caught by the doAction function.
   * @param {Error} err
   */
  actionError(err) {
    sscm.error = true;
    // eslint-disable-next-line no-console
    console.error('Setono Sylius Consent Management Plugin', err);
    sscm.hidePopup();
    sscm.hideBanner();
    sscm.closeModal();
  },

  /**
   * Internal function: Handles setup of the modal and popup (adds event listeners)
   */
  setup() {
    if (!document.forms[sscmFormName]) {
      throw (new Error('The sscm consent form is missing.'));
    }

    sscm.widgetForm = document.forms[sscmFormName];

    sscm.elmModal = document.getElementById('sscm-w-modal');
    sscm.elmPopup = document.getElementById('sscm-w-popup');
    sscm.elmPopupText = document.getElementById('sscm-wpb-text');
    sscm.elmPopupClose = document.getElementById('sscm-wpbb-close');
    sscm.elmPopupReload = document.getElementById('sscm-wpbb-reload');

    sscm.elmPopupClose.addEventListener('click', () => {
      sscm.doAction(() => sscm.hidePopup());
    }, { passive: true });

    sscm.elmPopupReload.addEventListener('click', () => {
      sscm.doAction(() => sscm.reloadPage()
        .then(() => sscm.setPopup('wait'))
        .finally(() => window.location.reload()));
    }, { passive: true });

    sscm.widgetForm.addEventListener('submit', sscm.consentSubmit, { passive: false });

    sscm.widgetForm.addEventListener('change', (e) => {
      if (e.target.nodeName === 'INPUT') {
        localStorage.setItem('sscmComs', JSON.stringify(['change', e.target.id, e.target.checked]));
        localStorage.removeItem('sscmComs');
      }
    }, { passive: true });
  },

  /**
   * Internal function: Finds script elements which are awaiting for consent and subscribes them.
   */
  findScriptTags() {
    [...document.getElementsByTagName('script')].forEach((/** HTMLScriptElement */ elmScript) => {
      if (elmScript.dataset.sscmConsent) {
        sscm.subscribeScript(elmScript);
      }
    });
  },

  /**
   * Internal function: Initiates an attempt to submit the consent choices.
   * @param {SubmitEvent} e
   */
  consentSubmit(e) {
    e.preventDefault();

    const formData = new FormData(sscm.widgetForm);

    /** @type {sscmConsentGranted} */
    const selectedConsent = Object.fromEntries(sscmConsentTypes.map((c) => [c, formData.has(`${sscmFormName}[${c}Granted]`)]));

    /** @type {Array<sscmConsentAvailable>} */
    const changedConsent = [];

    sscmConsentTypes.forEach((c) => {
      if (sscm.consent[c] !== selectedConsent[c]) {
        changedConsent.push(c);
      }
    });

    if (sscm.decided && changedConsent.length === 0) {
      sscm.doAction(() => Promise.all([sscm.hideBanner(), sscm.closeModal()])
        .then(() => {
          localStorage.setItem('sscmComs', JSON.stringify(['close']));
          localStorage.removeItem('sscmComs');
        }));
      return; // Consent did not change.
    }

    const xhr = new XMLHttpRequest();

    xhr.timeout = 5000;

    xhr.onload = () => {
      if (xhr.status === 204) {
        sscm.submitSuccess(selectedConsent, changedConsent);
      } else {
        sscm.submitError();
      }
    };
    xhr.onerror = sscm.submitError;
    xhr.ontimeout = xhr.onerror;
    // TODO. Håndtering af abort? Nok ikke nødvendigt.
    // xhr.onabort = xhr.onerror;

    xhr.open('POST', sscm.elmWidget.dataset.sscmAction);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    sscm.doAction(() => Promise.all([sscm.hideBanner(), sscm.closeModal()])
      .then(() => sscm.setPopup('save'))
      .then(sscm.showPopup)
      .then(() => new Promise((resolve) => {
        localStorage.setItem('sscmComs', JSON.stringify(['saving']));
        localStorage.removeItem('sscmComs');

        xhr.send(new URLSearchParams(formData));
        setTimeout(resolve, 1000);
      })));
  },

  /**
   * Internal function: Handles successful submits of the consent choices.
   * @param {sscmConsentGranted} selectedConsent
   * @param {Array<sscmConsentAvailable>} changedConsent
   * @param {boolean} initiator=true If set to false other tabs won't be informed about the success via localStorage.
   */
  submitSuccess(selectedConsent, changedConsent, initiator = true) {
    sscmConsentTypes.forEach((c) => {
      sscm.consent[c] = selectedConsent[c];
      sscm.widgetForm.querySelector(`input[name="${sscmFormName}[${c}Granted]"]`).checked = sscm.consent[c];
    });

    let reload = false;

    sscm.fireDatalayerEvents();

    Object.keys(sscm.subscribers).forEach((subId) => {
      if (changedConsent.some((c) => sscm.subscribers[subId].needs.includes(c))) {
        const consentGranted = sscm.isConsentGranted(sscm.subscribers[subId].needs);

        // eslint-disable-next-line default-case
        switch (typeof sscm.subscribers[subId].target) {
          case 'function':
            if (sscm.subscribers[subId].target(consentGranted) && !reload && sscm.subscribers[subId].init) {
              reload = true;
            }
            break;

          case 'object':
            if (consentGranted) {
              if (!sscm.subscribers[subId].init) {
                sscm.loadScript(sscm.subscribers[subId].target);
                sscm.subscribers[subId].init = true;
              }
            } else if (!reload && sscm.subscribers[subId].init) {
              reload = true;
            }
            break;
        }
      }
    });

    document.dispatchEvent(sscm.createEvent('sscmConsentUpdated'));

    if (reload && sscm.decided) {
      sscm.doAction(() => sscm.setPopup('reload')
        .then(() => {
          if (initiator) {
            localStorage.setItem('sscmComs', JSON.stringify(['success', selectedConsent, changedConsent]));
            localStorage.removeItem('sscmComs');
          }
        }));
    } else {
      sscm.doAction(() => sscm.hidePopup()
        .then(() => {
          if (initiator) {
            localStorage.setItem('sscmComs', JSON.stringify(['success', selectedConsent, changedConsent]));
            localStorage.removeItem('sscmComs');
          }
        }));
    }

    sscm.decided = true;
  },

  /**
   * Internal function: Handles errors when trying to submit the consent choices.
   * @param {boolean} initiator=true If set to false other tabs won't be informed about the error via localStorage.
   */
  submitError(initiator = true) {
    sscmConsentTypes.forEach((c) => {
      sscm.widgetForm.querySelector(`input[name="${sscmFormName}[${c}Granted]"]`).checked = sscm.consent[c];
    });
    sscm.doAction(() => sscm.setPopup('error')
      .then(() => {
        if (initiator) {
          localStorage.setItem('sscmComs', JSON.stringify(['error']));
          localStorage.removeItem('sscmComs');
        }
      }));
  },

  /**
   * Internal function: Creates custom events which contains a snapshot of the consent status at the time of creation.
   * @param {string} name
   * @return {CustomEvent<{consent: sscmConsentGranted}>}
   */
  createEvent(name) {
    return new CustomEvent(name, {
      bubbles: true,
      cancelable: false,
      detail: {
        consent: { ...sscm.consent },
      },
    });
  },

  /**
   * Internal function: Pushes 'events' about consent status into the global dataLayer array.
   */
  fireDatalayerEvents() {
    if (window.dataLayer == null) {
      return;
    }

    try {
      sscmConsentTypes.forEach((v) => {
        if (sscm.consent[v]) {
          window.dataLayer.push({ event: `${v}Granted` });
        } else if (sscm.decided) {
          window.dataLayer.push({ event: `${v}Revoked` }); // TODO Skal der også gives besked når brugeren fjerner consent?
        }
      });
    } catch (err) {
      // eslint-disable-next-line no-console
      console.error('Setono Sylius Consent Management Plugin', err);
    }
  },

  /**
   * @return {Promise}
   */
  reloadPage() {
    return new Promise((resolve, reject) => {
      if (sscm.elmPopup && sscm.elmWidget.dataset.sscmShow === 'popup') {
        requestAnimationFrame(() => {
          sscm.elmWidget.dataset.sscmShow = 'reload';
          resolve();
        });
      } else {
        reject(new Error('Internal error #1'));
      }
    });
  },

  /**
   * @return {Promise}
   */
  hideBanner() {
    return new Promise((resolve) => {
      if (sscm.elmBanner && sscm.elmWidget.dataset.sscmShow === 'banner') {
        requestAnimationFrame(() => {
          sscm.elmWidget.dataset.sscmShow = '';
          if (sscm.options.hideBanner) {
            sscm.options.hideBanner(resolve, sscm.elmBanner);
          } else {
            resolve();
          }
        });
      } else {
        resolve();
      }
    });
  },

  /**
   * @return {Promise}
   */
  openModal() {
    return new Promise((resolve, reject) => {
      if (sscm.elmModal) {
        if (sscm.elmWidget.dataset.sscmShow !== 'modal') {
          requestAnimationFrame(() => {
            sscm.elmWidget.dataset.sscmShow = 'modal';
            if (sscm.options.openModal) {
              sscm.options.openModal(resolve, sscm.elmModal);
            } else {
              resolve();
            }
          });
        } else {
          resolve();
        }
      } else {
        reject(new Error('Internal error #2'));
      }
    });
  },

  /**
   * @return {Promise}
   */
  closeModal() {
    return new Promise((resolve) => {
      if (sscm.elmModal && sscm.elmWidget.dataset.sscmShow === 'modal') {
        requestAnimationFrame(() => {
          sscm.elmWidget.dataset.sscmShow = '';
          if (sscm.options.closeModal) {
            sscm.options.closeModal(resolve, sscm.elmModal);
          } else {
            resolve();
          }
        });
      } else {
        resolve();
      }
    });
  },

  /**
   * @return {Promise}
   */
  showPopup() {
    return new Promise((resolve, reject) => {
      if (sscm.elmPopup) {
        if (sscm.elmWidget.dataset.sscmShow !== 'popup') {
          requestAnimationFrame(() => {
            sscm.elmWidget.dataset.sscmShow = 'popup';
            if (sscm.options.showPopup) {
              sscm.options.showPopup(resolve, sscm.elmPopup);
            } else {
              resolve();
            }
          });
        } else {
          resolve();
        }
      } else {
        reject(new Error('Internal error #3'));
      }
    });
  },

  /**
   * @return {Promise}
   */
  hidePopup() {
    return new Promise((resolve) => {
      if (sscm.elmPopup && sscm.elmWidget.dataset.sscmShow === 'popup') {
        requestAnimationFrame(() => {
          sscm.elmWidget.dataset.sscmShow = '';
          if (sscm.options.hidePopup) {
            sscm.options.hidePopup(resolve, sscm.elmPopup);
          } else {
            resolve();
          }
        });
      } else {
        resolve();
      }
    });
  },

  /**
   * Internal function: Sets which text to show in the popup.
   * @param {'save','reload','error','wait'} state
   * @return {Promise}
   */
  setPopup(state) {
    return new Promise((resolve, reject) => {
      if (sscm.elmPopup) {
        requestAnimationFrame(() => {
          switch (state) {
            case 'save':
              sscm.elmPopupText.dataset.sscmText = 'save';
              sscm.elmPopupClose.disabled = true;
              sscm.elmPopupReload.disabled = true;
              break;
            case 'reload':
              sscm.elmPopupText.dataset.sscmText = 'reload';
              sscm.elmPopupClose.disabled = false;
              sscm.elmPopupReload.disabled = false;
              break;
            case 'error':
              sscm.elmPopupText.dataset.sscmText = 'error';
              sscm.elmPopupClose.disabled = false;
              sscm.elmPopupReload.disabled = false;
              break;
            case 'wait':
              sscm.elmPopupText.dataset.sscmText = 'wait';
              sscm.elmPopupClose.disabled = true;
              sscm.elmPopupReload.disabled = true;
              break;
            default:
              reject(new Error('Internal error #4'));
              return;
          }
          resolve();
        });
      } else {
        reject(new Error('Internal error #5'));
      }
    });
  },

  /**
   * Subscribes callbacks that are run when changes to consent happens.
   *  @param {Array<sscmConsentAvailable>} consentNeeded This array contains the consents that the subscriber needs
   *  granted by the user.
   *  @param {sscmConsentCallback} consentCallback The callback is called right away if sscm is ready (init() has run)
   *  or when sscm is ready later. After this the callback is called each time consent changes (only happens if the
   *  consent changes affects any of the selected consent types).
   */
  subscribeCallback(consentNeeded, consentCallback) {
    if (typeof consentNeeded !== 'object' || consentNeeded.length === 0 || consentNeeded.some((c) => !sscmConsentTypes.includes(c)) || typeof consentCallback !== 'function') {
      // eslint-disable-next-line no-console
      console.error('Setono Sylius Consent Management Plugin', new Error('Incorrect arguments supplied to sscmSubscribe'));
      return;
    }
    const subId = sscm.counter++;
    sscm.subscribers[subId] = { needs: consentNeeded, init: false, target: consentCallback };
    sscm.initPromise.then(() => {
      consentCallback(sscm.isConsentGranted(consentNeeded));
      sscm.subscribers[subId].init = true;
    });
  },

  /**
   * Internal function: Used to add script elements to the consent subscriber list. Ones sscm.init() has run it will
   * load the script if the needed consent has been granted. Otherwise the scripts are loaded ones the consent has
   * been granted later.
   *  @param {HTMLScriptElement} elmScript
   */
  subscribeScript(elmScript) {
    const consentNeeded = elmScript.dataset.sscmConsent.split(',');
    if (consentNeeded.length === 0 || consentNeeded.some((c) => !sscmConsentTypes.includes(c))) {
      // eslint-disable-next-line no-console
      console.error('Setono Sylius Consent Management Plugin', new Error('Incorrect data-sscm-consent on script tag'));
      return;
    }
    const subId = sscm.counter++;
    sscm.subscribers[subId] = { needs: consentNeeded, init: false, target: elmScript };
    sscm.initPromise.then(() => {
      if (sscm.isConsentGranted(consentNeeded)) {
        sscm.loadScript(elmScript);
        sscm.subscribers[subId].init = true;
      }
    });
  },

  /**
   * Internal function: Returns true if all the consent types listed in the consentNeeded array are set to true in
   * sscm.consent. Invalid entries in consentNeeded are ignored.
   * @param {Array<sscmConsentAvailable>} consentNeeded
   * @return {boolean}
   */
  isConsentGranted(consentNeeded) {
    return sscmConsentTypes.every((v) => !consentNeeded.includes(v) || sscm.consent[v]);
  },

  /**
   * Internal function: Used to activate waiting script elements with either an external src (which is stored in
   * attribute data-sscm-src) or inline code. It will create a new script element, add either the src attribute or the
   * inner code, correct type attribute and finally replace the original script element with the new one.
   * @param {HTMLScriptElement} elmScript
   */
  loadScript(elmScript) {
    const script = document.createElement('script');
    if (elmScript.dataset.sscmSrc) {
      script.setAttribute('src', elmScript.dataset.sscmSrc);
    } else {
      script.innerHTML = elmScript.innerHTML;
    }
    requestAnimationFrame(() => {
      elmScript.replaceWith(script);
    });
  },

  /**
   * This function returns the current status of the selected consent types. Use when you need to check consent at
   * runtime of your scripts.
   * @param {Array<sscmConsentAvailable>} consentNeeded This array contains the consent types that needs to be granted
   * for the function to return true.
   * @return {boolean} Only returns true if all consent types in 'consentNeeded' have been granted, otherwise returns
   * false in all other cases (also if consentNeeded has an incorrect value).
   */
  consentGranted(consentNeeded) {
    if (typeof consentNeeded !== 'object' || consentNeeded.length === 0 || consentNeeded.some((c) => !sscmConsentTypes.includes(c))) {
      // eslint-disable-next-line no-console
      console.error('Setono Sylius Consent Management Plugin', new Error('Incorrect arguments supplied to sscmSubscribe'));
      return false;
    }
    return sscm.isConsentGranted(consentNeeded);
  },
};

export const sscmInit = sscm.init;

export const sscmSubscribe = sscm.subscribeCallback;

export const sscmGranted = sscm.consentGranted;
