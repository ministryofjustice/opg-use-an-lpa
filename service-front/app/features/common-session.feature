@session
Feature: Session length is independent of cookie lifetime
  As a user of the application
  I should be warned of potential or actual session expiry
  Which requires a session length independent of cookie lifetime

  @ui @actor @viewer
  Scenario: A user session is created when accessing the application
    When I access the service home page
    Then I am given a session cookie

  @ui @actor
  Scenario: An expired user session will log a user out of the application
    Given I am a user of the lpa application
    And I am currently signed in
    When my session expires
    And on the next day
    And I view my dashboard
    Then I am taken to the home page
#
#  @ui @viewer
#  Scenario: An expired user session will take the user to the enter code page
#    Given I have been given access to an LPA via share code
#    And I access the viewer service
#    And I give a valid LPA share code
#    When my session expires
#    And I enter an organisation name and confirm the LPA is correct
#   Then I am taken to the session expired page
