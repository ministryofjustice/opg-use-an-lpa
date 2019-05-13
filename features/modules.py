import os
from collections import defaultdict

def get_frontend_url( frontend ):
  """
  Return a URL for a Use an LPA frontend on a specific environment.
  The environment is based on the Terraform Workspace environment variable.
  """
  workspace = os.getenv('TF_WORKSPACE', 'localhost')

  # match workspace to a value in the dict or return a default value "development."
  account_namespace_mapping = defaultdict(lambda: "development.", {'production': "", 'preproduction': "preproduction."})
  dns_account_namespace = account_namespace_mapping[workspace]

  if dns_account_namespace == "development.":
    dns_env_namespace = workspace + "."
  else:
    dns_env_namespace = ""


  if workspace == 'localhost':
    url = 'http://viewer-web'
  else:
    url = 'https://{0}{1}.{2}use-an-lpa.opg.service.justice.gov.uk'.format(dns_env_namespace, frontend, dns_account_namespace)

  print(url)
  return url
