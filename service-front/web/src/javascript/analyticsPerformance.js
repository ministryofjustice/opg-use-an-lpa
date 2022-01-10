import Perfume from "perfume.js";

export default class AnalyticsPerformance {
    constructor() {
        new Perfume({
            analyticsTracker: (options) =>
                this.AnalyticsTrackerGTag(options),
        });

        if (typeof window.onerror === "object") {
            window.onerror = (err, url, line) => {
                track("exception", {
                    description: `${line}: ${err}`,
                });
            };
        }
    }

    AnalyticsTrackerGTag(options) {
        const { metricName, data } = options;
        const analyticsOptions = {
            name: metricName,
            value: data,
            event_category: "rum",
        };
        this.track("timing_complete", analyticsOptions);
    }

    track(metricAction, analyticsOptions) {
        if (window.gtag) {
            window.gtag("event", metricAction, analyticsOptions);
        }
    }
}
