import { ExpiryReason } from '../enum';

interface LpaCode {
  code: string,
  active: boolean,
  actor: string,
  last_updated_date: string,
  lpa: string,
  dob: string,
  expiry_date?: number | string,
  expiry_reason?: ExpiryReason | string,
  generated_date: string,
  status_details?: string,
  comment: string
  has_paper_verification_code?: boolean,
}

interface ExpireRequest {
  code?: string,
  lpa?: string,
  actor?: string,
  expiry_reason: ExpiryReason | string,
}

export { LpaCode, ExpireRequest };
