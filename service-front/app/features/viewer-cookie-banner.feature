@viewer @cookie-consent
Feature: Cookie consent
  As a user
  I want to see the cookie consent banner when I visit the service
  So that I can set my cookie preferences

  @ui
  Scenario: See cookie consent banner
    Given I want to view a lasting power of attorney
    When I access the service home page
    Then I see a cookie consent banner

  @ui
  Scenario: See options to accept or set cookie preference
    Given I want to view a lasting power of attorney
    When I access the service home page
    Then I see Accept all cookies and Set cookie preferences button

  @ui
  Scenario: Cookie banner disappears  when I accept all cookies
    Given I have seen the cookie banner
    When I click on Accept all cookies
    Then I should not see a cookie banner

  @ui
  Scenario: Navigates to cookie preference page when I click on Set cookie preferences
    Given I want to view a lasting power of attorney
    When I access the service home page
    Then I see a cookie consent banner
    And I click on Set cookie preferences button
    Then I am on the cookie preferences page

  @ui
  Scenario Outline: Save changes option in cookie preferences page
    Given I have seen the cookie banner
    And I click on Set cookie preferences button
    When I am on the cookie preferences page
    Then I see options to Use cookies that measure my website use and Do not use cookies that measure my website use
    And I choose an <option> and save my choice
    Then I should be on the home page of the service
    And I should not see a cookie banner

    Examples:
      |option|
      |Use cookies that measure my website use        |
      |Do not use cookies that measure my website use |

  @ui
  Scenario: Check cookie-seen-policy set
    Given I have seen the cookie banner
    And I set my cookie preferences
    Then I have a cookie named "seen_cookie_message"
