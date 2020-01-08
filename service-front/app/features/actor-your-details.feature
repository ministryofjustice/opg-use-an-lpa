@actor @yourdetails
Feature: YourDetails
  As a user
  If I have created an account
  I can request to change my log in details any time

  Background:
    Given I am a user of the lpa application
    And I am signed in

  @ui
  Scenario: The user can request to see their details and reset their details
    And I view my user details
    When I want my details to be reset
    Then I can change my email if required
    And I can change my passcode if required

  @ui
  Scenario: The user can request login details reset
    When I want my details to be reset
    And  I ask for a change of donors or attorneys details
    Then Then I am given instructions on how to change donor or attorney details

