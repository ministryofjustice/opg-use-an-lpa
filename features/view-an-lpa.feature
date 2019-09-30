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
    Then the "Enter Code" page is displayed

  Scenario: Show the share code help comment on enter code page when I click the link
    Given I go to the enter code page on the viewer service
    And the "What is a share code?" help section is not visible
    When I click on the "What is a share code?" help section
    Then the "What is a share code?" help section is visible

  Scenario: Show an error message when I try to search for an LPA without entering an LPA code
    Given I go to the enter code page on the viewer service
    When the share code form is submitted
    Then error message "Value is required and can't be empty" is displayed in the error summary
    And error message "Value is required and can't be empty" is displayed next to the LPA code input

  Scenario: Show an error message when I search for an LPA with an LPA code that is the wrong format
    Given I go to the enter code page on the viewer service
    And the share code input is populated with "wrong-format"
    When the share code form is submitted
    Then error message "Enter an LPA share code in the correct format." is displayed in the error summary
    And error message "Enter an LPA share code in the correct format." is displayed next to the LPA code input

  Scenario: Show an error page when I search for an LPA with an LPA code that does not exist
    Given I go to the enter code page on the viewer service
    And the share code input is populated with "1111-1111-1111"
    When the share code form is submitted
    Then the "Invalid Code" page is displayed

  Scenario: Show an error page when I search for an LPA with an LPA code that has expired
    Given I go to the enter code page on the viewer service
    And the share code input is populated with "N4KB-EBEZ-MNJF"
    When the share code form is submitted
    Then the "Expired Code" page is displayed

  Scenario: Show the confirmation page when I search for an LPA with a valid LPA code
    Given I go to the enter code page on the viewer service
    And the share code input is populated with "P9H8-A6ML-D3AM"
    When the share code form is submitted
    Then the "Is this the LPA you want to view?" page is displayed
    And an LPA summary for a Property and finance LPA for donor Rachel Sanderson is displayed

  Scenario: Show the wrong details help comment on confirmation code page when I click the link
    Given I go to the enter code page on the viewer service
    And the share code input is populated with "P9H8-A6ML-D3AM"
    When the share code form is submitted
    Then the "Is this the LPA you want to view?" page is displayed
    Given the "What to do if the details are wrong" help section is not visible
    When I click on the "What to do if the details are wrong" help section
    Then the "What to do if the details are wrong" help section is visible

  Scenario: Show the full LPA details for an active LPA when I click "Continue" on the confirmation page
    Given I go to the enter code page on the viewer service
    And the share code input is populated with "P9H8-A6ML-D3AM"
    When the share code form is submitted
    Then the "Is this the LPA you want to view?" page is displayed
    When I click the "Continue" button
    Then the "Rachel Sanderson's property and finance LPA" page is displayed
