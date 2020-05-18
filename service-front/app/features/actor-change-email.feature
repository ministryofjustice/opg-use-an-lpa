@actor @changeEmail
Feature: Change email
  As a user
  I want to be able to change my account email address
  So that I receive emails from the service to my new email address

  Background:
    Given I am a user of the lpa application
    And I am currently signed in
    And I am on the change email page

  @ui
  Scenario Outline: The user cannot change their email address because their password is incorrect
    Given I request to change my email with invalid credentials of <email> <password>
    Then I should be told that I could not change my email because <reason>

    Examples:
    | email          | password   | reason                           |
    | test@test.com  | inCorr3ct  | Your password is incorrect       |
