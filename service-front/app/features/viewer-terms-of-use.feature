@viewer @termsOfUSe
Feature: View an LPA via sharecode
  As an organisation who has been given a share code
  I can enter that code and see the details of an LPA
  So that I can carry out business functions

  @integration @ui
  Scenario: The user can enter a valid sharecode and see the details of an LPA
    Given I have been given access to an LPA via share code
    And I access the viewer service
    And I give a valid LPA share code
    When I confirm the LPA is correct
    Then I can see the full details of the valid LPA