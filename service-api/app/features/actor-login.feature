@actor @login
Feature: A user of the system is able to login
  As a user of the lpa application
  I can login using my credentials
  So that I can carry out operations within the application

  Background:
    Given I am a user of the lpa application
    And I have been given access to use an LPA via credentials

  @acceptance @integration
  Scenario: A user can login
    Given I access the login form
    When I enter correct credentials
    Then I am signed in

  @acceptance @integration
  Scenario: A user cannot login with an incorrect password
    Given I access the login form
    When I enter incorrect login password
    Then I am told my credentials are incorrect

  @acceptance @integration
  Scenario: A user cannot login with an incorrect email
    Given I access the login form
    When I enter incorrect login email
    Then my account cannot be found

  @acceptance @integration
  Scenario: A user cannot login if they have not activated their account
    Given I have not activated my account
    And I access the login form
    When I enter correct credentials
    Then I am told my account has not been activated
