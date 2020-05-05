@actor @back_navigation
Feature: Back navigation
  As a user
  I want the back function to take me to an appropriate page for where I am in the journey
  So that I can easily navigate the service

  @ui
  Scenario: Check back function on sign in page
    Given I access the use a lasting power of attorney web page
    When I select the option to sign in to my existing account
    And I am not signed in to the use a lasting power of attorney service at this point
    When I click back link on the page
    Then I should be taken to the <start> page

  @ui
  Scenario: Check back function on reset password page
    Given I access the use a lasting power of attorney web page
    When I select the option to sign in to my existing account
    And I have forgotten my password
    And I am on the password reset page
    When I click back link on the page
    Then I should be taken to the <login> page

  @ui
  Scenario: Check back function on change password page
    Given I am a user of the lpa application
    And I am currently signed in
    And I view my user details
    And I ask to change my password
    When I click back link on the page
    Then I should be taken to the <your details> page

  @ui
  Scenario: Check back function on add LPA page
    Given I am a user of the lpa application
    And I am currently signed in
    And I have been given access to use an LPA via credentials
    And I am on the add an LPA page
    When I click back link on the page
    Then I should be taken to the <dashboard> page

  @ui
  Scenario Outline: Check back function on check LPA page
    Given I am a user of the lpa application
    And I am currently signed in
    And I have been given access to use an LPA via credentials
    And I am on the add an LPA page
    When I request to add an LPA with valid details using <passcode>
    And I am on the check LPA page
    When I click back link on the page
    Then I should be taken to the <add a lpa> page

    Examples:
      | passcode       |
      | xyuphwqrechv   |



