@actor @addLpa
Feature: Add an LPA
  As a user
  If I have created an account
  I can add an LPA to my account

  Background:
    Given I am a user of the lpa application
    And I am currently signed in
    And I have been given access to use an LPA via credentials

  @ui
  Scenario: The user cannot add an LPA to their account when status is pending
    Given I am on the add an LPA page
    When I request to add an LPA whose status is pending using xyuphwqrechv
    Then The LPA is not found


  @integration @ui
  Scenario Outline: The user can add an LPA to their account
    Given I am on the add an LPA page
    When I request to add an LPA with valid details using <passcode> which matches <stored_code>
    Then The correct LPA is found and I can confirm to add it
    And The LPA is successfully added

    Examples:
      | passcode               | stored_code  |
      | cYuPhWqReChV           | CYUPHWQRECHV |
      | XyUpHwQrEcHV           | XYUPHWQRECHV |
      | CyUp-hWqR-EcHv         | CYUPHWQRECHV |
      | xYuP-HwQr-eChV         | XYUPHWQRECHV |
      | c-cyup-HWQR-echv       | CYUPHWQRECHV |
      | C-XYUP-hwqr-ECHV       | XYUPHWQRECHV |
      | C - CYUP - HWQR - ECHV | CYUPHWQRECHV |
      | c - xyup - hwqr - echv | XYUPHWQRECHV |
      | c-CYUP HWQR ECHV       | CYUPHWQRECHV |
      | C-xyup hwqr echv       | XYUPHWQRECHV |
      | C cyUP HWqr ecHV       | CYUPHWQRECHV |
      | c XYup HWqr EChv       | XYUPHWQRECHV |

  @integration @ui
  Scenario: The user cannot add an LPA to their account as it does not exist
    Given I am on the add an LPA page
    When I request to add an LPA that does not exist
    Then The LPA is not found
    And I request to go back and try again

  @integration @ui
  Scenario: The user can cancel adding their LPA
    Given I am on the add an LPA page
    When I fill in the form and click the cancel button
    Then I am taken back to the dashboard page
    And The LPA has not been added

  @ui
  Scenario Outline: The user cannot add an LPA with an invalid passcode
    Given I am on the add an LPA page
    When I request to add an LPA with an invalid passcode format of "<passcode>"
    Then I am told that my input is invalid because <reason>

    Examples:
      | passcode | reason |
      | T3ST$PA22-C0D3 | Your activation key must only include letters, numbers and dashes |
      | T3STP*22C0!? | Your activation key must only include letters, numbers and dashes |
      | C - T3S* - PA22 - C0D3 | Your activation key must only include letters, numbers and dashes |
      | T3STPA22C0D | Your activation key must be 12 numbers and letters long |
      |  | Enter your activation key |

  @ui
  Scenario Outline: The user cannot add an LPA with an invalid reference number
    Given I am on the add an LPA page
    When I request to add an LPA with an invalid reference number format of "<referenceNo>"
    Then I am told that my input is invalid because <reason>

    Examples:
      | referenceNo | reason |
      | 7000-00000001 | The LPA reference number must only include numbers |
      | 7000-0000 0001 | The LPA reference number must only include numbers |
      | 7000-0000-ABC! | The LPA reference number must only include numbers |
      | 7000-0000-00011 | The LPA reference number must be 12 numbers long |
      | 70000000000 | The LPA reference number must be 12 numbers long |
      |  | Enter an LPA reference number |

  @ui
  Scenario Outline: The user cannot add an LPA with an invalid DOB
    Given I am on the add an LPA page
    When I request to add an LPA with an invalid DOB format of "<day>" "<month>" "<year>"
    Then I am told that my input is invalid because <reason>

    Examples:
      | day | month | year | reason |
      | 32 | 05 | 1975 | Enter a real date of birth |
      | 10 | 13 | 1975 | Enter a real date of birth |
      | XZ | 10 | 1975 | Enter a real date of birth |
      | 10 | 05 | 3000 | Your date of birth must be in the past |

  @ui
  Scenario: The user is shown an error message when attempting to add the same LPA twice
    Given I have added an LPA to my account
    When I attempt to add the same LPA again
    Then The LPA should not be found
