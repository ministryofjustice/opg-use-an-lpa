from behave import *
from selenium.webdriver.common.keys import Keys
import json
import os


def get_viewer_url():
  workspace = os.getenv('TF_WORKSPACE', 'development')
  if workspace == "production":
    dns_namespace = ""
  else:
    dns_namespace = workspace + "."

  VIEWER_URL = 'https://viewer.{}use-an-lpa.opg.service.justice.gov.uk'.format(dns_namespace)
  print(VIEWER_URL)
  return VIEWER_URL

VIEWER_URL = get_viewer_url()

@given('I go to the viewer page on the internet')
def step_impl(context):
  context.browser.get(VIEWER_URL)
 
@when('I click Start Now')
def step_impl(context):
  start_button= context.browser.find_element_by_xpath("//a[@href='/enter-code']")
  start_button.click()

@then('The enter code page is displayed')
def step_impl(context):
  assert context.browser.find_element_by_css_selector('#share-code')
