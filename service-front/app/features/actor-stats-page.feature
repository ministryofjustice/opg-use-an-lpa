@actor @actorStats
Feature: Stats page
  As a user
  I want to view stats about the service

  @ui
  Scenario: Check I am able to view the stats page
    Given I am on the stats page
    Then I can see user accounts table