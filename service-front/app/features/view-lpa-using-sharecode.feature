@viewer @viewlpa
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

  @ui
  Scenario Outline: The user is shown the correct error messages when entering invalid details
    Given I am on the enter code page
    When I request to view an LPA with an invalid access code of "<accessCode>"
    Then I am told that my input is invalid because <reason>

    Examples:
      | accessCode | reason |
      | T3ST ACE2-C0D3 | LPA access codes are 13 numbers and letters long and start with a V |
      | T3STP*22C0!? | LPA access codes are 13 numbers and letters long and start with a V |
      | T3ST - PA22 - C0D3 | LPA access codes are 13 numbers and letters long and start with a V |
      | T3STPA22C0D | LPA access codes are 13 numbers and letters long and start with a V |
      |  | Enter the LPA access code |

  @ui
  Scenario Outline: The user is shown the correct error messages when entering invalid details
    Given I am on the enter code page
    When I request to view an LPA with an invalid donor's surname of "<surname>"
    Then I am told that my input is invalid because <reason>

    Examples:
      | surname | reason |
      |  | Enter the donor's surname |
