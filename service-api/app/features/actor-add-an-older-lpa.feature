@actor @addAnOlderLpa
Feature: Add an older LPA
  As a user
  I can add a paper LPA registered before after 31st August 2020 to my account

  Background:
    Given I have been given access to use an LPA via a paper document
    And I am a user of the lpa application
    And I am currently signed in

  @integration @acceptance @pact
  Scenario: The user can add an older LPA to their account
    Given I am on the add an older LPA page
    When I provide the details from a valid paper document
    And I confirm that those details are correct
    Then a letter is requested containing a one time use code

  @integration @acceptance @pact
  Scenario: The user cannot add an older LPA to their account that does not exist
    Given I am on the add an older LPA page
    When I provide details of an LPA that does not exist
    And I confirm that those details are correct
    Then I am informed that an LPA could not be found with these details

  @integration @acceptance @pact
  Scenario Outline: The user cannot add an older LPA to their account as the data does not match
    Given I am on the add an older LPA page
    When I provide details <dob> <postcode> <first_names> <last_name> that do not match the paper document
    And I confirm that those details are correct
    Then I am informed that an LPA could not be found with these details

    Examples:
      |     dob     | postcode | first_names | last_name |
      | 1985-05-23  | AB1CD2   | Some Random | Person    |
      | 1985-10-10  | XY9NY5   | Some Random | Person    |
      | 1985-10-10  | AB1CD2   | Wrong name  | Person    |
      | 1985-10-10  | AB1CD2   | Some Random | Incorrect |

  @integration @acceptance @pact
  Scenario: The user cannot add an older LPA to their account as their LPA is registered before Sept 2019
    Given I am on the add an older LPA page
    When I provide details from an LPA registered before Sept 2019
    And I confirm that those details are correct
    Then I am told that I cannot request an activation key

  @integration @acceptance @pact
  Scenario: The user cannot add an older LPA to their account if they have an activation key
    Given I am on the add an older LPA page
    When I provide the details from a valid paper document which already has an activation key
    And I confirm that those details are correct
    Then I am told that I have an activation key for this LPA and where to find it

  @acceptance
  Scenario Outline: The user cannot add an older LPA to their account due to missing data in request
    Given I am on the add an older LPA page
    When I provide the details from a valid paper document
    And A malformed request is sent which is missing the <reference_no> <dob> <postcode> <first_names> <last_name>
    Then I am told that something went wrong

    Examples:
      |  reference_no  |     dob    | postcode | first_names | last_name |
      |     null       | 1985-05-23 | AB1CD2   | Some Random |   Person  |
      |  700000001234  |     null   | AB1CD2   | Some Random |   Person  |
      |  700000001234  | 1985-05-23 |   null   | Some Random |   Person  |
      |  700000001234  | 1985-05-23 | AB1CD2   |     null    |   Person  |
      |  700000001234  | 1985-05-23 | AB1CD2   | Some Random |    null   |
