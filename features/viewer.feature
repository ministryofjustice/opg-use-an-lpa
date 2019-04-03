@viewer @web
Feature: View a lasting power of attorney
  As a person working for a bank,
  I want to enter a code for a lasting power of attorney that has been shared with me,
  So I can view information about the lasting power of attorney.

  Scenario: Load the enter code page
    Given I go to the viewer page on the internet
    When I click Start Now
    Then The enter code page is displayed