import {
    PerformanceAnalytics,
    ErrorAnalytics,
} from "@ministryofjustice/opg-performance-analytics";

export default class GoogleAnalytics {
    constructor(analyticsId)
    {
        this.analyticsId = analyticsId;
        this._setUpOnLoad();
    }

    _setUpOnLoad()
    {
        let s = document.createElement('script');
        s.type = 'text/javascript';
        s.src = `https://www.googletagmanager.com/gtag/js?id=${this.analyticsId}`;
        document.getElementsByTagName('head')[0].appendChild(s);
        window.dataLayer = window.dataLayer || [];
        window.gtag = function () {
            window.dataLayer.push(arguments);
        }
        window.gtag('js', new Date());
        window.gtag('config', this.analyticsId, {
            'linker': {
                'domains': ['www.gov.uk']
            },
            'transport_type': 'beacon',
            'anonymize_ip': true, // https://developers.google.com/analytics/devguides/collection/gtagjs/ip-anonymization
            'allow_google_signals': false, // https://developers.google.com/analytics/devguides/collection/gtagjs/display-features
            'allow_ad_personalization_signals': false, // https://developers.google.com/analytics/devguides/collection/gtagjs/display-features
            'page_title' : document.title,
            'page_path': `${location.pathname.split('?')[0]}`
        });
        this._trackExternalLinks();
        this._trackFormValidationErrors();
        this._trackFromValidationErrorsWithoutLink(); // done as separate method to stop assumptions breaking original functionality
        this._trackLpaDownload();
        this._trackAccessCodeReveal();

        PerformanceAnalytics();
        ErrorAnalytics();
    }

    trackEvent(action, category, label, value = "")
    {
        window.gtag('event', this._sanitiseData(action), {
            'event_category': this._sanitiseData(category),
            'event_label': this._sanitiseData(label),
            'value': this._sanitiseData(value)
        });
    }

    _sanitiseData(data)
    {
        const sanitisedDataRegex = [
            /[^\s=/?&]+(?:@|%40)[^\s=/?&]+/g, // Email
            /[A-PR-UWYZ][A-HJ-Z]?[0-9][0-9A-HJKMNPR-Y]?(?:[\\s+]|%20)*[0-9][ABD-HJLNPQ-Z]{2}/gi, // Postcode
            /^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/g, // Date
            /^(((\+44\s?\d{4}|\(?0\d{4}\)?)\s?\d{3}\s?\d{3})|((\+44\s?\d{3}|\(?0\d{3}\)?)\s?\d{3}\s?\d{4})|((\+44\s?\d{2}|\(?0\d{2}\)?)\s?\d{4}\s?\d{4}))(\s?\#(\d{4}|\d{3}))?$/g, // Telephone
        ];

        let dataCleansed = data;

        for (let i = 0; i < sanitisedDataRegex.length; i++) {
            dataCleansed = dataCleansed.replace(sanitisedDataRegex[i], '[sanitised]');
        }

        return dataCleansed;
    }

    _trackExternalLinks()
    {
        const externalLinkSelector = document.querySelectorAll('a[href^="http"]');
        const _this = this;
        for (let i = 0; i < externalLinkSelector.length; i++) {
            externalLinkSelector[i].addEventListener("click", function (e) {
                _this.trackEvent('click', 'outbound', this.href);
            });
        }
    }

    _trackFromValidationErrorsWithoutLink()
    {
        console.log("Tracking Errors")
        let errorFields = document.getElementsByClassName('govuk-error-summary__list')
        if(errorFields.length > 0) {
            errorFields = errorFields[0].getElementsByTagName("a");
            errorFields = [].slice.call(errorFields);
            let formErrors = errorFields.filter(x => x.getAttribute('href') !== '' && x.getAttribute('href') !== '#');
            formErrors.forEach(x => this.trackEvent('Form', 'Form errors', x.textContent));
        }
    }
    _trackFormValidationErrors()
    {
        let errorFields = document.getElementsByClassName('govuk-form-group--error');
        for (let i = 0, len = errorFields.length; i < len; i++) {
            let labelElement = errorFields[i].getElementsByTagName('label')[0];
            let label = labelElement.textContent.trim();
            let inputId = labelElement.getAttribute('for');
            // there can be more than one error message per field eg password rules
            let errorMessages = (errorFields[i].querySelectorAll('.govuk-error-message'));
            for (let x = 0, len = errorMessages.length; x < len; x++) {
                let errorMessage = errorMessages[x].textContent.replace("Error:", "").trim();
                this.trackEvent(label, 'Form errors', ('#' + inputId + ' - ' + errorMessage));
            }
        }
    }

    _trackLpaDownload()
    {
        const _this = this;
        let downloadLinkSelector = document.querySelector('a[href$="/download-lpa"]');
        if (downloadLinkSelector) {
            downloadLinkSelector.addEventListener('click', function (e) {
                _this.trackEvent('Download', 'LPA summary', 'Download this LPA summary');
            });
        }
    }

    _trackAccessCodeReveal()
    {
        const _this = this;
        let accessCodeRevealSelector = document.querySelector("#access-code-reveal");
        if (accessCodeRevealSelector) {
            accessCodeRevealSelector.addEventListener('click', function (e) {
                _this.trackEvent('AccessCodeReveal', 'Access code', 'The code I\'ve been given does not begin with a V');
            });
        }
    }
}
