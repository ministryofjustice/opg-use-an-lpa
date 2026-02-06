@actor @viewLpa @testing
Feature: View an LPA that I have added to my account
  As a user
  If I have a digital LPA to my account
  I can view how decisions are made my attorneys in the summary
  So that I can check all of its information is correct

  Background:
    Given I am a user of the lpa application
    And I am currently signed in
    And I have a digital LPA to add to account

  @ui @ff:paper_verification:true
  Scenario: The user can add a digital LPA to their account
    Given I am on the add an LPA page
    When I request to add the digital LPA with valid details using W9YRUTPM6RLM
    Then The correct digital LPA is found and I add it
    And The digital LPA is successfully added

  @ui @ff:support_datastore_lpas:true
  Scenario: The user can view an added digital LPA and see how decisions are made
    Given I have added a Combined LPA to my account
    And I am on the dashboard page
    When I request to view the LPA summary where how attorney decisions are made is added
    Then I can see decisions are made Jointly for some decisions, jointly and severally for others

  @ui @ff:support_datastore_lpas:true
  Scenario: No one can make joint decisions info shown when there are multiple attorneys and no attorneys can make joint decisions
    Given I have added a Combined LPA to my account
    And I am on the dashboard page
    When I request to view the LPA summary where no attorneys can make joint decisions
    Then I see that There are no longer attorneys who can make the joint decisions on this LPA

  @ui @ff:support_datastore_lpas:true
  Scenario: Right info shows when only one attorneys can make joint decisions
    Given I have added a Combined LPA to my account
    And I am on the dashboard page
    When I request to view the LPA summary where one attorneys can make joint decisions
    Then I see that The joint decisions for this LPA can now only be made by:

  @ui @ff:support_datastore_lpas:true
  Scenario: Right info shows when multiple but not all attorneys can make joint decisions
    Given I have added a Combined LPA to my account
    And I am on the dashboard page
    When I request to view the LPA summary where two attorneys can make joint decisions
    Then I see that The joint decisions for this LPA can now only be made, and must be agreed, by:
