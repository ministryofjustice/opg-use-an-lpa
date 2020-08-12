@actor @deathnotification

  Feature: Death Notification
    As a user
    I want to be able to inform OPG if the donor, attorney or replacement attorney dies
    So that my account is accurate

    Background:
      Given I am a user of the lpa application
      And I am currently signed in

    @ui
    Scenario Outline: A user can view the death notification page
    Given I am on the change details page
    When I click the <hyperlink> link on the change details page
    Then I expect to be on the death notification page
      Examples:
        | hyperlink |
      |the donor or an attorney dies|
      |the donor dies|
      |the attorney dies|
