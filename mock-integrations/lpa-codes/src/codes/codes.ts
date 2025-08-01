import { logger, stores } from '@imposter-js/types';
import { LpaCode } from '../types';
import codeData from './seeding-data';

function loadCodes(): LpaCode[] {
  let codes: LpaCode[] = [];
  const store = stores.open('codeData');

  for (const code of codeData) {
    if (store.hasItemWithKey(code.code)) {
      let alteredCode: LpaCode = JSON.parse(store.load(code.code))
      codes.push(alteredCode)
      logger.debug('Using altered code ' + alteredCode.code);
    } else {
      codes.push(code);
    }
  }

  logger.info('Loaded ' + codes.length + ' codes')
  return codes;
}

function codeExists(lpaUid: string, actorUid: string): LpaCode|null {
  let code = loadCodes().find(({ lpa, actor }) => lpa === lpaUid && actor === actorUid);
  if (code === undefined) {
    logger.debug('Code not found for ' + lpaUid + ' & ' + actorUid);
    return null;
  }

  logger.debug('Code found ' + code.code);
  return code;
}

function getCode(activationCode: string): LpaCode|null {
  let code = loadCodes().find(({ code }) => code === activationCode);
  if (code === undefined) {
    logger.debug('Code not found ' + activationCode);
    return null;
  }

  logger.debug('Code found ' + code.code);
  return code;
}

function revokeCode(activationCode: string): LpaCode|null {
  let code = getCode(activationCode);
  if (code === null || !code.active) {
    return null;
  }

  code.active = false;
  code.status_details = 'Revoked';

  const store = stores.open('codeData');
  store.save(code.code, JSON.stringify(code));

  return code;
}

function isNotExpired(code: LpaCode): boolean {
  if (code.expiry_date === 'valid') {
    logger.debug('Code ' + code.code + ' will never expire')
    return true;
  }

  const ttl = Math.floor((new Date()).getTime() / 1000)

  logger.debug('code date: ' + code.expiry_date + ' ttl: ' + ttl)

  return parseInt(code.expiry_date) > ttl;
}

export {codeExists, getCode, revokeCode, isNotExpired}
