@actor @onelogin
Feature: Authorise One Login

  @acceptance
  Scenario: I initiate authentication via one login
    Given I am on the temporary one login page
    When I click the one login button
    Then I am redirected to the redirect page