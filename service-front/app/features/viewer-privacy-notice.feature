@viewer @privacyNotice
Feature: View privacy notice from terms of use page
  As an organisation using the service
  I want to check the privacy notice
  So that I can be be sure of the way that the data is processed for an LPA

  @ui
  Scenario: The user can access the privacy notice from the terms of use page
    Given I am on the enter code page
    When I request to see the viewer terms of use
    And I request to see the viewer privacy notice
    Then I can see the viewer privacy notice

  @ui
  Scenario: Viewer can access the cookies page from the privacy notice page
    Given I am on the viewer privacy notice page
    When I navigate to the viewer cookies page
    Then I am taken to the viewer cookies page
