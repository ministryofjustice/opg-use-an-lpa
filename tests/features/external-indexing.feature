@actor @viewer
Feature: Headers to prevent external Indexing
  As a user of the application
  I should be instructed not to index pages

  @smoke
  Scenario: Indexing is blocked
    When I access the service homepage
    Then I receive headers that block external indexing

