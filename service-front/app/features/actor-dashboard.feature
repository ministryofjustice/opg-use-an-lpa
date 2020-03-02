@actor @actorDashboard
Feature: The user is able to see correct information on their dashboard
  As a user
  I want to be able to see any LPA's I have added on my dashboard
  So that I can see their details and perform actions on them

  Background:
    Given I am a user of the lpa application
    And I am currently signed in
    And I have added an LPA to my account

  @integration @ui
  Scenario Outline: As a user I can see the number of active access codes an LPA has
    Given I have 2 codes for one of my LPAs
    When I am on the dashboard page
    Then I can see that my LPA has the correct number of active codes <code1Expiry> <code2Expiry>

    Examples:
      | code1Expiry                | code2Expiry               |
      |  2021-04-01T23:59:59+01:00 | 2021-04-01T23:59:59+01:00 |
      |  2019-01-05T23:59:59+00:00 | 2022-01-05T23:59:59+00:00 |
      |  2019-01-05T23:59:59+00:00 | 2019-01-05T23:59:59+00:00 |

  @integration @ui
  Scenario: As a user I can see the number of active access codes an LPA has
    Given I have added an LPA to my account
    When I am on the dashboard page
    Then I can see that no organisations have access to my LPA
