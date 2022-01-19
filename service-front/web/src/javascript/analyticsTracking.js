import {
    PerformanceAnalytics,
    ErrorAnalytics,
} from "@ministryofjustice/opg-performance-analytics";

export default class AnalyticsTracking {
    constructor() {
        this.init();

        PerformanceAnalytics();
        ErrorAnalytics();
    }

    init() {
        const _this = this;
        document.addEventListener('click', (e) => {
            /* istanbul ignore else */
            if (e.target) {
                if (e.target.matches('[data-attribute="ga-event"]')) {
                    _this.processEventElement(e.target)
                } else if (e.target.getAttribute('href') && e.target.getAttribute('href').indexOf('http') === 0) {
                    _this.sendGoogleAnalyticsEvent('click', 'outbound', e.target.getAttribute('href'));
                }

            }

        })

        var observer = new MutationObserver(this.observeMutations);

        observer.observe(document.body, {
            attributes: true,
            subtree: true,
            childList: true,
            attributeOldValue: true,
            attributeFilter: ['open']
        });



        const gaLoadEvents = document.querySelectorAll('[data-attribute="ga-load-event"]');

        for (let i = 0, len = gaLoadEvents.length; i < len; i++) {
            const element = gaLoadEvents[i];
            _this.processEventElement(element)
        }
    }

    extractEventInfo(eventElement) {
        return {
            action: eventElement.getAttribute('data-gaAction'),
            event_params:
            {
                event_category: eventElement.getAttribute('data-gaCategory'),
                event_label: eventElement.getAttribute('data-gaLabel')
            }
        }
    }

    processEventElement(eventElement) {
        /* istanbul ignore else */
        if (typeof window.gtag === 'function') {
            const eventInfo = this.extractEventInfo(eventElement);
            this.sendGoogleAnalyticsEvent(eventInfo.action, eventInfo.event_params.event_category, eventInfo.event_params.event_label);
        }
    }

    sendGoogleAnalyticsEvent(action, category, label, value = "") {
        window.gtag('event', this._sanitiseData(action), {
            'event_category': this._sanitiseData(category),
            'event_label': this._sanitiseData(label),
            'value': this._sanitiseData(value)
        });
    }

    _sanitiseData(data) {
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

    observeMutations(mutations) {
        const _this = this;
        mutations.forEach(function (mutation) {
            if (mutation.type === "attributes") {
                if (mutation.target.getAttribute('data-module') === 'govuk-details') {
                    let eventInfo = _this.extractEventInfo(mutation.target);
                    _this.sendGoogleAnalyticsEvent(eventInfo.action, eventInfo.event_params.event_category,  eventInfo.event_params.event_label + " " + (mutation.oldValue === null ? "open" : "close"));
                }
            }
        });
    }
}
