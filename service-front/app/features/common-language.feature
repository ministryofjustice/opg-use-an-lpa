@actor @viewer @welsh
Feature: The application supports Welsh as a language
  As a developer of the UaLPA application
  I should be able to select a language using a url prefix
  So that I can dictate the Locale of the application

  @ui @welsh
  Scenario: A language prefix can be specified
    Given I prefix a url with the welsh language code
    When I access the service home page
    Then I should be on the welsh home page of the service
