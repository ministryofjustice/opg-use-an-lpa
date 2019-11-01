from behave import *
import json
import requests
from modules import get_frontend_url

HEALTHCHECK_URL = get_frontend_url("view") + '/healthcheck'

# View-An-LPA
@given('I fetch the healthcheck endpoint')
def step_impl(context):
    context.url = HEALTHCHECK_URL
    context.headers = {'content-type': 'application/json'}
    context.res = requests.get(context.url, headers=context.headers)

    assert not hasattr(context.res, 'error')
