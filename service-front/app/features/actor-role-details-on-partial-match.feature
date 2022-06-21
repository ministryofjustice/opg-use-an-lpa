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

  @ui @ff:allow_older_lpas:true
  Scenario Outline: The user is shown an error message when entering invalid donor details
    Given I am asked for my role on the LPA
    And I confirm that I am the Attorney
    When I provide invalid donor details of <firstnames> <surname> <dob>
    Then I am told that my input is invalid because <reason>

    Examples:
      | firstnames | surname | dob        | reason                            |
      | Donor      | Person  |            | Enter the donor's date of birth   |
      |            | Person  | 01-01-1980 | Enter the donor's first names     |
      | Donor      |         | 01-01-1980 | Enter the donor's last name       |
      | Donor      | Person  | 41-01-1980 | Date of birth must be a real date |

  @ui @ff:allow_older_lpas:true
  Scenario Outline: The user is shown an error message when entering invalid attorney details
    Given I am asked for my role on the LPA
    And I confirm that I am the Donor
    When I provide invalid attorney details of <firstnames> <surname> <dob>
    Then I am told that my input is invalid because <reason>

    Examples:
      | firstnames    | surname | dob        | reason                               |
      | Attorney      | Person  |            | Enter the attorney's date of birth   |
      |               | Person  | 01-01-1980 | Enter the attorney's first names     |
      | Attorney      |         | 01-01-1980 | Enter the attorney's last name       |
      | Attorney      | Person  | 41-01-1980 | Date of birth must be a real date    |

