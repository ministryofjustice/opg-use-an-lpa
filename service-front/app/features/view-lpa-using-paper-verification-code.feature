@viewer @viewlpa
Feature: View an LPA via Paper Verification Code
  As an organisation who has been given a paper verification code
  I can enter that code and see the details of an LPA
  So that I can carry out business functions

  @integration @ui @ff:paper_verification:true
  Scenario: View an LPA
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

