@actor @viewLpa @ff:support_datastore_lpas:true
Feature: View an LPA that I have added to my account
  As a user
  If I have a digital LPA to my account
  I can view how decisions are made my attorneys in the summary
  So that I can check all of its information is correct

  Background:
    Given I am a user of the lpa application
    And I am currently signed in
    And I have added a Combined LPA to my account

  @ui
  Scenario: The user can view an added digital LPA and see how decisions are made
    Given I am on the dashboard page
    When I request to view the LPA summary where how attorney decisions are made is added
    Then I can see decisions are made Jointly for some decisions, jointly and severally for others

  @ui
  Scenario: No one can make joint decisions info shown when there are multiple attorneys and no attorneys can make joint decisions
    Given I am on the dashboard page
    When I request to view the LPA summary where no attorneys can make joint decisions
    Then I see that There are no longer attorneys who can make the joint decisions on this LPA

  @ui
  Scenario: Right info shows when only one attorneys can make joint decisions
    Given I am on the dashboard page
    When I request to view the LPA summary where one attorneys can make joint decisions
    Then I see that The joint decisions for this LPA can now only be made by:

  @ui
  Scenario: Right info shows when multiple but not all attorneys can make joint decisions
    Given I am on the dashboard page
    When I request to view the LPA summary where two attorneys can make joint decisions
    Then I see that The joint decisions for this LPA can now only be made, and must be agreed, by:
