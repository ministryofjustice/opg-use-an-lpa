@viewer @viewanlpa
Feature: Instructions and Preferences Images in the LPA summary
  As an organisation who has been given a share code
  I can see instructions and preferences images in the summary of a LPA

  @smoke
  Scenario: View instructions and preferences
    Given I have been given access to an LPA via share code
    And I access the viewer service
    And I give a valid LPA share code
    And I enter an organisation name and confirm the LPA is correct
    Then I can see that the lpa has instructions and preferences images in summary
