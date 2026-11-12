@actor @onelogin
Feature: Authorise One Login

  @acceptance
  Scenario: I initiate authentication via one login
    Given I wish to login to the use an lpa service
     When I start the login process
     Then I am redirected to the one login service

  @acceptance
  Scenario: I can successfully sign in via one login as a completely new user
    Given I do not have a local account
    And I have an unknown One Login identity
    When I complete a One Login sign-in process
    Then I am returned to the use an lpa service
    And the login process is a success

  @acceptance
  Scenario: I can successfully sign in via one login as a migrating user
    Given I have a local account
    And I have an unknown One Login identity
    When I complete a One Login sign-in process
    Then I am returned to the use an lpa service
    And the login process is a success

  @acceptance
  Scenario: I can sign in as a new user with an orphan identity pin
    Given I do not have a local account
      And I have an unknown One Login identity
      And my One Login identity has an orphan identity pin
     When I complete a One Login sign-in process
     Then I am returned to the use an lpa service
      And the login process is a success
      And the orphan identity pin has been recovered

  @acceptance
  Scenario: I can migrate my local account with an orphan identity pin
    Given I have a local account
      And I have an unknown One Login identity
      And my One Login identity has an orphan identity pin
     When I complete a One Login sign-in process
     Then I am returned to the use an lpa service
      And the login process is a success
      And the orphan identity pin has been recovered

  @acceptance
  Scenario: I can successfully sign in via one login as an existing user
    Given I have a local account
      And I have a matching One Login identity
     When I complete a One Login sign-in process
     Then I am returned to the use an lpa service
      And the login process is a success

  @acceptance
  Scenario: I can successfully sign in via one login with a changed email
    Given I have a local account
      And the identity provided by One Login has a "new email"
     When I complete a One Login sign-in process
     Then I am returned to the use an lpa service
      And the login process is a success

  @acceptance
  Scenario: I can successfully sign in via one login with a different subject
    Given I have a local account
      And the identity provided by One Login has a "different subject than expected"
     When I complete a One Login sign-in process
     Then I am returned to the use an lpa service
      And the login process is a success
