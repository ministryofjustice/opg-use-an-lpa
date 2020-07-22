@viewer @actor
Feature: User Journeys start on Gov.uk
  As a user of the service,
  I want to have an introduction to the service,
  So that I know what it is about and what I will need to use it

  @smoke @viewer
  Scenario: I start a view journey
    Given I access the service root path
    Then the service should redirect me to "https://www.gov.uk/view-lasting-power-of-attorney"

  @smoke @actor
  Scenario: I start a use journey
    Given I access the service root path
    Then the service should redirect me to "https://www.gov.uk/use-lasting-power-of-attorney"
