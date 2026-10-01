import { getLpa } from './lpas/lpas.mjs';

const opId = context.request.path
logger.info('Operation is ' + opId)

let code = 400
let response = ""

if (opId.startsWith('/v1/use-an-lpa/lpas')) {
  let data = getLpa(context.request.pathParams.uid)

  if (data.uId === undefined) {
    code = 404
  } else {
    code = 200
    response = JSON.stringify(data)
  }
} else if (opId === '/v1/use-an-lpa/lpas/requestCode') {
  code = 200
  response = JSON.stringify({'queuedForCleansing': true})
} else if (opId === '/v1/healthcheck') {
  code = 200
  response = JSON.stringify({'status': 'OK'})

  logger.info('healthcheck requested')
}

if (response === '') {
  respond()
    .withStatusCode(code)
    .usingDefaultBehaviour();
} else {
  respond()
    .withStatusCode(code)
    .withData(response)
}
