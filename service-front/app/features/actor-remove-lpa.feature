@actor @actorDeleteLpa
Feature: The user is able to delete an added LPA from their account
  As a user
  I want to be able to delete an added LPA

  Background:
    Given I am a user of the lpa application
    And I am currently signed in
    And I have added an LPA to my account

  @ui @integration
  Scenario: As a user I am asked to confirm removal of LPA if I have requested to do so
    Given I am on the dashboard page
    When I request to remove the added LPA
    Then I am asked to confirm whether I am sure if I want to delete lpa

  @ui @integration
  Scenario: As a user I can go back to the dashboard page if I change my mind about deleting the LPA
    Given I am on the confirm lpa deletion page
    When I request to return to the dashboard page
    Then I am taken back to the dashboard page

  @ui @integration
  Scenario: As a user I no more see the removed LPA details on the dashboard
    Given I am on the dashboard page
    When I request to remove the added LPA
    And I confirm removal of the LPA
    Then The deleted LPA will not be displayed on the dashboard
    And I can see a flash message for the removed LPA
