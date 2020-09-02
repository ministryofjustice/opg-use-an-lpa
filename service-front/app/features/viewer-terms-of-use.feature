@viewer @termsOfUse
Feature: View terms of use from enter code page
  As an organisation using the service
  I want to check the terms of use
  So that I can be be sure of my rights and responsibilities for using the service

  @ui
  Scenario: The user can access the terms of use from the enter code page
    Given I am on the enter code page
    When I request to see the viewer terms of use
    Then I can see the viewer terms of use

#  @ui
#  Scenario: The user can go back to the enter code page from the terms of use page
#    Given I am on the viewer terms of use page
#    When I request to go back to the enter code page
#    Then I am taken back to the enter code page

  @ui
  Scenario: Viewer can access the cookies page from the terms of use page
    Given I am on the viewer terms of use page
    When I navigate to the viewer cookies page
    Then I am taken to the viewer cookies page
