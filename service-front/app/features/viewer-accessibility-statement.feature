@viewer @viewerAccessibilityStatement
Feature: Viewer accessibility statement
  As a viewer
  I want to have access to an accessibility statement
  So that I know what is and isn't accessible to me

  @ui
  Scenario: As a viewer I want to be able to access an accessibility statement
    Given I am on the enter code page
    When I request to view the accessibility statement
    Then I can see the accessibility statement for the View service
