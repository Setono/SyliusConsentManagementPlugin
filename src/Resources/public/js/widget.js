/**
 * Handles the add to wishlist and remove from wishlist actions (i.e. toggling)
 *
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
                    acceptSelected: function() {
                    },
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

        this.#widget.querySelector('form').addEventListener('submit', (event) => {
            event.preventDefault();

            const action = event.submitter.dataset.action;
            if (action === 'acceptAll') {
                this.#options.callback.acceptAll.bind(this)();
            }

            if (action === 'acceptSelected') {
                this.#options.callback.acceptSelected.bind(this)();
            }

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
                console.log(json);
            }).catch((error) => {
                console.error(error);
            });

            this.#backdrop.remove();
            this.#widget.remove();
        });
    }

    #checkAll() {
        this.#widget.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
            checkbox.checked = true;
        });
    }
}

new ConsentWidget(window.sscmWidget || {});
