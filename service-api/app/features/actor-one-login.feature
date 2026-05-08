@actor @onelogin
Feature: Authorise One Login

  @acceptance
  Scenario: I initiate authentication via one login
    Given I wish to login to the use an lpa service
     When I start the login process
     Then I am redirected to the one login service

  @acceptance
  Scenario: I can successfully sign in via one login
    Given I have a local account
      And I have a matching One Login identity
     When I complete a One Login sign-in process
     Then I am returned to the use an lpa service
      And the login process is a success

  @acceptance
  Scenario Outline: I can successfully sign in via one login with a changed email
    Given I have a local account
      And the identity provided by One Login has a "new email"
     When I complete a One Login sign-in process
     Then I am returned to the use an lpa service
      And the login process is a success

  @acceptance
  Scenario Outline: I can successfully sign in via one login with a different subject
    Given I have a local account
      And the identity provided by One Login has a "different subject than expected"
     When I complete a One Login sign-in process
     Then I am returned to the use an lpa service
      And the login process is a success
