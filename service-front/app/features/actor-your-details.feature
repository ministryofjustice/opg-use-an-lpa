@actor @settings
Feature: Settings dashboard
  As a user
  If I have created an account
  I can request to change my log in details any time

  Background:
    Given I am a user of the lpa application
    And I am currently signed in

#  UML-3344 Temporarily disabled tests on changing email addresses
#  @ui @ff:allow_gov_one_login:false
#  Scenario: The user can request to see their details and reset their details
#    Given I view my user details
#    Then I can change my email if required
#    And I can change my passcode if required

  @ui @ff:allow_gov_one_login:false
  Scenario: The user can request login details reset
    Given I view my user details
    When I ask for a change of donors or attorneys details
    Then Then I am given instructions on how to change donor or attorney details

  @ui @ff:allow_gov_one_login:true
    Scenario: The user sees a link to GOV.UK One Login settings when logged in via One Login
    Given I view my user details
    When I click the govuk-settings-link link on the page
    Then I am taken to the GOV.UK settings page