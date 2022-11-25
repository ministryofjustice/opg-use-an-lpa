@actor @actorAbroad

Feature: User does not live in the UK so cannot supply a UK postcode

  Background:
    Given I am a user of the lpa application
    And I am currently signed in
    And I have been given access to use an LPA via a paper document
    And I have requested an activation key with valid details and do not live in the UK

  @ui
  Scenario: The user can see they have answered No to living in the UK
    Given I am on the check your answers page
    When I request an activation key for an LPA
    Then I am asked for my full address

  @ui
  Scenario: The live in UK status is recorded in the task sent to sirius
    Given I request an activation key for an LPA
    And I fill in my abroad address and I select the address is the same as on paper LPA
    And I confirm that I am the Attorney
    And I provide the donor's details
    And I provide my telephone number
    When I confirm that the data is correct and click the confirm and submit button
    Then It is recorded in the sirius task that the user lives abroad

  @ui
  Scenario: The user is told that they have already added this lpa
    Given I am on the check your answers page
    When I request an activation key for an LPA that already exists in my account
    Then I should be told that I have already added this LPA
