export class ConsentWidget {
    document;
    dialogSelector;
    modalSelector;
    options;
    defaults = {
        'closeModal': function(e) {
            $('.sscm-consent-modal').modal('hide');
        },
        'dialogSelector': '.sscm-consent-dialog',
        'formName': 'setono_sylius_consent_management_consent',
        'modalSelector': '.sscm-consent-modal',
        'moreInformationButtonSelector': '.sscm-btn-more-information',
        'moreInformationClickHandler': function(e) {
            this.document.querySelector('.sscm-consent-container').style.display = 'none';
            $('.sscm-consent-modal')
                .modal({
                    keyboardShortcuts: false,
                    closable: false
                })
                .modal('show')
            ;
        }
    };

    constructor(document, modalSelector, options) {
        this.document = document;
        this.dialogSelector = dialogSelector;
        this.modalSelector = modalSelector;
        this.options = Object.assign(this.defaults, options);

        this.init();
    }

    init() {
        this.document.addEventListener('sscm:initial-consent', runScriptTags);
        this.document.addEventListener('sscm:initial-consent', fireDatalayerEvents);
        this.document.addEventListener('sscm:consent-updated', runScriptTags);
        this.document.addEventListener('sscm:consent-updated', fireDatalayerEvents);

        const moreInfoBtn = this.document.querySelector(this.options.moreInformationButtonSelector);

        if(null !== moreInfoBtn) {
            moreInfoBtn.addEventListener('click', (e) => {
                this.options.moreInformationClickHandler(e);
            });
        }
    }
}
