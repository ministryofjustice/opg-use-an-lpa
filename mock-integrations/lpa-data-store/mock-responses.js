var lpa = "lpa" + context.request.pathParams.uid.slice(-4);

switch (lpa) {
    case 'lpa0138':
    case 'lpa6361':
    case 'lpa7237':
    case 'lpa0252':
        respond().withExampleName(lpa);
        break;

    default:
        // default to bad request
        respond()
            .withStatusCode(404)
            .usingDefaultBehaviour();
        break;
}

