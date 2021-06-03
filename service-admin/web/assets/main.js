import MOJFrontend from '@ministryofjustice/frontend/moj/all.js';
import GOVUKFrontend from 'govuk-frontend/govuk/all.js';
import './main.scss';

// we aren't using the JS tabs, but they try to initialise this will stop them breaking
GOVUKFrontend.Tabs.prototype.setup = () => { };

GOVUKFrontend.initAll();
MOJFrontend.initAll();
