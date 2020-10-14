@actor @checkAccessCodes
Feature: The user is able to check the access codes they have created
  As a user
  I want to be able to check the access codes I have created for organisations
  So that I can see their details

  Background:
    Given I am a user of the lpa application
    And I am currently signed in
    And I have added an LPA to my account

  @integration @acceptance
  Scenario: As a user I can see the access codes I have created
    Given I have created an access code
    When I check my access codes
    Then I can see all of the access codes and their details

  @integration @acceptance
  Scenario: As a user I can see the expired access codes I have created
    Given I am on the dashboard page
    Given I have created an access code
    When I click to check my access code now expired
    Then I should be shown the details of the viewer code with status "EXPIRED"

  @acceptance @integration
  Scenario: As a user I can see all the access codes for the LPA I have added to my account
    Given I have created an access code
    When I click to check the access codes
    Then I can see all of the access codes and their details