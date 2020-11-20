@actor @removeLpa

Feature: The user is able to remove an LPA from their account
  As a user
  I want to be able to remove an LPA from my account
  So that does not appear on my dashboard if its no longer needed or active

  Background:
    Given I am a user of the lpa application
    And I am currently signed in
    And I have added an LPA to my account

  @acceptance @integration
  Scenario: The user can remove an LPA from their account
  Given I am on the dashboard page
  When I request to remove the LPA
  And I confirm removal of the LPA
  Then The LPA is removed
  And The removed LPA will not be displayed on the dashboard
  And I can see a flash message for the removed LPA
