@actor @actorDeleteAccount
Feature: The user is able to delete their account
  As a user
  I want to be able to delete my account
  If I no longer want to use the service

  Background:
    Given I am a user of the lpa application
    And I am currently signed in

  @ui
  Scenario: As a user I am asked to confirm to delete my account if I have requested to do so
    Given I am on the your details page
    When I request to delete my account
    Then I am asked to confirm whether I am sure if I want to delete my account

  @ui
  Scenario: As a user I can go back to the your details page if I change my mind about deleting my account
    Given I am on the confirm account deletion page
    When I request to return to the your details page
    Then I am taken back to the your details page

  @integration @ui
  Scenario: As a user I can delete my account
    Given I am on the your details page
    When I request to delete my account
    And I confirm that I want to delete my account
    Then My account is deleted
    And I am logged out of the service and taken to the deleted account confirmation page

  @ui
  Scenario: As a user I cannot access my account once it has been deleted
    Given I have deleted my account
    When I request login to my account that was deleted
    Then I am told my credentials are incorrect
