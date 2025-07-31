import { context, logger, respond } from '@imposter-js/types';
import { codeExists, getCode, isNotExpired, revokeCode } from './codes/codes';

// @ts-ignore
const opId = context.operation.operationId
logger.info('Operation is ' + opId)

let code = 400
let response = ""

if (opId === 'api.resources.handle_healthcheck') {
  code = 200
  response = JSON.stringify('OK')
} else if (opId === 'api.resources.validate_route') {
  if (context.request.body !== null) {
    let params = JSON.parse(context.request.body)

    let activationCode = getCode(params.code)
    logger.debug('Loaded code ' + JSON.stringify(activationCode))

    if (
      activationCode !== null &&
      activationCode.dob === params.dob &&
      activationCode.lpa === params.lpa &&
      activationCode.active === true &&
      isNotExpired(activationCode)
    ) {
      logger.info('Code ' + activationCode.code + ' matched parameters')

      response = JSON.stringify({'actor': activationCode.actor})
    } else {
      response = JSON.stringify({'actor': null})
    }

    code = 200
  }
} else if (opId === 'api.resources.revoke_route') {
  if (context.request.body !== null) {
    let params = JSON.parse(context.request.body)

    let activationCode = revokeCode(params.code)
    logger.debug('Loaded code ' + JSON.stringify(activationCode))

    if (activationCode !== null) {
      logger.info('Code ' + activationCode.code + ' revoked')

      response = JSON.stringify({'codes revoked': 1})
    } else {
      response = JSON.stringify({'codes revoked': 0})
    }

    code = 200
  }
} else if (opId === 'api.resources.actor_code_exists_route') {
  if (context.request.body !== null) {
    let params = JSON.parse(context.request.body)

    let activationCode = codeExists(params.lpa, params.actor)
    logger.debug('Loaded code ' + JSON.stringify(activationCode))

    if (
      activationCode !== null &&
      activationCode.active === true &&
      isNotExpired(activationCode)
    ) {
      logger.info('Code ' + activationCode.code + ' matched parameters')

      response = JSON.stringify({'Created': activationCode.generated_date})
    } else {
      response = JSON.stringify({'Created': null})
    }

    code = 200
  }
}

if (response === '') {
  respond()
    .withStatusCode(code)
    .usingDefaultBehaviour();
} else {
  respond()
    .withStatusCode(code)
    .withHeader('Content-Type', 'application/json')
    .withData(response)
}
