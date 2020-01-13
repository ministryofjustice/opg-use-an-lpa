const pa11y = require('pa11y');
const cli = require('pa11y-reporter-cli');
/*

Pa11y checks for common accessibility issues in your pages' code. More documentation at https://github.com/pa11y/pa11y-ci

It is not a replacement for understanding what accessibility is and how to build for it.

Inclusive design patterns is a good starting point if you need to undertand that: https://www.amazon.co.uk/Inclusive-Design-Patterns-Heydon-Pickering-ebook/dp/B01MAXK8XR

It checks code quality, so won't pick up content issues such as a picture of an apple with a text alternative of "orange" or a form label that it not correct for the input in question.

*/

(async function () {

    const config = {
        hideElements: 'svg', //svg fallback images currently cause issues due to fallback images beneath a role=presentation
    };    

    const auth_config = {...config}; 

    //actions API is new so may change
    auth_config.actions=[
        'set field #username to test@test.com', //check user
        'set field #password to password1', //check pwd
        'click element #submit',
    ]

    //set of page tests to run, array of pa11y calls returning promises
    const full_results = await Promise.all([
        
        //actor side local homepage
        pa11y('http://localhost:9001', config),

        //actor side login page pre login
        pa11y('http://localhost:9001/login', config),

        //actor side initial page post login actions
        pa11y('http://localhost:9001/login', auth_config),

        //view side local homepage
        pa11y('http://localhost:9002', config)

        //you can add additional page tests here
    ]);

    full_results.map(function(result){
        const cliResults = cli.results(result);
        console.log(cliResults);
    });

})();