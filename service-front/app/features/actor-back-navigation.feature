@actor @back_navigation
Feature: Back navigation
  As a user
  I want the back function to take me to an appropriate page for where I am in the journey
  So that I can easily navigate the service

  @ui
  Scenario: Check back function on reset password page
    Given I access the login form
    And I have forgotten my password
    And I am on the password reset page
    When I click the Back link on the page
    Then I should be taken to the <login> page

  @ui
  Scenario: Check back function on change password page
    Given I am a user of the lpa application
    And I am currently signed in
    And I view my user details
    And I ask to change my password
    When I click the Back link on the page
    Then I should be taken to the <your details> page

  @ui
  Scenario: Check back function on add LPA page
    Given I am a user of the lpa application
    And I am currently signed in
    And I have been given access to use an LPA via credentials
    And I am on the add an LPA page
    When I click the Back link on the page
    Then I should be taken to the <dashboard> page

  @ui
  Scenario: Check back function on check LPA page
    Given I am a user of the lpa application
    And I am currently signed in
    And I have been given access to use an LPA via credentials
    And I am on the add an LPA page
    When I request to add an LPA with valid details using xyuphwqrechv which matches XYUPHWQRECHV
    And I am on the check LPA page
    When I click the Back link on the page
    Then I should be taken to the <add a lpa> page

    @ui
    Scenario: Check back function on death notification page
      Given I am a user of the lpa application
      And I am currently signed in
      And I am on the death notification page
      When I click the Back link on the page
      Then I should be taken to the <change details> page
