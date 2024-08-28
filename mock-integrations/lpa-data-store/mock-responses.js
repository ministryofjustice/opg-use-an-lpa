var lpa = "lpa" + context.request.pathParams.uid.slice(-4);

switch (lpa) {
    case 'lpa4UX3':
        respond().withExampleName(lpa);
        break;

    default:
        // default to bad request
        respond()
            .withStatusCode(404)
            .usingDefaultBehaviour();
        break;
}
