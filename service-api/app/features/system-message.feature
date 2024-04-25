@actor @systemmessage
Feature: User able to view system message
  As a user
  I want to be able to see the system message if one is set

  @integration @acceptance
  Scenario: As a user I want be able to see the system message
     Given I am a user of the lpa application
     When I view a page and the system message is set
     Then I see the system message
