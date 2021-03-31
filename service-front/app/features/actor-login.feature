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
  Scenario Outline: A user is allowed to login with case insensitive email address and spaces are trimmed
    Given I access the login form
    When I enter correct email with <email_format> and <password> below
    Then I am signed in
    Examples:
      |email_format                 |password|
      |'lowercase@test.com'         |pa33w0rd|
      |'UPPERCASE@TEST.COM'         |pa33w0rd|
      |'    UNTRIMMED@test.com     '|pa33w0rd|

  @ui
  Scenario Outline: A user is not allowed to login with improper email address, blank email or password
    Given I access the login form
    When I enter incorrect login details with <email_format> and <password> below
    Then I should see relevant <error> message
    Examples:
      |email_format           |password|error                       |
      |GAP TEST@ test. com    |pa33w0rd|Enter an email address in the correct format, like name@example.com |
      |                       |pa33w0rd|Enter an email address in the correct format, like name@example.com |
      |nopassword@test.com    |        |Enter your password         |

  @ui @security
  Scenario: A hacker attempts to forge the full CSRF value
    Given I access the login form
    When I hack the CSRF value with 'ipwnedthissiterequest'
    Then I should see relevant As you have not used this service for over 20 minutes, the page has timed out. We've now refreshed the page - please try to sign in again message

  @ui @security
  Scenario: A hacker attempts to forge the request id from CSRF value
    Given I access the login form
    When I hack the request id of the CSRF value
    Then I should see relevant As you have not used this service for over 20 minutes, the page has timed out. We've now refreshed the page - please try to sign in again message

  @ui @security
  Scenario: A hacker attempts to forge the token from CSRF value
    Given I access the login form
    When I hack the token of the CSRF value
    Then I should see relevant As you have not used this service for over 20 minutes, the page has timed out. We've now refreshed the page - please try to sign in again message

  @ui @security
  Scenario: A hacker cannot access the site with an empty CSRF value
    Given I access the login form
    When I hack the CSRF value with ''
    Then I should see relevant Value is required and can't be empty message
