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
    When I provide the details from a valid paper LPA document
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
    When I provide details "<firstnames>" "<lastname>" "<postcode>" "<dob>" that do not match the paper document
    And I confirm that those details are correct
    Then I am informed that an LPA could not be found with these details

    Examples:
      | firstnames  | lastname  | postcode |    dob      |
      | Ian Deputy  | Deputy    |  string  | 03/12/1975  |
      | Ian Deputy  | Deputy    |  WR0NG1  | 10/10/1980  |
      | Wrong name  | Deputy    |  string  | 10/10/1980  |
      | Ian Deputy  | Incorrect |  string  | 10/10/1980  |


  @integration @acceptance @pact
  Scenario: The user cannot add an older LPA to their account as their LPA is registered before Sept 2019
    Given I am on the add an older LPA page
    When I provide details from an LPA registered before Sept 2019
    And I confirm that those details are correct
    Then I am told that I cannot request an activation key

  @integration @acceptance @pact
  Scenario: The user is informed if they have an activation key
    Given I am on the add an older LPA page
    When I provide the details from a valid paper document that already has an activation key
    And I confirm that those details are correct
    Then I am told that I have an activation key for this LPA and where to find it

  @acceptance
  Scenario: The user cannot add an older LPA to their account due to missing data in request
    Given I am on the add an older LPA page
    When I provide the details from a valid paper LPA document
    And A malformed request is sent which is missing a data attribute
    Then I am told that something went wrong

  @acceptance @integration
  Scenario: The user is able to generate a new key even if an activation key already exists
    Given I am on the add an older LPA page
    And I lost the letter received having the activation key
    When I request for a new activation key again
    Then A letter is requested "again" containing a one time use code
    Then I am told a new activation key is posted to the provided postcode