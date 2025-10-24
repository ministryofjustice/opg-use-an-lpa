@viewer @viewlpa
Feature: View an LPA via Paper Verification Code
  As an organisation who has been given a paper verification code
  I can enter that code and see the details of an LPA
  So that I can carry out business functions

  @ui @ff:paper_verification:true
  Scenario: View an LPA is for attorney
    Given I have been given access to an LPA via Paper Verification Code
    And I access the viewer service
    And I give a valid LPA Paper Verification Code
    Then I will be asked to enter more information

    Given I type in a valid LPA reference number
    When I select continue
    Then I will be asked who the paper verification code was sent to

    Given Attorney was chosen as the person who the paper verification code was sent to
    When I select continue
    Then they will see a page asking for attorney dob

    Given paper verification code is for the attorney
    And they have entered date of birth for attorney
    And they have entered number of attorneys
    Then they check their answers

    Given they change LPA Reference on check answers page
    When they click continue they return to check answers page
    Then they check their answers

    Given they change who code sent to on check answers page
    When they click continue they return to check answers page
    Then they check their answers

    Given they change attorney dob on check answers page
    When they click back they return to check answers page
    Then they check their answers

    Given they change number of attorney on check answers page
    When they click back they return to check answers page
    Then they check their answers

  Scenario: View an LPA is for donor
    Given I have been given access to an LPA via Paper Verification Code
    And I access the viewer service
    And I give a valid LPA Paper Verification Code
    Then I will be asked to enter more information

    Given I type in a valid LPA reference number
    When I select continue
    Then I will be asked who the paper verification code was sent to

    Given Donor was chosen as the person who the paper verification code was sent to
    When I select continue
    Then they will see a page asking for donor dob

    Given paper verification code is for the donor
    And they have entered date of birth for donor
    And they have entered attorney details
    Then they check their answers

  @ui @ff:paper_verification:true
  Scenario Outline: User is corrected when entering wrong format lpa reference number
    Given I access the viewer service and provide the required details to view lpa
    And I am on the page to enter Lpa reference number
    When I request to search for an LPA with an invalid format <reference>
    Then I am told that my entry is invalid because <reason>

    Examples:
      | reference               | reason                                               |
      | 7890-0400-4003          |  Enter LPA reference number in the correct format    |
      | M789004004XU3           |  Enter LPA reference number in the correct format    |
      | X-7890-0400-4003        |  Enter LPA reference number in the correct format    |
      | M-7890-0400             | The LPA reference number you entered is too short    |
      | M-78900-04000-4003      | The LPA reference number you entered is too long     |