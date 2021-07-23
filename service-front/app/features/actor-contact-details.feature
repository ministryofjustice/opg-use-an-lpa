@actor @contact-details
Feature: Add contact details when LPA needs cleansing
  As a user
  I want to be able to add my contact details
  So that I can be contacted when I potentially need to update my details in cleansing

  Background:
    Given I am a user of the lpa application
    And I am currently signed in

  #TODO : Change test to use actual previous page rather than just the dashboard
  @ui
  Scenario: The user can access the contact-details page
    Given I have navigated to the contact-details page
    When I enter my contact details
    Then I do not see any errors

  @ui
  Scenario: The user must enter a telephone number or click the no phone box
    Given I have navigated to the contact-details page
    When I enter nothing
    Then I am told that I must enter a phone number
