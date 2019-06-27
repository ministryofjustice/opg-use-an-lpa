from selenium import webdriver
from selenium.webdriver.firefox.options import Options
import os

options = Options()
options.headless = True


def before_tag(context, tag):
    if tag == 'web':
        context.browser = webdriver.Firefox(options=options)
        context.browser.implicitly_wait(20)


def after_tag(context, tag):
    if tag == 'web':
        context.browser.quit()


def after_scenario(context, scenario):
    print("scenario status" + scenario.status)
    if scenario.status == "failed":
        if not os.path.exists("failed_scenarios_screenshots"):
            os.makedirs("failed_scenarios_screenshots")
        os.chdir("failed_scenarios_screenshots")
        context.browser.save_screenshot(scenario.name + "_failed.png")
    context.browser.quit()
