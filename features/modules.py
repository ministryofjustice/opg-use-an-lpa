import os

def get_frontend_url( frontend ):
  """
  Return a URL for a Use an LPA frontend on a specific environment.
  The environment is based on the Terraform Workspace environment variable.
  """
  workspace = os.getenv('TF_WORKSPACE', 'development')

  account_namespace_mapping = {'production': "", 'preproduction': "preproduction."}

  dns_account_namespace = account_namespace_mapping.get(workspace, "development.")

  if dns_account_namespace == "development.":
    dns_env_namespace = workspace + "."
  else:
    dns_env_namespace = ""

  URL = 'https://{0}{1}.{2}use-an-lpa.opg.service.justice.gov.uk'.format(dns_env_namespace, frontend, dns_account_namespace)
  print(URL)
  return URL

