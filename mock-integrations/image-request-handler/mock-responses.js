var lpa = "lpa" + context.request.pathParams.uid.slice(-4);

switch (lpa) {
    case 'lpa0138':
    case 'lpa6361':
    case 'lpa7237':
    case 'lpa0252':
        respond().withExampleName(lpa);
        break;
    case 'lpa0344':
        var lpaStore = stores.open('lpa0344');

        var accessCount = 0;
        if (lpaStore.hasItemWithKey("count")) {
            accessCount = lpaStore.load("count");
        }

        status(lpaStore, accessCount);
        lpaStore.save("count", accessCount + 1);

        respond()
            .withStatusCode(200)
            .withFile("responses/lpa0344.json").template()
            .usingDefaultBehaviour();
        break;

    default:
        // default to bad request
        respond()
            .withStatusCode(404)
            .usingDefaultBehaviour();
        break;
}

function status(store, count)
{
    switch (count) {
        case 0:
            store.save("status", "COLLECTION_NOT_STARTED");
            store.save("urls", "{}")
            break;
        case 1:
        case 2:
        case 3:
            store.save("status", "COLLECTION_IN_PROGRESS");
            break;
        default:
            store.save("status", "COLLECTION_COMPLETE");
            var urls = JSON.stringify({
                "iap-700000000344-instructions": "http://localhost:4010/iap-700000000344-instructions.jpg"
            });
            store.save("urls", urls)
    }
}
