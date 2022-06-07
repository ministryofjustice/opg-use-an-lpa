@actor @checkGivenAddressOnPaper
Feature: Say if the given address is on paper LPA
  As a business user
  I would like to know if the user has provided their address as in paper LPA
  So that the cleansing team find it easier to check the validity of their request

  Background:
    Given I have been given access to use an LPA via a paper document
    And I am a user of the lpa application
    And I am currently signed in

  @ui @ff:allow_older_lpas:true
  Scenario: An attorney user is asked for their address if they have a partial match
    Given My LPA has been found but my details did not match
    Then I will be asked for my full address

  @ui @ff:allow_older_lpas:true
  Scenario: An attorney user is asked for their address if they have a partial match
    Given My LPA has been found but my details did not match
    When  I have provided my current address
    Then I am asked if it's the same as in paper LPA

  @ui @ff:allow_older_lpas:true
  Scenario: An attorney user is asked for their address if they have a partial match
    Given My LPA has been found but my details did not match
    And  I have provided my current address
    And I am asked if it's the same as in paper LPA
    When I select this is the address same as on paper LPA
    Then I am asked for my role on the LPA

  @ui @ff:allow_older_lpas:true
  Scenario: An attorney user is asked for their address if they have a partial match
    Given My LPA has been found but my details did not match
    And  I have provided my current address
    And I am asked if it's the same as in paper LPA
    When I select this is not the address same as on paper LPA
    Then I am asked for my role on the LPA

  @ui @ff:allow_older_lpas:true
  Scenario: An attorney user is asked for their address if they have a partial match
    Given My LPA has been found but my details did not match
    And  I have provided my current address
    And I am asked if it's the same as in paper LPA
    When I select not sure the address same as on paper LPA
    Then I am asked for my role on the LPA

