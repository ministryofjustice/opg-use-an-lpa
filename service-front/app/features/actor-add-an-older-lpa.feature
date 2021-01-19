@actor @addAnOlderLpa
Feature: Add an older LPA
  As a user
  I expect to be able to add an LPA registered after 31st August 2019 to my account
  So that I can manage access to the LPA digitally

  Background:
    Given I have been given access to use an LPA via a paper document
    And I am a user of the lpa application
    And I am currently signed in

  @integration
  Scenario: The user can access the appropriate triage page
    Given I am on the dashboard page
    When I choose to add an LPA
    And I do not have an activation key
    Then I am asked for details from a paper LPA document

  @integration
  Scenario: The user can review their answers and can choose to change them
    Given I have chosen to add an LPA without an activation key
    When I provide the details from a valid paper document
    Then I am asked to check my answers are correct
    And I can decide to change my details

  @wip @integration
  Scenario: The user can add an older LPA to their account
    Given I am on the add an older LPA page
    When I provide the details from a valid paper document
    And I confirm that those details are correct
    Then a letter is requested containing a one time use code

  @integration
  Scenario Outline: The user cannot add an old LPA to their account as the data does not match
    Given I am on the add an older LPA page
    When I provide details <reference_number> <dob> <postcode> <first_names> <last_name> that do not match the paper document
    Then I am informed that an LPA could not be found with these details

    Examples:
      | reference_number |   dob        | postcode | first_names | last_name |
      | 700000001234     |  1985-10-10  | AB1CD2   | Some Random | Person    |
      | 700000000054     |  1985-05-23  | AB1CD2   | Some Random | Person    |
      | 700000000054     |  1985-10-10  | XY9NY5   | Some Random | Person    |
      | 700000000054     |  1985-10-10  | AB1CD2   | Wrong name  | Person    |
      | 700000000054     |  1985-10-10  | AB1CD2   | Some Random | Incorrect |

  @integration
  Scenario: The user cannot add an older LPA to their account as their LPA is registered before Sept 2019
    Given I am on the add an older LPA page
    When I provide details from an LPA registered before Sept 2019
    And I confirm that those details are correct
    Then I am told that I cannot request an activation key

  @integration
  Scenario: The user cannot add an older LPA to their account if they have an activation key
    Given I am on the add an older LPA page
    When I provide the details from a valid paper document
    And I confirm that those details are correct
    And I already have a valid activation key for my LPA
    Then I am told that I have an activation key for this LPA and where to find it
