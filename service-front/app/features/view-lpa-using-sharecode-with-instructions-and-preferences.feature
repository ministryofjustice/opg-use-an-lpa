@viewer @viewlpaWithInstructionsAndPreferences
Feature: View an LPA via sharecode
  As an organisation who has been given a share code
  I can enter that code and see the details of an LPA
  So that I can carry out business functions

  Background:
    Given I have been given access to an LPA via share code
    And I access the viewer service

  @ui
  Scenario:test
    Given The LPA has instructions and preferences
    When I enter an organisation name and confirm the LPA is correct
    Then I can see the full details of the valid LPA
    And I can clearly see the lpa has instructions and preferences

  @ui
  Scenario:test
    Given The LPA has instructions
    When I enter an organisation name and confirm the LPA is correct
    Then I can see the full details of the valid LPA
    And I can clearly see the lpa has instructions

  @ui
  Scenario:test
    Given The LPA has preferences
    When I enter an organisation name and confirm the LPA is correct
    Then I can see the full details of the valid LPA
    And I can clearly see the lpa has preferences

  @ui
  Scenario:test
    Given The LPA has instructions and preferences and is signed before 2016
    When I enter an organisation name and confirm the LPA is correct
    Then I can see the full details of the valid LPA
    And I can clearly see the lpa has instructions andor preferences


