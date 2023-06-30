@viewer @viewlpaWithInstructionsAndPreferences
Feature: View an LPA via sharecode
  As an organisation who has been given a share code
  I can enter that code and see the details of an LPA
  So that I can carry out business functions

  Background:
    Given I have been given access to an LPA via share code
    And I access the viewer service

  @ui
  Scenario: The viewer can see instructions and preferences
    Given The LPA has instructions and preferences
    When I enter an organisation name and confirm the LPA is correct
    Then I can see the full details of the valid LPA
    And I can clearly see the lpa has instructions and preferences

  @ui
  Scenario: The viewer can see instructions
    Given The LPA has instructions
    When I enter an organisation name and confirm the LPA is correct
    Then I can see the full details of the valid LPA
    And I can clearly see the lpa has instructions

  @ui
  Scenario: The viewer can see preferences
    Given The LPA has preferences
    When I enter an organisation name and confirm the LPA is correct
    Then I can see the full details of the valid LPA
    And I can clearly see the lpa has preferences

  @ui @wip
  Scenario: Viewers looking at LPAs with no instructions or preferences clearly see that they don't have them
    Given the LPA does not have instructions or preferences
    When I enter an organisation name and confirm the LPA is correct
    Then I can see the full details of the valid LPA
    And preferences will show “no”
    And instructions will show “no”

  @ui @ff:instructions_and_preferences:false
  Scenario: Instructions and preferences information shows for older LPAs
    Given The LPA has instructions and preferences and is signed before 2016
    When I enter an organisation name and confirm the LPA is correct
    Then I can see the full details of the valid LPA
    And I can clearly see the lpa has instructions andor preferences

  @ui @ff:instructions_and_preferences:false
  Scenario: Instructions and preferences summary information shows for older LPAs
    Given The LPA has instructions and preferences and is signed before 2016
    When I enter an organisation name and confirm the LPA is correct
    Then I can see the full details of the valid LPA
    And I can see the lpa has instructions and preferences set in summary

  @ui @ff:instructions_and_preferences:false
  Scenario: Older LPAs with no instructions and preferences do not show anything in the summary
    Given The LPA has no instructions or preferences and is signed before 2016
    When I enter an organisation name and confirm the LPA is correct
    Then I can see the full details of the valid LPA
    And I can see the lpa has no instructions and preferences set in summary

  @ui
  Scenario: The viewer can see waiting message and image for instructions and preferences images that aren't ready yet
    Given The LPA has instructions and preferences for which images aren't yet ready
    When I enter an organisation name and confirm the LPA is correct
    Then I can see the full details of the valid LPA
    And I am told to wait for instructions and preferences images

  @ui
  Scenario: The viewer can see waiting message and image for instructions and preferences images where collection not yet started
    Given The LPA has instructions and preferences for which image collection is not yet started
    When I enter an organisation name and confirm the LPA is correct
    Then I can see the full details of the valid LPA
    And I am told to wait for instructions and preferences images

  @ui
  Scenario: The viewer can see message for instructions and preferences images that failed to load
    Given The LPA has instructions and preferences for which images will fail to load
    When I enter an organisation name and confirm the LPA is correct
    Then I can see the full details of the valid LPA
    And I am told that we cannot currently get the instructions and preferences images
