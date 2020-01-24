@actor @addLpa
Feature: Add an LPA
  As a user
  If I have created an account
  I can add an LPA to my account

  Background:
    Given I have been given access to use an LPA via credentials
    And I am a user of the lpa application
    And I am signed in

  @integration @ui
  Scenario: The user can add an LPA to their account
    Given I am on the add an LPA page
    When I request to add an LPA with valid details
    Then The correct LPA is found and I can confirm to add it
    And The LPA is successfully added

  @integration @ui
  Scenario: The user cannot add an LPA to their account
    Given I am on the add an LPA page
    When I request to add an LPA that does not exist
    Then The LPA is not found
    And I request to go back and try again

  @ui
  Scenario Outline: The user cannot add an LPA with an invalid passcode
    Given I am on the add an LPA page
    When I request to add an LPA with an invalid passcode format of "<passcode>"
    Then I am told that my input is invalid and needs to be <reason>

    Examples:
      | passcode | reason |
      | T3ST PA22C0D3 | Your passcode must only include letters, numbers and dashes |
      | T3ST PA22-C0D3 | Your passcode must only include letters, numbers and dashes |
      | T3STP*22C0!? | Your passcode must only include letters, numbers and dashes |
      | T3ST - PA22 - C0D3 | Your passcode must be 12 characters long |
      | T3STPA22C0D | Your passcode must be 12 characters long |
      |  | Enter your one-time passcode |

  @ui
  Scenario Outline: The user cannot add an LPA with an invalid reference number
    Given I am on the add an LPA page
    When I request to add an LPA with an invalid reference number format of "<referenceNo>"
    Then I am told that my input is invalid and needs to be <reason>

    Examples:
      | referenceNo | reason |
      | 7000-00000001 | The reference number must only include numbers |
      | 7000-0000 0001 | The reference number must only include numbers |
      | 7000-0000-ABC! | The reference number must only include numbers |
      | 7000-0000-00011 | The LPA reference number must be 12 numbers long |
      | 70000000000 | The LPA reference number must be 12 numbers long |
      |  | Enter a reference number |


