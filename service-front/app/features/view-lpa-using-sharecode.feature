@viewer @viewlpa
Feature: View an LPA via sharecode
  As an organisation who has been given a share code
  I can enter that code and see the details of an LPA
  So that I can carry out business functions

  @integration @ui
  Scenario: View an LPA
    Given I have been given access to an LPA via share code
    And I access the viewer service
    And I give a valid LPA share code
    When I enter an organisation name and confirm the LPA is correct
    Then I can see the full details of the valid LPA

  @integration @ui
  Scenario: View a cancelled LPA
    Given I have been given access to a cancelled LPA via share code
    And I access the viewer service
    And I give a valid LPA share code on a cancelled LPA
    When I confirm the cancelled LPA is correct
    Then I can see the full details of the cancelled LPA

  @ui @integration
  Scenario: The user can see an option to re enter code if the displayed LPA is incorrect
    Given I have been given access to a cancelled LPA via share code
    And I access the viewer service
    And I give a valid LPA share code
    When I realise the LPA is incorrect
    Then I want to see an option to re-enter code

  @ui @integration
  Scenario: The user should have an option to go back to check another LPA from summary page
    Given I have been given access to an LPA via share code
    And I access the viewer service
    And I give a valid LPA share code
    When I enter an organisation name and confirm the LPA is correct
    Then I can see the full details of the valid LPA
    And I want to see an option to check another LPA

  @ui
  Scenario Outline: The user is able to enter a valid LPA code
    Given I have been given access to an LPA via share code
    And I access the viewer service
    When I give a valid LPA share code of <accessCode> which matches <storedCode>
    And I enter an organisation name and confirm the LPA is correct
    Then I can see the full details of the valid LPA
    Examples:
    |accessCode             | storedCode   |
    |v-VWXW-adbc-3476       | VWXWADBC3476 |
    |V-awxw-ADBC-3476       | AWXWADBC3476 |
    |v-VWXW adBc 3476       | VWXWADBC3476 |
    |V-aWxw ADBC 3476       | AWXWADBC3476 |
    |v VWxW adbc 3476       | VWXWADBC3476 |
    |V awXw ADBC 3476       | AWXWADBC3476 |
    |v-VWxWaDbC3476         | VWXWADBC3476 |
    |v-aWxWadBC3476         | AWXWADBC3476 |
    |v - vwxw - adbc - 3476 | VWXWADBC3476 |
    |V - AWXW - ADBC - 3476 | AWXWADBC3476 |
    |vwxwadbc3476           | VWXWADBC3476 |
    |AWXWADBC3476           | AWXWADBC3476 |
    |vWXW-ADbc-3476         | VWXWADBC3476 |
    |AWXw-aDbC-3476         | AWXWADBC3476 |
    |VWXW - ADBC - 3476     | VWXWADBC3476 |
    |AWXW - ADBC - 3476     | AWXWADBC3476 |

  @ui
  Scenario Outline: The user is shown the correct error messages when entering invalid details
    Given I am on the enter code page
    When I request to view an LPA with an invalid access code of "<accessCode>"
    Then I am told that my input is invalid because <reason>

    Examples:
      | accessCode | reason |
      | V-T3ST_ACE2-C0D3 | Enter LPA access code in the correct format |
      | T3STP*22C0!? | Enter LPA access code in the correct format |
      | T3ST _ PA22 - C0D3 | Enter LPA access code in the correct format |
      | V - T3ST _ PA22 - C0D3 | Enter LPA access code in the correct format |
      | T3STPA22C0D | Enter LPA access code in the correct format |
      |  | Enter your LPA access code |

  @ui
  Scenario Outline: The user is shown the correct error messages when entering invalid details
    Given I am on the enter code page
    When I request to view an LPA with an invalid donor's surname of "<surname>"
    Then I am told that my input is invalid because <reason>

    Examples:
      | surname | reason |
      |  | Enter the donor's last name |

  @ui
  Scenario: The user enters an expired sharecode and is shown the reason for not able to see the details of an LPA
    Given I have been given access to an LPA via share code
    And I access the viewer service
    When I give a share code that has expired
    Then I see a message that the share code has expired

  @ui
  Scenario: The user enters a cancelled sharecode and is shown the reason for not able to see the details of an LPA
    Given I have been given access to an LPA via share code
    And I access the viewer service
    When I give a share code that's been cancelled
    Then I see a message that the share code has been cancelled

  @ui
  Scenario Outline: The user enters a non existing surname and share code and is shown the reason for not able to see the details of an LPA
    Given I have been given access to an LPA via share code
    And I access the viewer service
    When I give an invalid <sharecode> and <surname>
    Then I am told that the share code is invalid because <reason>
    Examples:
      |surname  | sharecode       | reason |
      | Billson | V-1110-1111-0111  | We could not find an LPA matching those details |
      | Billson | V - 1110 - 1111 - 0111  | We could not find an LPA matching those details |
      | Billson | 1110-1111-0111  | We could not find an LPA matching those details |

  @ui
  Scenario: The user is allowed to re-enter code after an invalid one entered
    Given I attempted an invalid share codes
    When I want to make an attempt to enter another share code
    Then I want to see page to enter another share code

  @ui
  Scenario: The user is allowed to re-enter code after viewing a valid one
    Given I am shown the LPA summary found with valid credentials
    When I request to go back and try again
    Then I want to see page to enter another share code


  @ui
  Scenario: The user gets a session timeout if the cookie is not present.
    Given I have been given access to an LPA via share code
    And I access the viewer service
    And I waited too long to enter the share code
    And I give a valid LPA share code
    Then I have an error message informing me to try again.
