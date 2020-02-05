from behave import *
from selenium.webdriver.common.keys import Keys
import json
from modules import get_frontend_url


# GIVENS
@given('I go to the viewer service homepage')
def step_impl(context):
    context.browser.get(get_frontend_url('view'))


@given('I go to the enter code page on the viewer service')
def step_impl(context):
    context.browser.get(get_frontend_url('view', '/enter-code'))


@given('the share code input is populated with "{share_code}" and "{donor_surname}"')
def step_impl(context, share_code, donor_surname):
    share_code_input = context.browser.find_element_by_css_selector(
        'form[name="share_code"] input[name="lpa_code"]')
    share_code_input.clear()  # Be sure the input is empty
    share_code_input.send_keys(share_code)
    donor_surname_input = context.browser.find_element_by_css_selector(
        'form[name="share_code"] input[name="donor_surname"]')
    donor_surname_input.clear()  # Be sure the input is empty
    donor_surname_input.send_keys(donor_surname)


# WHENS
@when('I click the "Start now" button')
def step_impl(context):
    button_element = context.browser.find_element_by_xpath(
        '//a[contains(concat(" ", @class, " "), " govuk-button ")][@href="/enter-code"]')
    button_element.click()


@when('I click the "Continue" button')
def step_impl(context):
    button_element = context.browser.find_element_by_xpath(
        '//a[contains(concat(" ", @class, " "), " govuk-button ")][@href="/view-lpa"]')
    button_element.click()


@when('I click on the "{help_link}" help section')
def step_impl(context, help_link):
    help_summary = context.browser.find_element_by_xpath(
        '//summary/span[contains(text(),"' + help_link + '")]')
    help_summary.click()


@when('the share code form is submitted')
def step_impl(context):
    share_code_form = context.browser.find_element_by_css_selector(
        'form[name="share_code"]')
    share_code_form.submit()


# THENS
@then('the "{page_heading}" page is displayed')
def step_impl(context, page_heading):
    page_heading_element = context.browser.find_element_by_xpath(
        '//h1[contains(text(),"' + page_heading + '")]')
    assert page_heading_element.text == page_heading


@then('error message "{error_message}" is displayed in the error summary')
def step_impl(context, error_message):
    error_message_element = context.browser.find_element_by_xpath(
        '//div[contains(concat(" ", @class, " "), " govuk-error-summary__body ")]//a[contains(text(),"' + error_message + '")]')
    assert error_message_element.text == error_message


@then('error message "{error_message}" is displayed next to the {input_label} input')
def step_impl(context, error_message, input_label):
    error_message_element = context.browser.find_element_by_xpath(
        '//label[contains(text(), "' + input_label + '")]/../span[contains(concat(" ", @class, " "), " govuk-error-message ")]')
    assert error_message in error_message_element.text

@then('another error message "{error_message}" is displayed next to the {input_label} input')
def step_impl(context, error_message, input_label):
    error_message_element = context.browser.find_element_by_xpath(
        '//label[contains(text(), "' + input_label + '")]/../span[contains(concat(" ", @class, " "), " govuk-error-message ")][2]')
    assert error_message in error_message_element.text


# STEPS
@step('the "{help_link}" help section is {visibility}')
def step_impl(context, help_link, visibility):
    help_link_element = context.browser.find_element_by_xpath(
        '//span[contains(concat(" ", @class, " "), " govuk-details__summary-text ")][contains(text(), "' + help_link + '")]')
    help_text = context.browser.find_element_by_css_selector(
        '.govuk-details__text')

    if visibility == 'visible':
        assert help_text.size.get('height') > 0
        assert help_text.size.get('width') > 0
    elif visibility == 'not visible':
        # Check that the help text is of zero size (can't used is_displayed() here because it will be true')
        assert help_text.size.get('height') == 0
        assert help_text.size.get('width') == 0
