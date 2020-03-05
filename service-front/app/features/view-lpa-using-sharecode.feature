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
    When I confirm the LPA is correct
    Then I can see the full details of the valid LPA

  @integration @ui
  Scenario: View a cancelled LPA
    Given I have been given access to a cancelled LPA via share code
    And I access the viewer service
    And I give a valid LPA share code
    When I confirm the LPA is correct
    Then I can see the full details of a cancelled LPA

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

  @ui
  Scenario Outline: The user enters an expired sharecode and is shown the reason for not able to see the details of an LPA
    Given I have been given access to an LPA via share code
    And I access the viewer service
    When I give a share code that has got expired
    Then I am told that the share code is invalid because <reason>
  Examples:
  | reason |
  | The code that you entered has expired |

  @ui
  Scenario Outline: The user enters a cancelled sharecode and is shown the reason for not able to see the details of an LPA
    Given I have been given access to an LPA via share code
    And I access the viewer service
    When I give a share code that's been cancelled
    Then I am told that the share code is invalid because <reason>
    Examples:
      | reason |
      | The code you entered has been cancelled |

  @ui
  Scenario Outline: The user enters a non existing surname and share code and is shown the reason for not able to see the details of an LPA
    Given I have been given access to an LPA via share code
    And I access the viewer service
    When I give an invalid <sharecode> and <surname>
    Then I am told that the share code is invalid because <reason>
    Examples:
      |surname  |sharecode       | reason |
      | Billson |1110-1111-0111  | We could not find an LPA matching those details |

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


