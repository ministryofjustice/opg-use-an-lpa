@actor @dashboard
Feature: The user is able to see correct information on their dashboard
  As a user
  I want to be able to see any LPA's I have added on my dashboard
  So that I can see their details and perform actions on them

  Background:
    Given I am a user of the lpa application
    And I am currently signed in
    And I have added an LPA to my account

  @acceptance @integration
  Scenario Outline: As a user I can see the number of active access codes an LPA has
    Given I have 2 codes for one of my LPAs
    When I am on the dashboard page
    Then I can see that my LPA has <noActiveCodes> with expiry dates <code1Expiry> <code2Expiry>

    Examples:
      | noActiveCodes                | code1Expiry                | code2Expiry               |
      | 2 active codes               |  2021-04-01T23:59:59+01:00 | 2021-04-01T23:59:59+01:00 |
      | 1 active code                |  2019-01-05T23:59:59+00:00 | 2022-01-05T23:59:59+00:00 |
      | No organisations have access |  2019-01-05T23:59:59+00:00 | 2019-01-05T23:59:59+00:00 |

  @acceptance @integration
  Scenario: As a user I can see the number of active access codes an LPA has
    Given I have added an LPA to my account
    When I am on the dashboard page
    Then I can see that no organisations have access to my LPA

  @acceptance @integration
  Scenario: As a user I am asked to confirm removal of LPA if I have requested to do so
    Given I am on the dashboard page
    When I request to remove the added LPA
    Then I am asked to confirm whether I am sure if I want to delete lpa

  @acceptance @integration
  Scenario: As a user I can go back to the dashboard page if I change my mind about deleting the LPA
    Given I am on the confirm lpa deletion page
    When I request to return to the dashboard page
    Then I am taken back to the dashboard page

  @acceptance @integration
  Scenario: As a user I no more see the removed LPA details on the dashboard
    Given I am on the dashboard page
    When I request to remove the added LPA
    And I confirm removal of the LPA
    Then The deleted LPA will not be displayed on the dashboard
    And I can see a flash message for the removed LPA