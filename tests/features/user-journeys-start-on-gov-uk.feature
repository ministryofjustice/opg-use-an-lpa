@smoke
Feature: User Journeys start on Gov.uk
  As a user of the service,
  I want to have an introduction to the service,
  So that I know what it is about and what I will need to use it

  Background:
    # This feature is implemented at the load balancer level and can be found in the terraform
    # terraform/environment/region/use_load_balancer.tf:90
    # terraform/environment/region/viewer_load_balancer.tf:77

  @smoke @viewer
  Scenario: I start a view journey
    Given I access the service root path
    Then the service should redirect me to "https://www.gov.uk/view-lasting-power-of-attorney"

  @smoke @actor
  Scenario: I start a use journey
    Given I access the service root path
    Then the service should redirect me to "https://www.gov.uk/manage-lasting-power-attorney/use-lasting-power-of-attorney"
