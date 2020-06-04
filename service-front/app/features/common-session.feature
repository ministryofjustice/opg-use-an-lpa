@session
Feature: Session length is independent of cookie lifetime
  As a user of the application
  I should be warned of potential or actual session expiry
  Which requires a session length independent of cookie lifetime

  @ui @actor @viewer
  Scenario: A user session is created when accessing the application
    When I access the service homepage
    Then I am given a session cookie

  @ui @actor
  Scenario: An expired user session will log a user out of the application
    Given I am a user of the lpa application
    And I am currently signed in
    When my session expires
    And I view my dashboard
    Then I am taken to the login page
