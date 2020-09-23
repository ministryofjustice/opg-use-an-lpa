@actor @viewer @i18n
Feature: The application is available in both English and Welsh.
  As a user of the lpa application
  I can access an English and Welsh version of the application
  So that I can use it in the language of my choice

  @smoke
  Scenario: The application home page defaults to English
    Given I access the service homepage
    Then I can see English text
    And the documents language is set to English

  @smoke
  Scenario: The application home page is available in Welsh
    Given I access the Welsh service homepage
    Then I can see Welsh text
    And the documents language is set to Welsh
