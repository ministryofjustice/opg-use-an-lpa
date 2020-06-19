@actor @privacyNotice
Feature: View privacy notice from the terms of use page
  As a user
  I want to check the privacy notice
  So that I can understand how my private data will be handled by the service

  @ui
  Scenario: user wants to see the privacy notice
    Given I am on the create account page
    When I request to see the actor terms of use
    And I request to see the actor privacy notice
    Then I can see the actor privacy notice

  @ui
  Scenario: The user can go back to the terms of use page from the actor privacy notice page
    Given I am on the actor privacy notice page
    When I request to go back to the terms of use page
    Then I am taken back to the terms of use page
