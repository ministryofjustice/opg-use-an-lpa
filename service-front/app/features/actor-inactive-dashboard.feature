@actor @actorInactiveDashboard
Feature: The user is able to see correct information on their dashboard
  As a user
  I want access to use an LPA removed for actors who have been made inactive on my LPA
  So that they are not able to use my LPA through this service that they do not have authority to use

  Background:
    Given I am a user of the lpa application
    And I am currently signed in
    And I have added an LPA to my account
    And I am inactive against the LPA on my account

  @ui
  Scenario:
    Given an attorney can be removed from acting on a particular LPA
    When I am on the dashboard page
    Then I can see authority to use the LPA is revoked
    And I cannot make access codes for the LPA
    And I cannot check existing or inactive access codes for the LPA
    And I cannot view the LPA summary

  @ui
  Scenario:
    Given I am on the dashboard page
    When I navigate to give an organisation access
    Then I am shown a not found error

  @ui
  Scenario:
    Given I am on the dashboard page
    When I navigate to check an access code
    Then I am shown a not found error

  @ui
  Scenario:
    Given I am on the dashboard page
    When I navigate to view the LPA summary
    Then I am shown a not found error
