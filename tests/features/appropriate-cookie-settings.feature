@viewer @actor @cookie
Feature: Appropriate cookie settings are configured
  As a developer or CI service,
  I want to ensure my application cookies are handled correctly by browsers,
  So that I can be sure that user sessions are as secure as possible

  @smoke
  Scenario: Check cookie-secure and http-only are set on the session cookie
    Given I access the service homepage
    Then the session cookie is marked secure and httponly
