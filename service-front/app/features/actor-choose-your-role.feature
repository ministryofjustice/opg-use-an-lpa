@actor @chooseYourRole
Feature: Choose your role
  As a user
  I would like to enter the my role on the LPA
  So that the cleansing team find it easier to check the validity of my request

  Background:
    Given I have been given access to use an LPA via a paper document
    And I am a user of the lpa application
    And I am currently signed in
    And My LPA has been found but my details did not match
    And I have provided my current address

  @ui @ff:allow_older_lpas:true
  Scenario: The user is asked for the donor's details if they are the attorney on the LPA
    Given I am asked for my role on the LPA
    When I confirm that I am the Attorney
    Then I am asked to provide the donor's details to verify that I am the attorney

  @ui @ff:allow_older_lpas:true
  Scenario: The user is asked for their contact details if they are the donor on the LPA
    Given I am asked for my role on the LPA
    When I confirm that I am the Donor
    Then I am asked for my contact details

  @ui @ff:allow_older_lpas:true
  Scenario: The user is asked for their contact details if they are the donor on the LPA
    Given I am asked for my role on the LPA
    And I have given the address on the paper LPA
    When I click the Back link on the page
    Then I am asked for the address on the paper LPA

  @ui @ff:allow_older_lpas:true
  Scenario: The user is asked for their contact details if they are the donor on the LPA
    Given I am asked for my role on the LPA
    And I have not given the address on the paper LPA
    When I click the Back link on the page
    Then I will be asked for my full address






