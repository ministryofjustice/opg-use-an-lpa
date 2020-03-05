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
