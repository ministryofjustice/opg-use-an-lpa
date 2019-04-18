from behave import *
from selenium.webdriver.common.keys import Keys
import json
from modules import get_frontend_url

VIEWER_URL = get_frontend_url("viewer")

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
