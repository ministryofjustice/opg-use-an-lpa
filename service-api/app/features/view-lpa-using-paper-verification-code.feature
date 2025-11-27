@viewer @viewlpa @paperverificationcode @ff:support_datastore_lpas:true
Feature: View an LPA using a paper verification code
  As an organisation who has been given a paper verification code
  I can enter that code and see the details of an LPA
  So that I can carry out business functions

  @acceptance
  Scenario: View an LPA with a paper verification code
    Given I have access to an LPA via "a" paper verification code
    And I access the viewer service
    When I provide donor surname and paper verification code
    Then I am told that the LPA has been found

    Given I provide the correct code holders date of birth, number of attorneys and attorney name
    When I ask to verify my information
    Then I will be asked to enter an organisation name

  @acceptance
  Scenario: View an LPA with a cancelled paper verification code
    Given I have access to an LPA via "a cancelled" paper verification code
    And I access the viewer service
    When I provide donor surname and paper verification code
    Then I am told that the paper verification code has been cancelled

  @acceptance
  Scenario: View an LPA with an expired paper verification code
    Given I have access to an LPA via "an expired" paper verification code
    And I access the viewer service
    When I provide donor surname and paper verification code
    Then I am told that the paper verification code has expired

  @acceptance
  Scenario Outline: Cannot view an LPA when providing incorrect information
    Given I have access to an LPA via "a" paper verification code
    And I access the viewer service
    When I provide donor surname and paper verification code
    Then I am told that the LPA has been found

    Given I provide <sentToDonor>, <attorneyName>, <dateOfBirth> and <noOfAttorneys> as my information
    When I ask to verify my information
    Then I am told that I cannot view the LPA summary

    Examples:
      | sentToDonor | attorneyName | dateOfBirth | noOfAttorneys | description |
      | true  | "Herman Seakrest" | "1982-07-24" | 2 | Not the donor date of birth     |
      | false | "John Seakrest"   | "1982-07-24" | 2 | Not an attorneys name           |
      | true  | "John Seakrest"   | "1982-07-24" | 2 | Not an attorneys name           |
      | false | "Herman Seakrest" | "1970-12-08" | 2 | Not the attorneys date of birth |
      | true  | "Herman Seakrest" | "1970-12-08" | 2 | Not the donors date of birth    |
      | false | "Herman Seakrest" | "1982-07-24" | 1 | Incorrect attorney count        |
