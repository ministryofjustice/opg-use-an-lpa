@actor @termsOfUse
Feature: View terms of use from create account page
  As a user
  I want to check the terms of use
  So that I can be be sure of my rights and responsibilities for using the service

  @ui
  Scenario: The user can access the terms of use from the create account page
    Given I am on the create account page
    When I request to see the actor terms of use
    Then I can see the actor terms of use

  @ui
  Scenario: Actor can access the actor cookies page from the actor terms of use page
    Given I am on the actor terms of use page
    When I navigate to the actor cookies page
    Then I am taken to the actor cookies page
