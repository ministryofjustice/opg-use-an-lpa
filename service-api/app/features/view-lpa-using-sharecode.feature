@viewer @viewlpa
Feature: View an LPA via sharecode
  As an organisation who has been given a share code
  I can enter that code and see the details of an LPA
  So that I can carry out business functions

  @integration @acceptance @pact
  Scenario: View a cancelled LPA
    Given I have been given access to a cancelled LPA via share code
    And I access the viewer service
    When I give a share code that's been cancelled
    Then I can see a message the LPA has been cancelled

  @acceptance @integration @pact
  Scenario: The user can see an option to re enter code if the displayed LPA is incorrect
    Given I have been given access to a cancelled LPA via share code
    And I access the viewer service
    And I give a valid LPA share code
    When I realise the LPA is incorrect
    Then I want to see an option to re-enter code

  @acceptance @integration @pact
  Scenario: The user should have an option to go back to check another LPA from summary page
    Given I have been given access to an LPA via share code
    When I give a valid LPA share code
    And I want to see an option to check another LPA

  @acceptance @integration @pact 
  Scenario: The user should be able to see instructions
    Given I have been given access to an LPA via share code
    And the LPA has instructions
    When I give a valid LPA share code
    Then I can see instructions images

  @acceptance @integration @pact 
  Scenario: The user should be able to see preferences
    Given I have been given access to an LPA via share code
    And the LPA has preferences
    When I give a valid LPA share code
    Then I can see preferences images

  @acceptance @integration @pact
  Scenario: The user should be able to see instructions and preferences
    Given I have been given access to an LPA via share code
    And the LPA has instructions and preferences
    When I give a valid LPA share code
    Then I can see instructions and preferences images
