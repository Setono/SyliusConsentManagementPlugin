/**
 * @typedef {object} ConsentManagerOptions
 * @property {boolean} displayWidget
 * @property {string[]} consentedCategories
 */
export default class ConsentManager {
    /**
     * @type {ConsentManagerOptions}
     */
    #options;

    /**
     * @param {ConsentManagerOptions} options
     */
    constructor(options = {}) {
        this.#options = Object.assign({
            displayWidget: false,
            consentedCategories: [],
        }, options);

        if(this.#options.displayWidget) {
            import('./consent-widget.js').then((module) => {
                window.sscmWidget = new module.default(window.sscmWidgetOptions || {});
            });
        }

        /**
         * This is the event dispatched inside the widget.js when a user posts the widget form
         */
        document.addEventListener('sscm:consent:updated', (event) => {
            this.#dispatchEvents(event.detail.categories);
        });

        document.addEventListener('sscm:consent:granted', (event) => {
            this.#loadScripts(event.detail.categories);
        });

        this.#dispatchEvents(this.#options.consentedCategories);
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
        if (undefined === window.dataLayer) {
            return;
        }

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
            if (script.dataset.sscmSrc) {
                elm.setAttribute('src', script.dataset.sscmSrc);
            } else {
                elm.innerHTML = script.innerHTML;
            }

            script.replaceWith(elm);
        });
    }
}
