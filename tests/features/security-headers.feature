@actor @viewer
Feature: Headers to increase service security
  As a user of the application
  I should be instructed not to index pages

  @smoke
  Scenario: Indexing is blocked
    When I access the service homepage
    Then I receive headers that block external indexing

  @smoke
  Scenario: iframe embedding is blocked
    When I access the service homepage
    Then I receive headers that block external iframe embedding

  @smoke
  Scenario: Verify that a suitable Referrer-Policy header is included
    When I access the service homepage
    Then I receive headers that cause the browser to not inform the destination site any URL information

  @smoke
  Scenario: Verify that a suitable CORS header is included
    When I access the service homepage from localhost:9002
    Then I see headers that ensures avoiding everyone freely accessing any resources of that domain


