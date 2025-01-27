/**
 * @typedef {Object} ConsentWidgetOptions
 * @property {Object} selector
 * @property {string} selector.backdrop - Selector for the backdrop element
 * @property {string} selector.widget - Selector for the widget container
 * @property {Object} callback
 * @property {Function} callback.acceptAll - Callback function to call when the accept all button is clicked. The first argument is the consent widget object
 * @property {Function} callback.acceptSelected - Callback function to call when the accept selected button is clicked. The first argument is the consent widget object
 */
class ConsentWidget {
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
        this.#options = Object.assign({
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

        document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', this.#init.bind(this)) : this.#init();
    }

    #init() {
        this.#backdrop = document.querySelector(this.#options.selector.backdrop);
        if(null === this.#backdrop) {
            throw new Error('Backdrop element not found. Selector was: ' + this.#options.selector.backdrop);
        }

        this.#widget = document.querySelector(this.#options.selector.widget);
        if(null === this.#widget) {
            throw new Error('Widget element not found. Selector was: ' + this.#options.selector.widget);
        }

        this.#widget.querySelector('form').addEventListener('submit', (event) => {
            event.preventDefault();

            const action = event.submitter.dataset.action;

            if(!['acceptAll', 'acceptSelected'].includes(action)) {
                throw new Error('Invalid action');
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
                console.error(error);
            });

            this.#widget.style.display = 'none';
            this.#backdrop.style.display = 'none';
        });
    }

    #checkAll() {
        this.#widget.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
            checkbox.checked = true;
        });
    }
}

new ConsentWidget(window.sscmWidget || {});
