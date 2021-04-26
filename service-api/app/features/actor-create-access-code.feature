@actor @createAccessCode
Feature: The user is able to create access codes for organisations
  As a user
  I want to be able to create access codes to enable organisations to view my LPA
  So that I do not have to give them the paper LPA

  Background:
    Given I am a user of the lpa application
    And I am currently signed in
    And I have added an LPA to my account

  @acceptance @integration
  Scenario: As a user I want to be told if I have not created any access codes yet
    Given I have added an LPA to my account
    And I am on the dashboard page
    When I check my access codes
    Then I should be told that I have not created any access codes yet
    And I should be able to click a link to go and create the access codes

  @integration @acceptance
  Scenario: As a user I can generate an access code for an organisation
    Given I am on the dashboard page
    When I request to give an organisation access to one of my LPAs
    Then I am given a unique access code

  @acceptance @integration
  Scenario: As a user I wouldn't be able to create a viewer code if  the status of LPA has changed  to Revoked
    Given I have added an LPA to my account
    And I am on the dashboard page
    And The status of the LPA got Revoked
    When I request to give an organisation access to the LPA whose status changed to Revoked
    Then I am taken back to the dashboard page
