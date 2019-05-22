from selenium import webdriver
from selenium.webdriver.firefox.options import Options

options = Options()
options.headless = True

def before_tag(context, tag):
  if tag == 'web':
    context.browser = webdriver.Firefox(options=options)
    context.browser.implicitly_wait(10)

def after_tag(context, tag):
  if tag == 'web':
    context.browser.quit()
