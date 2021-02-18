@actor @checkYourAnswers

Feature: Check your answers before requesting an activation key
  As a user
  I want to be able to check my answers for requesting an activation key
  So that I can check they are correct or change them if not

  Background:
    Given I am a user of the lpa application
    And I am currently signed in

  @ui
  Scenario: The user can go back and change their answers
    Given I have requested an activation key with valid details
    When I request to go back and change my answers
    Then I am taken back to previous page where I can see my answers and change them
