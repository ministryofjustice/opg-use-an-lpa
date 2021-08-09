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

  @ui
  Scenario: The user is given an option to generate a new key even if an activation key already exists
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

  @ui @integration
  Scenario: The user is unable to request key for an LPA that they have already added
    Given I am on the add an older LPA page
    And I have added an LPA to my account
    When I provide the details from a valid paper LPA which I have already added to my account
    And I confirm that those details are correct
    Then I should be told that I have already added this LPA

  # Older Older LPA Journey

  @ui @integration
  Scenario: The user is asked for their role on the LPA if the data does not match
    Given I am on the add an older LPA page
    When I provide details that do not match a valid paper document
    And I confirm that those details are correct
    Then I am asked for my role on the LPA

  @ui
  Scenario: The user is asked for the donor's details if they are the attorney on the LPA
    Given My LPA has been found but my details did not match
    And I am asked for my role on the LPA
    When I confirm that I am the attorney
    Then I am asked to provide the donor's details to verify that I am the attorney

  @ui
  Scenario: The attorney is asked for their contact details after providing donor details
    Given I am on the donor details page
    When I provide the donor's details
    Then I am asked for my contact details

  @ui
  Scenario: The user is asked for their contact details if they are the donor on the LPA
    Given My LPA has been found but my details did not match
    And I am asked for my role on the LPA
    When I confirm that I am the donor on the LPA
    Then I am asked for my contact details

  #TODO : Change test to use actual previous page rather than just the dashboard
  @ui
  Scenario: The user can access the contact-details page
    Given I have navigated to the contact-details page
    When I enter my contact details
    Then I do not see any errors

  @ui
  Scenario: The user must enter a telephone number or click the no phone box
    Given I have navigated to the contact-details page
    When I enter nothing
    Then I am told that I must enter a phone number
