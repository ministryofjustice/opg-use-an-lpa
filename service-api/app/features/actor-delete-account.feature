@actor @actorDeleteAccount
Feature: The user is able to delete their account
  As a user
  I want to be able to delete my account
  If I no longer want to use the service

  Background:
    Given I am a user of the lpa application
    And I am currently signed in

  @acceptance
  Scenario: As a user I can delete my account
    Given I am on the your details page
    When I request to delete my account
    And I confirm that I want to delete my account
    Then My account is deleted
    And I am logged out of the service and taken to the index page
