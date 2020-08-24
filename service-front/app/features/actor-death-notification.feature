@actor @death-notification

Feature: Death Notification
  As a user
  I want to be able to inform OPG if the donor, attorney or replacement attorney dies
  So that my account is accurate

  Background:
    Given I am a user of the lpa application
    And I am currently signed in
    And I have added an LPA to my account

  @ui
  Scenario: A user can view the death notification page
    Given I am on the change details page
    When I select to find out more if the donor or an attorney dies
    Then I expect to be on the death notification page
