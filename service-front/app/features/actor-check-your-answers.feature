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

  @wip
  Scenario: The user continues to see instructions when provided data matches
    Given I have requested an activation key with valid details
    When I click on Continue in the check answers page
    Then I am taken to the <activation key sent confirmation page> based on the provided data match
    And I can see instructions on what happens next

  @wip
  Scenario: The user shown message cannot find LPA when data does not match
    Given I have requested an activation key with valid details
    When I click on Continue in the check answers page
    Then I am taken to the <cannot find LPA page> based on the provided data match
    And I can see instructions on what to do next

  @wip
  Scenario: The user shown message cannot send activation key
    Given I have requested an activation key with valid details
    When I click on Continue in the check answers page
    Then I am taken to the <cannot send activation key> based on the LPA details retrieved
    And I can see the reasons


