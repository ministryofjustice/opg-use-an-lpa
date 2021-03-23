@viewer @viewanlpa
Feature: Retain a copy of the shared LPA summary
  As an organisation who has been given a share code
  I can download a summary of a LPA
  So that I can retain a copy of the data as it was at the time I accessed it

  @smoke
  Scenario: Download a PDF Summary
    Given I have been given access to an LPA via share code
    And I access the viewer service
    And I give a valid LPA share code
    And I enter an organisation name and confirm the LPA is correct
    And I can see the full details of the valid LPA
    When I click Download this LPA summary
    Then I am given a PDF file of the summary
