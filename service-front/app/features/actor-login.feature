@actor @login
Feature: A user of the system is able to login
  As a user of the lpa application
  I can login using my credentials
  So that I can carry out operations within the application

  Background:
    Given I am a user of the lpa application
    And I have been given access to use an LPA via credentials

  @ui
  Scenario: A user can login
    Given I access the login form
    When I enter correct credentials
    Then I am signed in

  @ui
    Scenario: A user cannot login with an incorrect password
    Given I access the login form
    When I enter incorrect login password
    Then I am told my credentials are incorrect

  @ui
  Scenario: An incorrect email will give the same message as an incorrect password
    Given I access the login form
    When I enter incorrect login password
    Then I am told my credentials are incorrect

  @ui
  Scenario: A user cannot login if they have not activated their account
    Given I have not activated my account
    And I access the login form
    When I enter correct credentials
    Then I am told my account has not been activated

  @ui
  Scenario: Visiting the login page when signed in will redirect to the dashboard
    Given I am currently signed in
    When I attempt to sign in again
    Then I am directed to my dashboard

  @ui @integration
  Scenario: A user is taken to the dashboard page when they login, having logged in previously
    Given I am a user of the lpa application
    And I have logged in previously
    When I sign in
    Then I am taken to the dashboard page

  @ui
  Scenario Outline: A user is allowed to login with case insensitive email address
    Given I access the login form
    When I enter correct email with <email_format> and <password> below
    Then I am signed in

    Examples:
      |email_format               |password|
      |TEST@test.com              |pa33w0rd|
      |    TEST@TEST.COM          |pa33w0rd|

  @ui
  Scenario Outline: A user is not allowed to login with improper email address
    Given I access the login form
    When I enter incorrect email with <email_format> and <password> below
    Then I should see relevant <error> message

    Examples:
      |email_format     |password|error                       |
      |TEST@ test. com  |pa33w0rd|Enter a valid email address |