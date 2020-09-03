@actor @triageEntry
Feature: Triage
  As a new user
  I want to create an account
  So that I can login to add my lpas to share

  @ui
  Scenario: The user can login directly when they say they have an existing account
    Given I am a user of the lpa application
    And I want to use my lasting power of attorney
    And I am on the triage page
    When I select the option to sign in to my existing account
    Then I am allowed to login

  @ui
  Scenario: The user can create new account page when they say they do not have an existing account
    Given I am on the triage page
    When I select the option to create a new account
    Then I am allowed to create an account

  @ui
  Scenario: The user can navigate back to triage page from login page
     Given I access the login form
     When I click the Back link on the page
     Then I should be taken to the triage page

  @ui
  Scenario: The user can navigate back to triage page from account creation page
    Given I access the account creation page
    When I click the Back link on the page
    Then I should be taken to the triage page

  @ui
  Scenario: The user sees an error message when they do not say if they have an existing account or if they want to create one
    Given I am on the triage page
    When I do not provide any options and continue
    Then I am not allowed to progress

  @ui
  Scenario: The user sees a banner about the fact that only LPAs issued after a certain date can be used on this service
    Given I am on the triage page
    Then I can see banner about existing LPAs

  @ui
  Scenario: The banner will take the user to the triage page unless they are signed in
    Given I am on the create account page
    When I click the Use a lasting power of attorney link on the page
    Then I am taken to the triage page of the service

  @ui
  Scenario: When signed in, clicking on the banner will redirect to the dashboard
    Given I am a user of the lpa application
    And I sign in
    And I am on the your details page
    When I click the Use a lasting power of attorney link on the page
    Then I am taken to the dashboard page
