@actor @logging
Feature: Full featured logging capabilities are available
  As a developer of the UaLPA application
  I need to see logging information in all environments
  So that I can detect, diagnose and fix issues

  Background:
    Given I am a user of the lpa application
    And I have been given access to use an LPA via credentials
    And I am currently signed in

  @ui
  Scenario: An inbound tracing header is attached to outbound requests
    Given I attach a tracing header to my requests
    When I view my dashboard
    Then my outbound requests have attached tracing headers

