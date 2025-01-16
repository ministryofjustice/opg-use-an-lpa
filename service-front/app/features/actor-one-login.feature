@actor @onelogin
Feature: Authenticate One Login
  As a user of the application
  I can login using GovUK OneLogin
  So that I am sure my account is secure

  @ui
  Scenario: I initiate authentication via one login
    Given I am on the one login page
    When I click the one login button
    Then I am redirected to the redirect page in English

  @ui @welsh
  Scenario: I initiate authentication via one login in Welsh
    Given I am on the one login page
    And I select the Welsh language
    When I click the one login button
    Then I am redirected to the redirect page in Welsh

  @ui @welsh
  Scenario Outline: One Login returns a user error
    Given I have logged in to one login in <language>
    When One Login returns a "<error_type>" error
    Then I am redirected to the login page with a "<error_type>" error and "<error_message>"

  Examples:
    | language | error_type              | error_message                           |
    | English  | access_denied           | Tried to login however access is denied |
    | English  | temporarily_unavailable | One Login is temporarily unavailable    |
    | Welsh    | access_denied           | Mae problem                             |
    | Welsh    | temporarily_unavailable | Mae problem                             |

  @ui
  Scenario Outline: One Login returns a system error
    Given I have logged in to one login in English
    When One Login returns a "<error_type>" error
    Then I should be shown an error page

    Examples:
      | error_type                |
      | unauthorized_client       |
      | invalid_request           |
      | invalid_scope             |
      | unsupported_response_type |
      | server_error              |

  @ui
  Scenario: I am redirected to the dashboard when local account does exist
    Given I have logged in to one login in English
    When I have an email address that matches a local account
    Then I see the LPA dashboard with any LPAs that are in the account

  @ui
  Scenario: I am redirected to the dashboard when I am signed in and visit the homepage
    Given I have logged in to one login in English
    When I have an email address that matches a local account
    When I visit the homepage
    Then I am redirected to the LPA dashboard page

  @ui
  Scenario: I am redirected to an empty dashboard when local account does not exist
    Given I have logged in to one login in English
    When I have an email address that does not match a local account
    Then I see an empty LPA dashboard

  @ui
  Scenario: I am redirected to the dashboard when local account already flagged as one-login
    Given I have logged in to one login in English
    When I have an account whose sub matches a local account
    Then I see the LPA dashboard with any LPAs that are in the account

  @ui
  Scenario: I am redirected to the one login page from the login page
    When I access the login page
    Then I am redirected to the one login page
