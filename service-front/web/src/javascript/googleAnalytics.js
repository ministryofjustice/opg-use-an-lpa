
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
        window.gtag = function() {
            window.dataLayer.push(arguments);
        }

        window.gtag('js', new Date());
        window.gtag('config', this.analyticsId, {
            'linker': {
                'domains': ['www.gov.uk', 'www.research.net']
            },
            'anonymize_ip': true, // https://developers.google.com/analytics/devguides/collection/gtagjs/ip-anonymization
            'allow_google_signals': false, // https://developers.google.com/analytics/devguides/collection/gtagjs/display-features
            'allow_ad_personalization_signals': false // https://developers.google.com/analytics/devguides/collection/gtagjs/display-features
        });
    }

    trackEvent(action, category, label, value)
    {
        window.gtag('event', this._sanitiseData(action), {
                    'event_category': this._sanitiseData(category),
                    'event_label': this._sanitiseData(label),
                    'value': this._sanitiseData(value)
            }
        );
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
}