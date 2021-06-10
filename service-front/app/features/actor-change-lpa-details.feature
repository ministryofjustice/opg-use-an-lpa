@actor @changeLpaDetails
Feature: View the change LPA details page
  As a user
  I need to know what to do when I see a mistake on my LPA summary
  So that any mistakes can be corrected

  Background:
    Given I am a user of the lpa application
    And I am currently signed in
    And I have added an LPA to my account

  @ui
  Scenario: The user can access the incorrect LPA details page from the full lpa summary
    Given I am on the full lpa page
    When I select that I have seen something incorrect in the LPA details
    Then I am taken to the change LPA details page
