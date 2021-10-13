@actor @addAnOlderLpa
Feature: Add an older LPA
  As a user
  I can add a paper LPA registered before after 31st August 2020 to my account

  Background:
    Given I have been given access to use an LPA via a paper document
    And I am a user of the lpa application
    And I am currently signed in

  @integration @acceptance @pact @ff:allow_older_lpas:false
  Scenario: The user cannot add an old LPA which does not have a registered status
    Given I am on the add an older LPA page
    When I provide details of an LPA that is not registered
    And I confirm the details I provided are correct
    Then I am informed that an LPA could not be found with these details

  @integration @acceptance @pact @ff:save_older_lpa_requests:false
  Scenario: The user can add an older LPA to their account
    Given I am on the add an older LPA page
    When I provide the details from a valid paper LPA document
    And I confirm the details I provided are correct
    Then I am shown the details of an LPA
    And I confirm details shown to me of the found LPA are correct
    And a letter is requested containing a one time use code
    And A record of my activation key request is not saved

  @integration @acceptance @pact @ff:save_older_lpa_requests:true
  Scenario: The user can add an older LPA to their account and we store the record in our DB
    Given I am on the add an older LPA page
    When I provide the details from a valid paper LPA document
    And I confirm the details I provided are correct
    Then I am shown the details of an LPA
    And I confirm details shown to me of the found LPA are correct
    And a letter is requested containing a one time use code
    And A record of my activation key request is saved

  @integration @acceptance @pact
  Scenario: The user cannot add an older LPA to their account that does not exist
    Given I am on the add an older LPA page
    When I provide details of an LPA that does not exist
    And I confirm the details I provided are correct
    Then I am informed that an LPA could not be found with these details

  @integration @acceptance @pact @ff:allow_older_lpas:false
  Scenario Outline: The user cannot add an older LPA to their account as the data does not match
    Given I am on the add an older LPA page
    When I provide details "<firstnames>" "<lastname>" "<postcode>" "<dob>" that do not match the paper document
    And I confirm the details I provided are correct
    Then I am informed that an LPA could not be found with these details

    Examples:
      | firstnames  | lastname  | postcode |    dob      |
      | Ian Deputy  | Deputy    |  string  | 03/12/1975  |
      | Ian Deputy  | Deputy    |  WR0NG1  | 10/10/1980  |
      | Wrong name  | Deputy    |  string  | 10/10/1980  |
      | Ian Deputy  | Incorrect |  string  | 10/10/1980  |


  @integration @acceptance @pact @ff:allow_older_lpas:false
  Scenario: The user cannot add an older LPA to their account as their LPA is registered before Sept 2019
    Given I am on the add an older LPA page
    When I provide details from an LPA registered before Sept 2019
    And I confirm the details I provided are correct
    Then I am told that I cannot request an activation key

  @integration @acceptance @pact
  Scenario: The user is informed if they have an activation key
    Given I am on the add an older LPA page
    When I provide the details from a valid paper document that already has an activation key
    And I confirm the details I provided are correct
    Then I am told that I have an activation key for this LPA and where to find it

  @acceptance @integration @pact @ff:save_older_lpa_requests:false
  Scenario: The user is able to generate a new key even if an activation key already exists and the record is not saved
    Given I am on the add an older LPA page
    And I lost the letter containing my activation key
    When I request for a new activation key again
    Then a letter is requested containing a one time use code
    And A record of my activation key request is not saved

  @acceptance @integration @pact @ff:save_older_lpa_requests:true
  Scenario: The user is able to generate a new key even if an activation key already exists and the record is saved
    Given I am on the add an older LPA page
    And I lost the letter containing my activation key
    When I request for a new activation key again
    Then a letter is requested containing a one time use code
    And A record of my activation key request is saved

  @acceptance @integration @pact @ff:save_older_lpa_requests:false
  Scenario: The user is unable to request a key for an LPA that they have already added (save requests disabled)
    Given I am on the add an older LPA page
    And I have added an LPA to my account
    When I provide the details from a valid paper LPA which I have already added to my account
    And I confirm the details I provided are correct
    Then I should be told that I have already added this LPA

  @acceptance @integration @pact @ff:save_older_lpa_requests:true
  Scenario: The user is unable to request a key for an LPA that they have already added (save requests enabled)
    Given I am on the add an older LPA page
    And I have added an LPA to my account
    When I provide the details from a valid paper LPA which I have already added to my account
    And I confirm the details I provided are correct
    Then I should be told that I have already added this LPA

  @acceptance @integration @pact @ff:save_older_lpa_requests:true
  Scenario: The user is able to request a new key for an LPA that they have already requested a key for
    Given I am on the add an older LPA page
    When I provide the details from a valid paper LPA which I have already requested an activation key for
    And I confirm the details I provided are correct
    Then I am told that I have an activation key for this LPA and where to find it

  @acceptance
  Scenario: The user cannot add an older LPA to their account due to missing data in request
    Given I am on the add an older LPA page
    When I provide the details from a valid paper LPA document
    And A malformed request is sent which is missing a data attribute
    Then I am told that something went wrong

  @acceptance @integration @pact
  Scenario: The user is not shown the attorney details being a donor on the lpa
    Given I am on the add an older LPA page
    When I provide the details from a valid paper LPA document
    And I confirm the details I provided are correct
    Then I being the donor on the LPA I am not shown the attorney details

  @acceptance @integration @pact
  Scenario: The user is not shown the donor details being an attorney on the lpa
    Given I am on the add an older LPA page
    When I provide the attorney details from a valid paper LPA document
    And I confirm the details I provided are correct
    Then I being the attorney on the LPA I am shown the donor details
