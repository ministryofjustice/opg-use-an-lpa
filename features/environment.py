from selenium import webdriver
from selenium.webdriver.firefox.options import Options

options = Options()
options.headless = True

def before_scenario(context, scenario):
  if 'web' in context.tags:
    context.browser = webdriver.Firefox(options=options)
    context.browser.implicitly_wait(10)
 
def after_scenario(context, scenario):
  if 'web' in context.tags:
    context.browser.quit()
