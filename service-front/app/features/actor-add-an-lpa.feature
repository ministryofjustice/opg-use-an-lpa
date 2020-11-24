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

  @ui
  Scenario: The user cannot add an LPA to their account and is shown the answers provided
    Given I am on the add an LPA page
    When I request to add an LPA whose status is pending using xyuphwqrechv
    Then The LPA is not found
    And I see a page showing me the answers I have entered and content that helps me get it right


  @integration @ui
  Scenario Outline: The user can add an LPA to their account
    Given I am on the add an LPA page
    When I request to add an LPA with valid details using <passcode> which matches <storedCode>
    Then The correct LPA is found and I can confirm to add it
    And The LPA is successfully added
    And I can see a flash message for the added LPA

    Examples:
      | passcode               | storedCode   |
      | C cyUP HWqr ecHV       | CYUPHWQRECHV |
      | c XYup HWqr EChv       | XYUPHWQRECHV |
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
      | T3ST$PA22-C0D3 |  Enter an activation key in the correct format |
      | T3STP*22C0!? |  Enter an activation key in the correct format |
      | C - T3S* - PA22 - C0D3 |  Enter an activation key in the correct format |
      | T3STPA22C0D | Enter an activation key in the correct format |
      |  | Enter your activation key |

  @ui
  Scenario Outline: The user cannot add an LPA with an invalid reference number
    Given I am on the add an LPA page
    When I request to add an LPA with an invalid reference number format of "<referenceNo>"
    Then I am told that my input is invalid because <reason>

    Examples:
      | referenceNo | reason |
      | 7000-00000001 | Enter the LPA reference number in the correct format |
      | 7000-0000 0001 | Enter the LPA reference number in the correct format |
      | 7000-0000-ABC! | Enter the LPA reference number in the correct format |
      | 7000-0000-00011 | The LPA reference number you entered is too long |
      | 70000000000 | The LPA reference number you entered is too short |
      |  | Enter the LPA reference number |

  @ui
  Scenario Outline: The user cannot add an LPA with an invalid DOB
    Given I am on the add an LPA page
    When I request to add an LPA with an invalid DOB format of "<day>" "<month>" "<year>"
    Then I am told that my input is invalid because <reason>

    Examples:
      | day | month | year | reason |
      | 32 | 05 | 1975 | Date of birth must be a real date |
      | 10 | 13 | 1975 | Date of birth must be a real date |
      | XZ | 10 | 1975 | Date of birth must be a real date |
      |  | 10 |    | Date of birth must include a day Date of birth must include a year|
      |  |  | 1975 | Date of birth must include a day Date of birth must include a month |
      | XZ |  |    | Date of birth must include a month Date of birth must include a year |
      | 10 | 05 | 3000 | Date of birth must be in the past |
      |  |  |  | Enter your date of birth |


  @ui
  Scenario: The user is shown an error message when attempting to add the same LPA twice
    Given I have added an LPA to my account
    When I attempt to add the same LPA again
    Then I should be told that I have already added this LPA

  @ui
  Scenario Outline: The user can add an LPA to their account when they have the same DOB as others on the LPA
    Given I am on the add an LPA page
    When I request to add an LPA with the code "<passcode>" that is for "<firstName>" "<secondName>" and I will have an Id of <id>
    Then The correct LPA is found and I can see the correct name which will have a role of "<role>"
    And The LPA is successfully added

    Examples:
      | id  | passcode     | firstName | secondName | role     |
      | 164 | TYUPHWQRECHV | Harold    | Stallman   | Attorney |
      | 64  | AYUPHWQRECHV | Simon     | Matthews   | Attorney |
