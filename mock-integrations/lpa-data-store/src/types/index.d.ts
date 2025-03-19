import { context, logger, respond } from '@imposter-js/types';

type Context = typeof context & {
  operation: Operation
}

interface Operation {
  operationId: string
}

declare let extended_context: Context
export {extended_context as context, respond, logger}
