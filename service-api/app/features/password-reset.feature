@actor @password
Feature: Password Reset
  As a user
  If I have forgotten my password
  I can request that I change it to a known value

  Background:
    Given I am a user of the lpa application

  @integration @acceptance
  Scenario: The user can request a password reset and get an email
    Given I have forgotten my password
    When I ask for my password to be reset
    Then I receive unique instructions on how to reset my password
 
  Scenario: The user can follow their unique instructions to supply a new password
    Given I have asked for my password to be reset
    When I follow my unique instructions on how to reset my password
    And I choose a new password
    Then my password has been associated with my user account

  Scenario: The user cannot follow expired instructions to supply a new password
    Given I have asked for my password to be reset
    When I follow my unique expired instructions on how to reset my password
    Then I am told that my instructions have expired
    And I am unable to continue to reset my password