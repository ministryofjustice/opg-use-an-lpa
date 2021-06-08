@actor @actorBlankDashboard
Feature: The user is able to see a dashboard where their LPAs will be shown when registered
  As a user
  I want to be able to see information about the dashboard and to create a new LPA
  So that I can learn more about the service and start to use it by registering for an LPA

  Background:
    Given I am a user of the lpa application
    And I am currently signed in
    And I have not added any LPAs to my account

  @ui
  Scenario: As a new user What Can I do with my LPAs is always closed by default
    Given I have not added any LPAs to my account
    When I am on the dashboard page
    Then I can see that the What I can do link is closed