@actor @cancelaccesscode
Feature: Actor able to cancel access code
  As a user
  I want to be able to cancel the access code I generated for an organisation
  So that no more the code can be used to view my LPA details

  Background:I want to see the code details
    Given I am a user of the lpa application
    And I am currently signed in
    And I have added an LPA to my account
    And I am on the dashboard page
    And I have generated an access code for an organisation

  @integration @acceptance
  Scenario: As a user be able to see option for cancelling the access code for an organisation
    Given I am on the create viewer code page
    When I request to view the check access code page
    Then I want to see the code details
    And I see the option to cancel the code

  @integration @acceptance
  Scenario: As a user be able to cancel a viewer code
    Given I have generated an access code for an organisation
    When I request to view the check access code page
    And  I cancel the organisation access code
    Then I want to be asked for confirmation prior to cancellation
#
#  @integration @acceptance
#  Scenario: As a user be able to view the cancelled viewer codes
#    Given I have generated an access code for an organisation
#    When I request to cancel the organisation access code
#    And I am on the confirm cancel code page
##    And I confirm cancellation of the chosen viewer code
#   Then I should be shown the details of the cancelled viewer code with cancelled status