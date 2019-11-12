@actor @password
Feature: Password Reset
  If I have forgotten my password
  As a user of the use a lpa application
  I can request that I change it to a known value

  @integration
  Scenario: The user can request a password reset and get an email
    Given I am a user of the lpa application
    And I have forgotten my password
    When I ask for my password to be reset
    Then I receive unique instructions on how to reset my password

  @integration @wip
  Scenario: The user can follow their unique instructions to supply a new password
    Given I am a user of the lpa application
    And I have asked for my password to be reset
    When I follow my unique instructions on how to reset my password
    And I choose a new password
    Then my password has been associated with my user account
