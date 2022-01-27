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
    Then I can see that my LPA has <noActiveCodes> with expiry dates <code1Expiry> <code2Expiry>

    Examples:
      | noActiveCodes                | code1Expiry | code2Expiry |
      | 2 active codes               |  +3weeks    | +3weeks     |
      | 1 active code                |  -1week     | +3weeks     |
      | No organisations have access |  -1day      | -1week      |

  @integration @ui
  Scenario: As a user I can see the number of active access codes an LPA has
    Given I am on the dashboard page
    Then I can see that no organisations have access to my LPA

  @ui
  Scenario: As a user I can see the message on instructions and preferences
    Given I am on the dashboard page
    Then I can see the message Important: This LPA has instructions or preferences

  @ui
  Scenario: As a user I can see the read more link in the message on instructions and preferences
    Given I am on the dashboard page
    Then I can see Read more link along with the instructions or preference message

  @ui
  Scenario: As a user I am navigated to the instructions and preferences page
    Given I am on the dashboard page
    And I click the Read more link in the instructions or preference message
    Then I am navigated to the instructions and preferences page

  @ui
  Scenario: Check back function on instructions and preferences page
    Given I am on the instructions and preferences page
    And I click the Back to your LPAs link on the page
    Then I should be taken to the <dashboard> page

  @ui
  Scenario: As a user I am shown how many LPAs are in my account
    Given I have added an additional LPA to my account
    When I am on the dashboard page
    Then I am told that I have 2 LPAs in my account
