@viewer @actor @healthcheck
Feature: Check status of the service
  As a developer or CI service,
  I want to access a url to examine the health, version and other metadata,
  So that I can be sure the service is behaving as expected

  @smoke
  Scenario: I want to check the service health
    Given I fetch the healthcheck endpoint
    Then I see JSON output
    And it contains a "healthy" key/value pair

  @smoke
  Scenario: I want to discover the service version
    Given I fetch the healthcheck endpoint
    Then I see JSON output
    And it contains a "version" key/value pair
