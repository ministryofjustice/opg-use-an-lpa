@errorPage
Feature: Session length is independent of cookie lifetime
  As a user of the application
  I should be warned of potential or actual session expiry
  Which requires a session length independent of cookie lifetime

  @ui @actor @viewer
  Scenario: A user error page is shown when a wrong url is entered by user
    Given I access the service home page
    When I provide a wrong url that does not exist
    Then I should be shown an error page with details