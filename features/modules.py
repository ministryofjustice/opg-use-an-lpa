import os

def get_frontend_url( frontend ):
  workspace = os.getenv('TF_WORKSPACE', 'development')
  if workspace == "production":
    dns_namespace = ""
  else:
    dns_namespace = workspace + "."

  URL = 'https://{}.{}use-an-lpa.opg.service.justice.gov.uk'.format(frontend, dns_namespace)
  print(URL)
  return URL