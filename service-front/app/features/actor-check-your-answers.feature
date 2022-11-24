@actor @checkYourAnswers

Feature: Check your answers before requesting an activation key
  As a user
  I want to be able to check my answers for requesting an activation key
  So that I can check they are correct or change them if not

  Background:
    Given I am a user of the lpa application
    And I am currently signed in

  @ui
  Scenario: The user can go back and change their entered lpa reference number
    Given I have requested an activation key with valid details
    When I request to go back and change my LPA reference number
    Then I am taken back to the reference number page where I can see my answer and change it
    Then I press continue and I am taken back to the check answers page

  @ui
  Scenario: The user can go back and change their entered names
    Given I have requested an activation key with valid details
    When I request to go back and change my names
    Then I am taken back to the your names page where I can see my answers and change them
    Then I press continue and I am taken back to the check answers page

  @ui
  Scenario: The user can go back and change their entered date of birth
    Given I have requested an activation key with valid details
    When I request to go back and change my date of birth
    Then I am taken back to the date of birth page where I can see my answers and change them
    Then I press continue and I am taken back to the check answers page

  @ui
  Scenario: The user can go back and change their entered postcode
    Given I have requested an activation key with valid details
    When I request to go back and change my postcode
    Then I am taken back to the postcode page where I can see my answers and change them
    Then I press continue and I am taken back to the check answers page
