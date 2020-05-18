@actor @changeEmail
Feature: Change email
  As a user
  I want to be able to change my account email address
  So that I receive emails from the service to my new email address

  Background:
    Given I am a user of the lpa application
    And I am currently signed in

  @ui @integration
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

  @ui
  Scenario: The user cannot change their email address because the email chosen belongs to another user on the service
    Given I am on the change email page
    When I request to change my email to an email address that is taken by another user on the service
    Then I should be told that I could not change my email as their was a problem with the request

  @ui
  Scenario: The user cannot change their email address because another user has requested to change their email to this and token has not expired
    Given I am on the change email page
    When I request to change my email to an email address that another user has requested to change their email to but their token has not expired
    Then I should be told that I could not change my email as their was a problem with the request

  @ui
  Scenario: The user can change their email address that another user has requested to change their email to this but their token has expired
    Given I am on the change email page
    When I request to change my email to an email address that another user has requested to change their email to but their token has expired
    Then I should be logged out
    And I should be told that my request was successful

  @ui
  Scenario: The user can change their email address
    Given I am on the change email page
    When I request to change my email to a unique email address
    Then I should be logged out
    And I should be told that my request was successful
