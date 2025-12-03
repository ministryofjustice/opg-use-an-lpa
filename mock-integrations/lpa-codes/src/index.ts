import { context, logger, respond } from '@imposter-js/types';
import { codeExists, expireCode, getCode, isNotExpired, revokeCode } from './codes/codes';
import { ExpiryReason } from './enum';
import { ExpireRequest } from './types';

// @ts-ignore
const opId = context.operation.operationId
logger.info('Operation is ' + opId)

let code = 400
let response = ""

function unix2date(date: number): string {
  const dateObj = new Date(date * 1000);
  return dateObj.toISOString().substring(0, 10)
}

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

      if (activationCode.has_paper_verification_code) {
        response = JSON.stringify({'actor': activationCode.actor, 'has_paper_verification_code': true})
      } else {
        response = JSON.stringify({'actor': activationCode.actor})
      }
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
} else if (opId === 'api.resources.pvc_validate_route') {
  if (context.request.body !== null) {
    let params = JSON.parse(context.request.body)

    let activationCode = getCode(params.code)
    logger.debug('Loaded code ' + JSON.stringify(activationCode))

    if (
      activationCode !== null
    ) {
      logger.info('Code ' + activationCode.code + ' matched parameters')

      const responseData: Record<string, any> = {
        lpa: activationCode.lpa,
        actor: activationCode.actor,
      }

      if (activationCode.expiry_date !== undefined) {
        responseData.expiry_date = unix2date(activationCode.expiry_date as number)
        responseData.expiry_reason = activationCode.expiry_reason
      }

      response = JSON.stringify(responseData)
      code = 200
    } else {
      code = 404
    }
  }
} else if (opId === 'api.resources.pvc_expire_route') {
  if (context.request.body !== null) {
    const params = JSON.parse(context.request.body) as ExpireRequest

    let key: string
    if (params.lpa !== undefined) {
      key = codeExists(params.lpa, params.actor)?.code
    } else {
      key = params.code
    }

    let activationCode = expireCode(
      key,
      ExpiryReason[params.expiry_reason as keyof typeof ExpiryReason],
    )
    logger.debug('Loaded code ' + JSON.stringify(activationCode))

    if (
      activationCode !== null
    ) {
      logger.info(
        'Code ' +
        activationCode.code +
        ' expires in ' +
        ExpiryReason[activationCode.expiry_reason] +
        ' days'
      )

      const responseData: Record<string, any> = {
        expiry_date: unix2date(activationCode.expiry_date as number)
      }

      response = JSON.stringify(responseData)
      code = 200
    } else {
      code = 404
    }
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
