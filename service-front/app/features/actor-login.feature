@actor @login
Feature: Actor login
  As a user
  I want to login
  So that I can see and share my lpas

#  1. login successful without  lpas
#  2.  login successful with lpas
  Background:
    Given I have asked for creating new account

  @ui @integration
  Scenario: User wants to sign in first time
    Given My account is active
    When I sign in
    Then I should be taken to the new users first page

  @ui @integration
  Scenario: User wants to sign in
    Given My account is active
    When I revisit to sign in again
    Then I should be taken to 





