@pact
Feature: Runs a basic check on Healthcheck endpoint on integration middleware

  @pact
  Scenario: Healthcheck Endpoint responds OK
    When I request the status of the API HealthCheck EndPoint
    Then I should receive the status of the API
