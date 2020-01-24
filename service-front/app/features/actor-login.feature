@actor @login
Feature: A user of the system is able to login
  As a user of the lpa application
  I can login using my credentials
  So that I can carry out operations within the application

  @ui
  Scenario: A user can login
    Given I am a user of the lpa application
    And I access the login form
    When I enter correct credentials
    Then I am signed in

  @ui
  Scenario: Visiting the login page when signed in will redirect to the dashboard
    Given I am a user of the lpa application
    And I am currently signed in
    When I attempt to sign in again
    Then I am directed to my dashboard
