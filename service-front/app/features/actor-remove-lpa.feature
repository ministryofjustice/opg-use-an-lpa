@actor @actorDeleteLpa
Feature: The user is able to delete an added LPA from their account
  As a user
  I want to be able to delete an added LPA

  Background:
    Given I am a user of the lpa application
    And I am currently signed in
    And I have added an LPA to my account

  @ui @integration
  Scenario: The user is asked to confirm that they want to remove an LPA from their account
    Given I am on the dashboard page
    When I request to remove the added LPA
    Then I am asked to confirm whether I am sure if I want to remove an lpa

  @ui @integration
  Scenario: The user can go back to the dashboard if they change their mind about removing an LPA
    Given I am on the confirm lpa deletion page
    When I request to return to the dashboard page
    Then I am taken back to the dashboard page

  @ui @integration
  Scenario: The user can remove an LPA from their account
    Given I am on the dashboard page
    When I request to remove the added LPA
    And I confirm removal of the LPA
    Then The deleted LPA will not be displayed on the dashboard
    And I can see a flash message for the removed LPA
