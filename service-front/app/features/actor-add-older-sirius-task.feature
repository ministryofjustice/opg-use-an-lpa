@actor @enterAddressOnPaper
Feature: Add an older LPA creates Sirius task
  As a caseworker
  I expect to see a Sirius task against LPAs that have been partially matched or require cleansing
  So that I can more efficiently provide access to the LPA for the user

  Background:
    Given I am a user of the lpa application
      And I am currently signed in
      And I have been given access to use an LPA via a paper document

  @ui
  Scenario: The user can enter the address on their paper lpa successfully
    Given My LPA has been found but my details did not match
      And My LPA was registered 'before' 1st September 2019 and LPA is 'not marked' as clean
      And I have provided my current address
      And I confirm that I am the Attorney
      And I provide the donor's details
      And I provide my telephone number
    When I confirm that the data is correct and click the confirm and submit button
    Then My current address is recorded in the Sirius task

  @ui
  Scenario: The user can specify that their address is different
    Given My LPA has been found but my details did not match
      And My LPA was registered 'before' 1st September 2019 and LPA is 'not marked' as clean
      And I select the address is not the same as on paper LPA
      And I have given the address on the paper LPA
      And I confirm that I am the Attorney
      And I provide the donor's details
      And I provide my telephone number
    When I confirm that the data is correct and click the confirm and submit button
    Then The address given on the paper LPA is recorded in the Sirius task
