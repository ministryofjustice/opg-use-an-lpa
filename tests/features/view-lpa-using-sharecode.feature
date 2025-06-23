@viewer @viewanlpa
Feature: View an LPA via share code
  As an organisation who has been given a share code
  I can enter that code and see the details of an LPA
  So that I can carry out business functions

  @smoke
  Scenario: Service is only accessible over secure https
    Given I access the viewer service insecurely
    Then the viewer service homepage should be shown securely

  @smoke @ff:paper_verification:false
  Scenario: View an LPA
    Given I have been given access to an LPA via share code
    And I access the viewer service
    And I give a valid LPA share code
    When I enter an organisation name and confirm the LPA is correct
    Then I can see the full details of the valid LPA
