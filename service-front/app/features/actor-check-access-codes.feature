@actor @checkAccessCodes
Feature: The user is able to check the access codes they have created
  As a user
  I want to be able to check the access codes I have created for organisations
  So that I can see their details

  Background:
    Given I am a user of the lpa application
    And I am currently signed in
    And I have added an LPA to my account
    And I have created an access code
    And I am on the dashboard page

  @ui @integration
  Scenario: As a user I can see the access codes I have created
    When I click to check my access codes
    Then I can see all of my access codes and their details

  @ui @integration
  Scenario: As a user I can see the expired access codes I have created
    When I click to check my access code now expired
    Then I should be shown the details of the viewer code with status EXPIRED

  @ui @integration
  Scenario: As a user I can see the active and inactive access codes
    When I click to check my active and inactive codes
    Then I can see the relevant Active codes and Inactive codes of my access codes and their details

  @ui
  Scenario: As a user I can see the relevant status when an access code has been cancelled and is later expired
    Given I cancel the viewer code
    When I click to check the viewer code has been cancelled which is now expired
    Then I should be shown the details of the viewer code with status CANCELLED

  @ui @integration
  Scenario: As a user I can see the who has viewed the LPA's I have added using the access code
    Given I have shared the access code with organisations to view my LPA
    When I click to check my access codes that is used to view LPA
    Then I can see the name of the organisation that viewed the LPA

  @ui @integration
  Scenario: As a user I can see the code had not been used to view an LPA
    Given I have shared the access code with organisations to view my LPA
    When I click to check my access codes
    Then I can see the code has not been used to view the LPA

  @ui
  Scenario: As a user I cannot see the access codes if the status of the LPA has changed to Revoked
    Given The LPA has been revoked
    When I check access codes of the status changed LPA
    Then I cannot see my access codes and their details
    And I am taken back to the dashboard page
