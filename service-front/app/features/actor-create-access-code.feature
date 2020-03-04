@actor @createAccessCode
Feature: The user is able to create access codes for organisations
  As a user
  I want to be able to create access codes to enable organisations to view my LPA
  So that I do not have to give them the paper LPA

  Background:
    Given I am a user of the lpa application
    And I am currently signed in
    And I have added an LPA to my account

  @ui @integration
  Scenario: As a user I want to be told if I have not created any access codes yet
    Given I have added an LPA to my account
    And I am on the dashboard page
    When I check my access codes
    Then I should be told that I have not created any access codes yet
    And I should be able to click a link to go and create the access codes


  @ui
  Scenario Outline: As a user I to be shown the mistakes I make while creating an access code for an organisation
    Given I am on the dashboard page
    When I request to give an organisation access
    And I have not provided required information for creating access code such as <organisationname>
    Then I should be told access code could not be created due to <reasons>
    Examples:
      | organisationname  |  reasons                          |
      |                   |  Enter an organisation name       |

  @ui @integration
  Scenario: As a user I can generate an access code for an organisation
    Given I am on the dashboard page
    When I request to give an organisation access to one of my LPAs
    Then I am given a unique access code

