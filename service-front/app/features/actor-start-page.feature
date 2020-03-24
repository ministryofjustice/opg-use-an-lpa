@actor @start
Feature: Start page
  As a user
  I want to be shown instructions on how to get started with the Use an Lpa service
  So that I know how to use the service correctly for the first time

  @ui
  Scenario: The user is taken to the create account page when they request to from the get started page
    Given I am on the get started page
    When I request to create an account
    Then I am taken to the create account page

  @ui
  Scenario: The user is taken to the login page when they click the 'I already have an account' link
    Given I am on the get started page
    When I click the I already have an account link
    Then I am taken to the login page
