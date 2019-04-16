import os

def get_frontend_url( frontend ):
  """
  Return a URL for a Use an LPA frontend on a specific environment.
  The environment is based on the Terraform Workspace environment variable.
  """
  workspace = os.getenv('TF_WORKSPACE', 'development')
  if workspace == "production":
    dns_namespace = ""
  else:
    dns_namespace = workspace + "."

  URL = 'https://{}.{}use-an-lpa.opg.service.justice.gov.uk'.format(frontend, dns_namespace)
  print(URL)
  return URL