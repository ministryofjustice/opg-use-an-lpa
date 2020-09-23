@viewer @contactUs
Feature: View contact us from footer of page
  As an organisation using the service
  I want to check the contact information of the Office of the Public Guardian
  So that I can know details for communications

  @ui
  Scenario: The user can access the contact us link from the footer of pages
    Given I am on the enter code page
    When I request to see the contact us details
    Then I can see the contact us page

  @ui
  Scenario: Viewer can access the feedback link from the contact us page
    Given I am on the contact us page
    When I navigate to the call charges page
    Then I am taken to the call charges page

  @ui
  Scenario: Viewer is taken back to previous page from the contact us page
    Given I am on the enter code page
    When I request to see the contact us details
    Then I can see the contact us page
    When I click the Back link on the page
    Then I am taken back to the enter code page
