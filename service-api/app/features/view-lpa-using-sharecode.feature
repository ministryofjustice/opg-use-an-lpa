@viewer @viewlpa
Feature: View an LPA via sharecode
  As an organisation who has been given a share code
  I can enter that code and see the details of an LPA
  So that I can carry out business functions

  @integration @acceptance
  Scenario: View an LPA
    Given I have been given access to an LPA via share code
    And I access the viewer service
    When I give a valid LPA share code
    And I confirm the LPA is correct
    Then I can see the full details of the valid LPA

  @integration @acceptance
  Scenario: View a cancelled LPA
    Given I have been given access to a cancelled LPA via share code
    And I access the viewer service
    When I give a valid LPA share code
    And I confirm the LPA is correct
    Then I can see the full details of a cancelled LPA

  @acceptance @integration
  Scenario: The user can see an option to re enter code if the displayed LPA is incorrect
    Given I have been given access to a cancelled LPA via share code
    And I access the viewer service
    And I give a valid LPA share code
    When I realise the LPA is incorrect
    Then I want to see an option to re-enter code

  @acceptance @integration
  Scenario: The user should have an option to go back to check another LPA from summary page
    Given I have been given access to an LPA via share code
    And I access the viewer service
    And I give a valid LPA share code
    When I confirm the LPA is correct
    Then I can see the full details of the valid LPA
    And I want to see an option to check another LPA

