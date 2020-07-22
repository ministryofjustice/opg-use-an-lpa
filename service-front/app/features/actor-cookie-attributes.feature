@actor @cookie-attributes
Feature: cookie attributes
  As a user
  I I want to ensure cookie attributes are set when I visit the service
  So that I ensure the service I access is safe and secure

  @ui
  Scenario: Check cookie-secure set and http-only is set to True
    Given I am a user of the lpa application
    And I am currently signed in
    Then I want to ensure cookie attributes are set

