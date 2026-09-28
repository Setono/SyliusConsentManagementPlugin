/**
 * @typedef {object} ConsentManagerOptions
 * @property {boolean} displayWidget
 * @property {string[]} consentedCategories
 * @property {string|null} widgetScriptUrl - The URL of consent-widget.js. Pass the versioned asset URL, so browsers don't use a stale widget after an upgrade
 */
export default class ConsentManager {
    /**
     * @type {ConsentManagerOptions}
     */
    #options;

    /**
     * @type {string[]}
     */
    #consentedCategories;

    /**
     * @param {ConsentManagerOptions} options
     */
    constructor(options = {}) {
        this.#options = Object.assign({
            displayWidget: false,
            consentedCategories: [],
            widgetScriptUrl: null,
        }, options);

        this.#consentedCategories = [...this.#options.consentedCategories];

        if(this.#options.displayWidget) {
            import(this.#options.widgetScriptUrl ?? './consent-widget.js').then((module) => {
                window.sscmWidget = new module.default(window.sscmWidgetOptions || {});
            }).catch((error) => {
                console.error('The consent widget could not be loaded', error);
            });
        }

        /**
         * This is the event dispatched inside the widget.js when a user posts the widget form
         */
        document.addEventListener('sscm:consent:updated', (event) => {
            this.#consentedCategories = [...event.detail.categories];
            this.#dispatchEvents(event.detail.categories);
        });

        document.addEventListener('sscm:consent:granted', (event) => {
            this.#loadScripts(event.detail.categories);
        });

        this.#dispatchEvents(this.#options.consentedCategories);
    }

    /**
     * @returns {string[]} The categories the visitor has consented to
     */
    getConsentedCategories() {
        return [...this.#consentedCategories];
    }

    /**
     * @param {string} category
     * @returns {boolean}
     */
    isGranted(category) {
        return this.#consentedCategories.includes(category);
    }

    /**
     * Calls the callback right away if the visitor has consented to the category, and otherwise when they do.
     * Unlike listening for the sscm:consent:<category>:granted event, this also works for scripts that run after
     * the events have been dispatched
     *
     * @param {string} category
     * @param {Function} callback
     */
    whenGranted(category, callback) {
        if (this.isGranted(category)) {
            callback();

            return;
        }

        document.addEventListener(`sscm:consent:${category}:granted`, () => callback(), { once: true });
    }

    /**
     * @param {string[]} categories
     */
    #dispatchEvents(categories) {
        document.dispatchEvent(new CustomEvent('sscm:consent:granted', {
            bubbles: true,
            detail: {
                categories: categories,
            },
        }));

        categories.forEach((category) => {
            document.dispatchEvent(new CustomEvent(`sscm:consent:${category}:granted`, { bubbles: true}));
        });

        this.#dispatchDatalayerEvents(categories);
    }

    /**
     * @param {string[]} categories
     */
    #dispatchDatalayerEvents(categories) {
        // A tag manager may define the data layer after this script has run. Creating it, like the GTM snippet does,
        // keeps the events, so the tag manager can process them when it loads
        window.dataLayer = window.dataLayer || [];

        categories.forEach((category) => {
            window.dataLayer.push({ event: `sscm:consent:${category}:granted` });
        });
    }

    /**
     * @param {string[]} categories
     */
    #loadScripts(categories) {
        const scripts = document.querySelectorAll('script[data-sscm-consent]');
        scripts.forEach((script) => {
            const requiredCategories = script.dataset.sscmConsent.split(',');
            if(!requiredCategories.every((category) => categories.includes(category))) {
                return;
            }

            const elm = document.createElement('script');

            // Keep attributes like async, defer, integrity and crossorigin
            for (const attribute of script.attributes) {
                if ('type' === attribute.name || attribute.name.startsWith('data-sscm-')) {
                    continue;
                }

                elm.setAttribute(attribute.name, attribute.value);
            }

            // Browsers hide the nonce attribute's value, but keep it in the property. Without it, a CSP would block the script
            elm.nonce = script.nonce;

            if (script.dataset.sscmSrc) {
                // Inserted scripts run asynchronously by default, which would break the order of scripts that depend on each other
                if (!script.hasAttribute('async')) {
                    elm.async = false;
                }

                elm.src = script.dataset.sscmSrc;
            } else {
                elm.textContent = script.textContent;
            }

            script.replaceWith(elm);
        });
    }
}
