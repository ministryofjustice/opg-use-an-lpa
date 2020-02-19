import os
from collections import defaultdict


def get_frontend_url(frontend, path='', protocol="https"):
    # Return a URL for a Use an LPA frontend on a specific environment.
    # The environment is based on the Terraform Workspace environment variable.
    workspace = os.getenv('TF_WORKSPACE', 'localhost')

    if workspace == "production":
        dns_env_namespace = ""
    else:
        dns_env_namespace = workspace + "."

    if workspace == 'localhost':
        url = 'http://viewer-web'
    else:
        url = '{0}://{1}{2}.lastingpowerofattorney.opg.service.justice.gov.uk'.format(
            protocol, dns_env_namespace, frontend)

    return url + path
