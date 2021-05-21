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
      | noActiveCodes                | code1Expiry | code2Expiry |
      | 2 active codes               |  +3weeks    | +3weeks     |
      | 1 active code                |  -1week     | +3weeks     |
      | No organisations have access |  -1day      | -1week      |

  @acceptance @integration
  Scenario: As a user I can see the number of active access codes an LPA has
    Given I have added an LPA to my account
    When I am on the dashboard page
    Then I can see that no organisations have access to my LPA

  @acceptance @integration
  Scenario: As a user I cannot see the LPA whose status is not either registered or cancelled
    Given The status of the LPA changed from Registered to Suspended
    When I am on the dashboard page
    Then I cannot see the added LPA
