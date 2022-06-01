@actor @roleDetails
Feature: Provide role details when adding an older LPA
  As a key team member
  I want to know additional information such as role details for an activation key request
  so that security checks pass and I can update an address in sirius

  Background:
    Given I am a user of the lpa application
    And I am currently signed in
    And I have been given access to use an LPA via a paper document
    And My LPA has been found but my details did not match
    And I have provided my current address
    And I am asked for my role on the LPA

  @ui @ff:allow_older_lpas:true
  Scenario: The user is asked for the donor's details if they are the attorney on the LPA
    Given I am asked for my role on the LPA
    When I confirm that I am the Attorney
    Then I am asked to provide the donor's details to verify that I am the attorney

  @ui @ff:allow_older_lpas:true
  Scenario: The user is asked for their attorney details if they are the donor on the LPA
    Given I am asked for my role on the LPA
    When I confirm that I am the Donor
    Then I am asked for the attorney details

  @ui @ff:allow_older_lpas:true
  Scenario: The user is asked contact details after providing donor's details
    Given I am asked for my role on the LPA
    When I confirm that I am the Attorney
    And I provide the donor's details
    Then I am asked for my contact details

  @ui @ff:allow_older_lpas:true
  Scenario: The user is asked contact details after providing attorney's details
    Given I am asked for my role on the LPA
    When I confirm that I am the Donor
    And I provide the attorney details
    Then I am asked for my contact details
