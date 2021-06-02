@actor @addAnOlderLpa
Feature: Add an older LPA
  As a user
  I expect to be able to add an LPA registered after 31st August 2019 to my account
  So that I can manage access to the LPA digitally

  Background:
    Given I have been given access to use an LPA via a paper document
    And I am a user of the lpa application
    And I am currently signed in

  @ui @integration
  Scenario: The user can add an older LPA to their account
    Given I am on the add an older LPA page
    When I provide the details from a valid paper document
    And I confirm that those details are correct
    Then a letter is requested containing a one time use code
    And I receive an email confirming activation key request

  @ui @integration
  Scenario: The user cannot add an old LPA to their account as the data does not match
    Given I am on the add an older LPA page
    When I provide details that do not match a valid paper document
    And I confirm that those details are correct
    Then I am informed that an LPA could not be found with these details

  @ui @integration
  Scenario: The user cannot add an older LPA to their account as their LPA is registered before Sept 2019
    Given I am on the add an older LPA page
    When I provide details from an LPA registered before Sept 2019
    And I confirm that those details are correct
    Then I am told that I cannot request an activation key

  @ui @integration
  Scenario: The user is informed when trying to add an older LPA to their account and it has an activation key
    Given I am on the add an older LPA page
    And I already have a valid activation key for my LPA
    When I provide the details from a valid paper document
    And I confirm that those details are correct
    Then I am told that I have an activation key for this LPA and where to find it

  @ui @integration
  Scenario: The user is given an option to  generate a new key even if an activation key already exists
    Given I am on the add an older LPA page
    And I already have a valid activation key for my LPA
    And I lost the letter received having the activation key
    Then I should have an option to regenerate an activation key for the old LPA I want to add

  @ui @integration
  Scenario: The user is able to generate a new key even if an activation key already exists
    Given I am on the add an older LPA page
    And I already have a valid activation key for my LPA
    And I lost the letter received having the activation key
    When I request for a new activation key again
    Then I am told a new activation key is posted to the provided postcode






