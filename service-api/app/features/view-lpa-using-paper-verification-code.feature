@viewer @viewlpa @paperverificationcode @ff:support_datastore_lpas:true
Feature: View an LPA using a paper verification code
  As an organisation who has been given a paper verification code
  I can enter that code and see the details of an LPA
  So that I can carry out business functions

  @acceptance
  Scenario: View an LPA with a paper verification code
    Given I have access to an LPA via a paper verification code
    And I access the viewer service
    When I provide donor surname and paper verification code
    Then I am told that the LPA has been found

  @acceptance
  Scenario: View an LPA with a cancelled paper verification code
    Given I have access to an LPA via "a cancelled" paper verification code
    And I access the viewer service
    When I provide donor surname and paper verification code
    Then I am told that the paper verification code has been cancelled

  @acceptance
  Scenario: View an LPA with an expired paper verification code
    Given I have access to an LPA via "an expired" paper verification code
    And I access the viewer service
    When I provide donor surname and paper verification code
    Then I am told that the paper verification code has expired
