@actor @enterAddressOnPaper
Feature: Enter address on the Paper LPA
  As a user
  I would like the opportunity to enter the address on the LPA
  So that the cleansing team find it easier to check the validity of my request

  Background:
    Given I am a user of the lpa application
    And I am currently signed in
    And I have been given access to use an LPA via a paper document
    And My LPA has been found but my details did not match
    And My LPA was registered 'before' 1st September 2019 and LPA is 'not marked' as clean

  @ui
    Scenario: The user can enter the address on their paper lpa successfully
      Given I am asked for the address on the paper LPA
      When I input a valid paper LPA address
      Then I am asked for my role on the LPA

  @ui
   Scenario: The User is told to enter an address if they have left the field empty
     Given I am asked for the address on the paper LPA
     When I enter nothing
     Then I am shown an error telling me to input the paper address