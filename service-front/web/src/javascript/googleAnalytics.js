
export default class GoogleAnalytics {
    constructor(analyticsId)
    {
        this.analyticsId = analyticsId;
        this._setUpOnLoad();
    }

    _setUpOnLoad()
    {
         // TO DO - enable analytics and fire off a pageview
         //window.GOVUK.analyticsSetup(window)

        //new class for ga stuff - done

        // check if it is allowed to work? [ref - refunds, global cookie-function]

        //write a method that listens to google events
        // check data presence
        //sanitise it and send it

        let s = document.createElement('script');
        s.type = 'text/javascript';
        s.src = `https://www.googletagmanager.com/gtag/js?id=${this.analyticsId}`;
        document.getElementsByTagName('head')[0].appendChild(s);
        window.dataLayer = window.dataLayer || [];
        this.gtag('js', new Date());
        this.gtag('config', this.analyticsId);
    }

    gtag(arguments){
        window.dataLayer.push(arguments);
    }

    trackEvents(action, category, label, value)
    {
        console.log({action, category, label, value});
        this.gtag('event', action, {
                    'event_category': category,
                    'event_label': label,
                    'value': value
        }
    );


    }

}