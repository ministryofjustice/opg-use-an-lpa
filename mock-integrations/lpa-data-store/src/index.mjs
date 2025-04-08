import { getList, getLpa } from './lpas/lpas.mjs';

const opId = context.operation.operationId
logger.info('Operation is ' + opId)

let code = 400
let response = ""

if (opId === 'getLpa') {
  let data = getLpa(context.request.pathParams.uid)

  if (data.uid === undefined) {
    code = 404
  } else {
    code = 200
    response = JSON.stringify(data)
  }
} else if (opId === 'getList') {
  if (context.request.body !== null) {
    let uids = JSON.parse(context.request.body).uids

    code = 200
    response = JSON.stringify(getList(uids))

    logger.info(uids.length + ' lpas requested')
  }
} else if (opId === 'healthCheck') {
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
