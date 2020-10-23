@viewer @viewanlpa
Feature: View an LPA via share code
  As an organisation who has been given a share code
  I can enter that code and see the details of an LPA
  So that I can carry out business functions

  @smoke
  Scenario: Service is only accessible over secure https
    Given I access the viewer service insecurely
    Then the viewer service homepage should be shown securely

  @integration @acceptance @smoke
  Scenario: View an LPA
    Given I have been given access to an LPA via share code
    And I access the viewer service
    And I give a valid LPA share code
    When I enter an organisation name and confirm the LPA is correct
    Then I can see the full details of the valid LPA

  @integration @acceptance
  Scenario: View a cancelled LPA
    Given I have been given access to a cancelled LPA via share code
    And I access the viewer service
    And I give a share code that's been cancelled
    Then I can see a message the LPA has been cancelled

  @integration @acceptance
  Scenario: The user can see an option to re enter code if the displayed LPA is incorrect
    Given I have been given access to a cancelled LPA via share code
    And I access the viewer service
    And I give a valid LPA share code
    When I realise the LPA is incorrect
    Then I want to see an option to re-enter code

  @integration @acceptance
  Scenario: The user should have an option to go back to check another LPA from summary page
    Given I have been given access to an LPA via share code
    And I access the viewer service
    And I give a valid LPA share code
    When I enter an organisation name and confirm the LPA is correct
    Then I can see the full details of the valid LPA
    And I want to see an option to check another LPA

  @acceptance
  Scenario Outline: The user is shown the correct error messages when entering invalid details
    Given I am on the enter code page
    When I request to view an LPA with an invalid access code of "<accessCode>"
    Then I am told that my input is invalid because <reason>

    Examples:
      | accessCode | reason |
      | T3ST ACE2-C0D3 | Enter LPA access code in the correct format |
      | T3STP*22C0!? | Enter LPA access code in the correct format |
      | T3ST - PA22 - C0D3 | Enter LPA access code in the correct format |
      | T3STPA22C0D | Enter LPA access code in the correct format |
      |  | Enter your LPA access code |

  @acceptance
  Scenario: The user is shown the correct error messages when entering invalid details
    Given I am on the enter code page
    When I request to view an LPA with an invalid donor's surname of ""
    Then I am told that my input is invalid because "Enter the donor's last name"

  @acceptance
  Scenario: The user enters an expired share code and is shown the reason for not able to see the details of an LPA
    Given I have been given access to an LPA via share code
    And I access the viewer service
    When I give a share code that has expired
    Then I am told that the share code is invalid because "The access code you entered has expired"

  @acceptance
  Scenario: The user enters a cancelled share code and is shown the reason for not able to see the details of an LPA
    Given I have been given access to an LPA via share code
    And I access the viewer service
    When I give a share code that's been cancelled
    Then I am told that the share code is invalid because "The access code you entered has been cancelled"

  @acceptance
  Scenario: The user enters a non existing surname and share code and is shown the reason for not able to see the details of an LPA
    Given I have been given access to an LPA via share code
    And I access the viewer service
    When I give an invalid "1110-1111-0111" and "Billson"
    Then I am told that the share code is invalid because "We could not find an LPA matching those details"

  @acceptance
  Scenario: The user is allowed to re-enter code after an invalid one entered
    Given I attempted an invalid share codes
    When I want to make an attempt to enter another share code
    Then I want to see page to enter another share code

  @acceptance
  Scenario: The user is allowed to re-enter code after viewing a valid one
    Given I am shown the LPA summary found with valid credentials
    When I request to go back and try again
    Then I want to see page to enter another share code
