@actor @login
Feature: A user of the system is able to login
  As a user of the lpa application
  I can login using my credentials
  So that I can carry out operations within the application

  Background:
    Given I am a user of the lpa application
    And I have been given access to use an LPA via credentials

  @smoke @ff:allow_gov_one_login:from_env
  Scenario: A user can login
    Given I access the login form
    When I enter correct credentials
    Then I am signed in
    Then Scripts Work 
