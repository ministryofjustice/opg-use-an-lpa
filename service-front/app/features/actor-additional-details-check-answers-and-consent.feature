@actor @additionalDetailsCheckAnswers
Feature: Additional details check answers and consent
  As a user
  I expect when I have been asked for more information about the LPA I am trying to add
  I can then view the information I have supplied and change it if necessary before submitting

  Background:
    Given I have been given access to use an LPA via a paper document
    And I am a user of the lpa application
    And I am currently signed in
    And My LPA has been found but my details did not match

  @ui @ff:allow_older_lpas:true
  Scenario: The user is shown the correct information on the check and consent page as an attorney without a phone number
    Given I have provided my current address
    And I confirm that I am the Attorney
    And I provide the donor's details
    When I select that I cannot take calls
    Then I am asked to consent and confirm my details
    And I can see my address, attorney role, donor details and that I have not provided a telephone number

  @ui @ff:allow_older_lpas:true
  Scenario: The user can is shown the correct information on the check and consent page as an attorney with a phone number
    And I have provided my current address
    And I confirm that I am the Attorney
    And I provide the donor's details
    When I enter my telephone number
    Then I am asked to consent and confirm my details
    And I can see my address, attorney role, donor details and telephone number

  @ui @ff:allow_older_lpas:true
  Scenario: The user can is shown the correct information on the check and consent page as a donor with a phone number
    Given I have provided my current address
    And I confirm that I am the Donor
    And I provide the attorney details
    When I enter my telephone number
    Then I am asked to consent and confirm my details
    And I can see my address, donor role, attorney details and telephone number

  @ui @ff:allow_older_lpas:true
  Scenario: The user can is shown the correct information on the check and consent page as a donor without a phone number
    And I have provided my current address
    And I confirm that I am the Donor
    And I provide the attorney details
    When I select that I cannot take calls
    Then I am asked to consent and confirm my details
    And I can see my address, donor role, attorney details and that I have not provided a telephone number

  @ui @ff:allow_older_lpas:true
  Scenario: The user is shown the correct information on the check and consent page when they have added an address that is on the paper lpa
    Given I select the address is not the same as on paper LPA
    And I am asked for my address from the paper LPA
    And I input a valid paper LPA address
    And I confirm that I am the Attorney
    And I provide the donor's details
    When I select that I cannot take calls
    Then I am asked to consent and confirm my details
    And I can see my address, attorney role, donor details and that I have not provided a telephone number
    And I can see the paper address I have input
