@actor @index
Feature: Index page
  As a user
  I want to start on the index page when I visit the service
  So that I can navigate through the service from the start

  @ui
  Scenario: The user is taken to the get started page when they request to get started from the index page
    Given I am on the index page
    When I request to get started with the service
    Then I am taken to the get started page

  @ui
  Scenario: The user is taken to the login page when they request to from the index page
    Given I am on the index page
    When I select the option to sign in to my existing account
    Then I am taken to the login page
