@actor @cancelaccesscode
Feature: Actor able to cancel access code
  As a user
  I want to be able to cancel the access code I generated for an organisation
  So that no more the code can be used to view my LPA details

  Background:I want to see the code details
    Given I am a user of the lpa application
    And I am currently signed in
    And I have added an LPA to my account
    And I have generated an access code for an organisation and can see the details

  @integration @acceptance
  Scenario: As a user be able to see option for cancelling the access code for an organisation
     Given I can see all of my access codes and their details
     When I want to cancel the access code for an organisation
     Then I want to see the option to cancel the code

  @integration @acceptance
  Scenario: As a user be able to cancel a viewer code
    Given I can see all of my access codes and their details
    When  I cancel the organisation access code
    Then I want to be asked for confirmation prior to cancellation

  @integration
  Scenario: As a user be able to view the cancelled viewer codes
    Given I can see all of my access codes and their details
    When I cancel the organisation access code
    And I confirm cancellation of the chosen viewer code
    Then I should be shown the details of the cancelled viewer code with cancelled status

  @integration @acceptance
  Scenario: As a user be able to view the cancelled viewer codes
    Given I can see all of my access codes and their details
    When I cancel the organisation access code
    And I do not confirm cancellation of the chosen viewer code
    Then I should be taken back to the access code summary page