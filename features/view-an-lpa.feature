@viewer @web
Feature: View a lasting power of attorney
  As a person working for a bank,
  I want to enter a code for a lasting power of attorney that has been shared with me,
  So I can view information about a lasting power of attorney to make decisions about how I should serve a customer.

  Scenario: Go to the service homepage
    Given I go to the viewer service homepage
    Then the "View a lasting power of attorney" page is displayed

  Scenario: Start the service when I click on the button
    Given I go to the viewer service homepage
    When I click the "Start now" button
    Then the "Enter the LPA access code" page is displayed

  Scenario: Show an error message when I try to search for an LPA without entering an LPA code or donor surname
    Given I go to the enter code page on the viewer service
    When the share code form is submitted
    Then error message "Value is required and can't be empty" is displayed in the error summary
    And error message "Value is required and can't be empty" is displayed next to the LPA access code input
    And error message "Value is required and can't be empty" is displayed next to the Donor surname input

  Scenario: Show an error message when I search for an LPA with an LPA code that is the wrong format
    Given I go to the enter code page on the viewer service
    And the share code input is populated with "wrong-format" and "donor-surname"
    When the share code form is submitted
    Then error message "Enter an LPA share code in the correct format." is displayed in the error summary
    And error message "Enter an LPA share code in the correct format." is displayed next to the LPA access code input

  Scenario: Show an error page when I search for an LPA with an invalid LPA code and surname that does not exist
    Given I go to the enter code page on the viewer service
    And the share code input is populated with "111111111111" and "test-surname"
    When the share code form is submitted
    Then the "We could not find an LPA matching those details" page is displayed

  Scenario: Show an error page when I search for an LPA with an LPA code that has expired
    Given I go to the enter code page on the viewer service
    And the share code input is populated with "n4kb-ebez-mnjf" and "Sanderson"
    When the share code form is submitted
    Then the "Expired code" page is displayed

  Scenario: Show the confirmation page when I search for an LPA with a valid LPA code and matching donor's surname
    Given I go to the enter code page on the viewer service
    And the share code input is populated with "P9H8 A6ml D3AM" and "Sanderson"
    When the share code form is submitted
    Then the "Is this the LPA you want to view?" page is displayed

  Scenario: Show the wrong details help comment on confirmation code page when I click the link
    Given I go to the enter code page on the viewer service
    And the share code input is populated with "P9H8-A6ML-D3AM" and "Sanderson"
    When the share code form is submitted
    Then the "Is this the LPA you want to view?" page is displayed
    Given the "If you need to access this LPA after" help section is not visible
    When I click on the "If you need to access this LPA after" help section
    Then the "If you need to access this LPA after" help section is visible

  Scenario: Show the full LPA details for an active LPA when I click "Continue" on the confirmation page
    Given I go to the enter code page on the viewer service
    And the share code input is populated with "P9H8-A6ML-D3AM" and "Sanderson"
    When the share code form is submitted
    Then the "Is this the LPA you want to view?" page is displayed
    When I click the "Continue" button
    Then the "Rachel Sanderson" page is displayed
