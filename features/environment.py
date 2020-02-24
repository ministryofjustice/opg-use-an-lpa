from selenium import webdriver
from selenium.webdriver.firefox.options import Options
import os

options = Options()
options.headless = True


def before_tag(context, tag):
    if tag == 'web':
        context.browser = webdriver.Firefox(options=options)
        context.browser.implicitly_wait(12)


def after_tag(context, tag):
    if tag == 'web':
        context.browser.quit()


def after_scenario(context, scenario):
    if scenario.status == "failed":
        if not os.path.exists("features/failed_scenarios_screenshots"):
            os.makedirs("features/failed_scenarios_screenshots")

        print (
            "Saving screenshot to \"%s/features/failed_scenarios_screenshots/%s_failed.png\"" %
            (os.getcwd(), scenario.name)
        )
        context.browser.save_screenshot("features/failed_scenarios_screenshots/" + scenario.name + "_failed.png")
