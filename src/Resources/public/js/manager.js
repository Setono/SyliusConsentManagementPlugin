/**
 * @typedef {Object} ConsentManagerOptions
 * @property {Object} selector
 * @property {string} selector.categories - Selector for the consented categories element
 */
class ConsentManager {
    /**
     * @type {ConsentManagerOptions}
     */
    #options;

    /**
     * @param {ConsentManagerOptions} options
     */
    constructor(options = {}) {
        this.#options = Object.assign({
                selector: {
                    categories: '#sscm-consented-categories-json',
                },
            },
            options
        );

        document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', this.#init.bind(this)) : this.#init();
    }

    #init() {
        const categoriesElement = document.querySelector(this.#options.selector.categories);
        if(null === categoriesElement) {
            throw new Error('Categories element not found. Selector was: ' + this.#options.selector.categories);
        }

        const categories = JSON.parse(categoriesElement.textContent);
        if(!Array.isArray(categories)) {
            throw new Error('Categories element does not contain a valid JSON array');
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

        this.#dispatchEvents(categories);
    }

    /**
     * @param {Array<string>} categories
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
     * @param {Array<string>} categories
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
     * @param {Array<string>} categories
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

new ConsentManager(window.sscmManager || {});
