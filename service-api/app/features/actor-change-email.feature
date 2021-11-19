@actor @changeEmail
Feature: Change email
  As a user
  I want to be able to change my account email address
  So that I receive emails from the service to my new email address

  Background:
    Given I am a user of the lpa application
    And I am currently signed in

  @acceptance @integration
  Scenario: The user cannot change their email address because their password is incorrect
    Given I am on the change email page
    When I request to change my email with an incorrect password
    Then I should be told that I could not change my email because my password is incorrect

  @acceptance @integration
  Scenario: The user cannot request to change their email address because the email chosen belongs to another user on the service
    Given I am on the change email page
    When I request to change my email to an email address that is taken by another user on the service
    Then I should be told my email change request was successful

  @acceptance @integration
  Scenario: The user cannot request to change their email address because another user has requested to change their email to this and token has not expired
    Given I am on the change email page
    When I request to change my email to an email address that another user has requested to change their email to but their token has not expired
    Then I should be told my email change request was successful

  @acceptance @integration
  Scenario: The user can request to change their email address that another user has requested to change their email to this but their token has expired
    Given I am on the change email page
    When I request to change my email to an email address that another user has requested to change their email to but their token has expired
    Then I should be sent an email to both my current and new email
    And I should be told that my request was successful

  @acceptance
  Scenario: The user cannot request to change their email address without their id
    Given I am on the change email page
    When I request to change my email to an email address without my id
    Then I should be told that a bad request was made

  @acceptance
  Scenario: The user cannot request to change their email address without their new email address
    Given I am on the change email page
    When I request to change my email to an email address without my new email
    Then I should be told that a bad request was made

  @acceptance
  Scenario: The user cannot request to change their email address without their password
    Given I am on the change email page
    When I request to change my email to an email address without my password
    Then I should be told that a bad request was made

  @acceptance @integration
  Scenario: The user can request to change their email address
    Given I am on the change email page
    When I request to change my email to a unique email address
    Then I should be sent an email to both my current and new email
    And I should be told that my request was successful

  @acceptance @integration
  Scenario: The user can change their email address with a valid email token
    Given I have requested to change my email address
    And My email reset token is still valid
    When I click the link to verify my new email address
    Then My account email address should be reset
    And I should be able to login with my new email address

  @acceptance @integration
  Scenario: The user cannot change their email address with an expired email token
    Given I have requested to change my email address
    When I click the link to verify my new email address after my token has expired
    Then I should be told that my email could not be changed

  @acceptance @integration
  Scenario: The user cannot change their email address with an email token that doesnt exist
    Given I have requested to change my email address
    When I click an old link to verify my new email address containing a token that no longer exists
    Then I should be told that my email could not be changed
