import { context, logger, respond } from '@imposter-js/types';

interface Context extends context {
  request: Request;
  operation: Operation;
}
interface Operation {
  getOperationId(): string;
}

declare let extended_context: Context;
export {extended_context as context, respond, logger};
