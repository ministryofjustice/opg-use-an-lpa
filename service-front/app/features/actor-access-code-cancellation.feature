@actor @cancelaccesscode
Feature: Actor able to cancel access code
  As a user
  I want to be able to cancel the access code I generated for an organisation
  So that no more the code can be used to view my LPA details

  Background:
    Given I am a user of the lpa application
    And I am currently signed in
    And I have added an LPA to my account

  @ui
  Scenario: As a user be able to see viewer code details
    Given I am on the dashboard page
    And I have generated an access code for an organisation
   # And I want to check access page
    #When I want to cancel the code
    #Then I want to see the code details and the option to cancel the code

  #  @ui
#  Scenario: As a user be able to cancel a viewer code
#    Given I have generated an access code for an organisation
#    When I cancel the code
#    Then I want to be asked for confirmation prior to cancellation

#  Scenario: As a user be able to view the cancelled viewer codes
#    Given I have generated an access code for an organisation
#    When I  have confirmed cancellation of the chosen viewer code
#    Then I should be shown the details of the cancelled viewer code with cancelled status