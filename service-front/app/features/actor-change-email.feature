@actor @changeEmail
Feature: Change email
  As a user
  I want to be able to change my account email address
  So that I receive emails from the service to my new email address

  Background:
    Given I am a user of the lpa application
    And I am currently signed in

  @ui
  Scenario: The user cannot change their email address because their password is incorrect
    Given I am on the change email page
    When I request to change my email with an incorrect password
    Then I should be told that I could not change my email because my password is incorrect

  @ui
  Scenario: The user cannot change their email address because the email is invalid
    Given I am on the change email page
    When I request to change my email to an invalid email
    Then I should be told that I could not change my email because the email is invalid

  @ui
  Scenario: The user cannot change their email address to the same email they have currently
    Given I am on the change email page
    When I request to change my email to the same email of my account currently
    Then I should be told that I could not change my email because the email is the same as my current email
