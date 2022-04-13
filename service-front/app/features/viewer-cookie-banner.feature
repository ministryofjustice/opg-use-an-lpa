@viewer @cookie-consent
Feature: Cookie consent
  As a user
  I want to see the cookie consent banner when I visit the service
  So that I can set my cookie preferences

  @ui
  Scenario: See cookie consent banner
    Given I want to view a lasting power of attorney
    When I access the service home page
    Then I see a View a lasting power of attorney cookie consent banner

  @ui
  Scenario: See options to accept or set cookie preference
    Given I want to view a lasting power of attorney
    When I access the service home page
    Then I see Accept analytics cookies and Reject analytics cookies button

  @ui
  Scenario: Navigates to cookie preference page when I click on Set cookie preferences
    Given I want to view a lasting power of attorney
    When I access the service home page
    Then I see a View a lasting power of attorney cookie consent banner
    And I click on the view cookies link
    Then I am on the cookie preferences page

#  @ui
#  Scenario Outline: Save changes option in cookie preferences page
#    Given I have seen the View a lasting power of attorney cookie banner
#    And I click on the view cookies link
#    When I am on the cookie preferences page
#    Then I see options Yes and No to accept analytics cookies
#    And I choose an <option> and save my choice
#    Then I should be on the cookies page of the service
#    Then I have a cookie named cookie_policy
#
#    Examples:
#      |option|
#      |Use cookies that measure my website use        |
#      |Do not use cookies that measure my website use |

#  @ui
#  Scenario: Check cookie-seen-policy set
#    Given I have seen the View a lasting power of attorney cookie banner
#    And I set my cookie preferences
#    Then I have a cookie named cookie_policy
