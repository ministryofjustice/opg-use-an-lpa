import * as MOJFrontend from '@ministryofjustice/frontend/moj/all.mjs';
import { Tabs, initAll } from 'govuk-frontend/dist/govuk/all.mjs';

// we aren't using the JS tabs, but they try to initialise this will stop them breaking
Tabs.prototype.setup = () => { };

initAll();
MOJFrontend.initAll();
