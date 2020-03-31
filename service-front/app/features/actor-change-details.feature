@actor @changeDetailsPage
Feature: View the change details page
  As a user
  I want to know what to do if I need to change the donor or attorney's details
  So that I can contact the correct person to update my lpa

  Background:
    Given I am a user of the lpa application
    And I am currently signed in
    And I have added an LPA to my account

  @ui
  Scenario Outline: The user can access the change details page from the full lpa summary
    Given I am on the full lpa page
    When I click the <link> to change a donor or attorneys details
    Then I am taken to the change details page

    Examples:
    | link                                  |
    | Need to change the donor's details?   |
    | Need to change an attorney's details? |
