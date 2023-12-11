@smoke
Feature: User is sent to the live service URL
  As a user of the service who has been given an old web address for the service,
  I want to be redirected to the new live service url,
  So that I can continue to use the service correctly.

  Background:
    # This feature is implemented at the load balancer level and can be found in the terraform
    # terraform/environment/region/actor_load_balancer.tf:36
    # terraform/environment/region/viewer_load_balancer.tf:35

  @smoke @viewer
  Scenario: I start a view journey
    Given I access the service with the old web address
    Then the service homepage should be shown securely

  @smoke @actor
  Scenario: I start a use journey
    Given I access the service with the old web address
    Then the service homepage should be shown securely
