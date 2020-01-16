@actor @login
Feature: Actor login
  As a user
  I want to login
  So that I can see and share my lpas

  Background:
    Given I have asked for creating new account

  @ui @integration
  Scenario: User sign in first time
    Given My account is active
    When I sign in for first time
    Then I should be taken to the new users first page

  @ui @integration
  Scenario: User signs in again to view blank dashboard
    Given My account is active
    When I sign in next time
    Then I should be taken to the dashboard page

  @ui @integration
  Scenario: User signs in to view added lpa
#    Given I had signed in
#    And I had added lpas
#    When I sign in again
#    Then Then I should be taken to dashboard page

  #  4. cant login - password incorrect
  #  5. logout [check if session cookie removed]




