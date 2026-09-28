/**
 * @typedef {Object} ConsentWidgetOptions
 * @property {Array<string>} allowedActions - The allowed actions for widget buttons
 * @property {Object} selector
 * @property {string} selector.backdrop - Selector for the backdrop element
 * @property {string} selector.widget - Selector for the widget container
 * @property {Object} callback
 * @property {Function} callback.acceptAll - Callback function to call when the 'Accept all' button is clicked. The first argument is the consent widget object
 * @property {Function} callback.acceptSelected - Callback function to call when the 'Accept selected' button is clicked. The first argument is the consent widget object
 */
export default class ConsentWidget {
    /**
     * @type {ConsentWidgetOptions}
     */
    #options;

    /**
     * @type {HTMLElement}
     */
    #backdrop;

    /**
     * @type {HTMLElement}
     */
    #widget;

    /**
     * @param {ConsentWidgetOptions} options
     */
    constructor(options = {}) {
        // Merged deeply, so e.g. overriding one callback keeps the other default callbacks
        this.#options = ConsentWidget.#merge({
                allowedActions: ['acceptAll', 'acceptSelected'],
                selector: {
                    backdrop: '.sscm-backdrop',
                    widget: '.sscm-widget-container',
                },
                callback: {
                    acceptAll: function() {
                        this.#checkAll();
                    },
                    acceptSelected: function() {},
                },
            },
            options
        );

        this.#backdrop = document.querySelector(this.#options.selector.backdrop);
        if(null === this.#backdrop) {
            throw new Error('Backdrop element not found. Selector was: ' + this.#options.selector.backdrop);
        }

        this.#widget = document.querySelector(this.#options.selector.widget);
        if(null === this.#widget) {
            throw new Error('Widget element not found. Selector was: ' + this.#options.selector.widget);
        }

        this.#widget.querySelectorAll('button[data-toggle]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();

                button.setAttribute('aria-expanded', 'true');

                const selector = event.currentTarget.dataset.toggle;
                if(typeof selector === 'undefined') {
                    return;
                }

                const element = document.querySelector(selector);
                if(null === element) {
                    throw new Error('Element not found. Selector was: ' + selector);
                }

                button.setAttribute('aria-expanded', element.classList.toggle('sscm-show') ? 'true' : 'false');
            })
        });

        this.#widget.querySelector('form').addEventListener('submit', (event) => {
            event.preventDefault();

            // There is no submitter when the form is submitted otherwise, e.g. with the enter key
            const action = event.submitter?.dataset.action ?? 'acceptSelected';

            if(!this.#options.allowedActions.includes(action)) {
                throw new Error('Invalid action. Allowed actions are: ' + this.#options.allowedActions.join(', '));
            }

            if(!Object.hasOwn(this.#options.callback, action)) {
                throw new Error('Callback function not found for action: ' + action);
            }

            this.#options.callback[action].bind(this)();

            fetch(event.target.action, {
                method: 'POST',
                body: new FormData(event.target),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
            }).then((response) => {
                if (!response.ok) {
                    throw new Error(`Response status: ${response.status}`);
                }

                return response.json();
            }).then((json) => {
                this.#widget.dispatchEvent(new CustomEvent('sscm:consent:updated', {
                    bubbles: true,
                    detail: {
                        categories: json,
                    },
                }));
            }).catch((error) => {
                console.error('The consent could not be saved', error);

                // Show the widget again, so the visitor can retry instead of believing their choice was saved
                this.#show();
            });

            this.#hide();
        });
    }

    #show() {
        this.#widget.style.display = '';
        this.#backdrop.style.display = '';
    }

    #hide() {
        this.#widget.style.display = 'none';
        this.#backdrop.style.display = 'none';
    }

    /**
     * @param {Object} defaults
     * @param {Object} options
     * @returns {Object}
     */
    static #merge(defaults, options) {
        const merged = { ...defaults };

        Object.entries(options).forEach(([key, value]) => {
            const isObject = (candidate) => null !== candidate && 'object' === typeof candidate && !Array.isArray(candidate);

            merged[key] = isObject(value) && isObject(defaults[key]) ? ConsentWidget.#merge(defaults[key], value) : value;
        });

        return merged;
    }

    #checkAll() {
        this.#widget.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
            checkbox.checked = true;
        });
    }
}
