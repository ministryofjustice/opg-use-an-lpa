from behave import *
import json

@then('I see JSON output')
def step_impl(context):
    context.json_res = json.loads(context.res.content)
    assert context.json_res

@then('it contains a "{name}" key/value pair')
def step_impl(context, name):
    assert name in context.json_res