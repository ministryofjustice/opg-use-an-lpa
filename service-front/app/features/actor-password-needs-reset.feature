@actor @passwordNeedsReset
Feature: A user of the system is able to login
  As a user of the lpa application
  I can login using my credentials
  So that I can carry out operations within the application

  Background:
    Given I am a user of the lpa application
    And I have been given access to use an LPA via credentials

  @ui
  Scenario: A user is requested to reset password if password security compromised
    Given I access the login form
    When I sign successfully
    Then I am requested to reset my password

  @ui
  Scenario: A user is able to reset password if password security compromised
    Given My password security is compromised and requested to reset my password on login
    When I request for my password to be reset
    Then I receive an email and shown unique instructions on how to reset my password
