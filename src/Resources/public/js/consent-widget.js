"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

var ConsentWidget = /*#__PURE__*/function () {
  function ConsentWidget(document, options) {
    _classCallCheck(this, ConsentWidget);

    _defineProperty(this, "document", void 0);

    _defineProperty(this, "options", void 0);

    _defineProperty(this, "defaults", {
      'closeModal': function closeModal(e) {
        $('.sscm-consent-modal').modal('hide');
      },
      'dialogSelector': '.sscm-consent-dialog',
      'formName': 'setono_sylius_consent_management_consent',
      'modalSelector': '.sscm-consent-modal',
      'moreInformationButtonSelector': '.sscm-btn-more-information',
      'moreInformationClickHandler': function moreInformationClickHandler(e, d) {
        d.querySelector('.sscm-consent-container').style.display = 'none';
        $('.sscm-consent-modal').modal({
          keyboardShortcuts: false,
          closable: false
        }).modal('show');
      }
    });

    this.document = document;
    this.options = Object.assign(this.defaults, options);
    this.init();
  }

  _createClass(ConsentWidget, [{
    key: "init",
    value: function init() {
      var _this = this;

      this.document.addEventListener('sscm:initial-consent', runScriptTags);
      this.document.addEventListener('sscm:initial-consent', fireDatalayerEvents);
      this.document.addEventListener('sscm:consent-updated', runScriptTags);
      this.document.addEventListener('sscm:consent-updated', fireDatalayerEvents);
      var moreInfoBtn = this.document.querySelector(this.options.moreInformationButtonSelector);

      if (null !== moreInfoBtn) {
        moreInfoBtn.addEventListener('click', function (e) {
          _this.options.moreInformationClickHandler(e);
        });
      }
    }
  }]);

  return ConsentWidget;
}();

var _default = new ConsentWidget(document, window.sscmConsentWidget);

exports.default = _default;