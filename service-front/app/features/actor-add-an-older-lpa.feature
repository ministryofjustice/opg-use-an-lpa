@actor @addAnOlderLpa
Feature: Add an older LPA
  As a user
  I expect to be able to add an LPA registered after 31st August 2019 to my account
  So that I can manage access to the LPA digitally

  Background:
    Given I have been given access to use an LPA via a paper document
    And I am a user of the lpa application
    And I am currently signed in

  @ui
  Scenario: The user is taken to the add lpa triage page from the dashboard
    Given I am on the dashboard page
    When I select to add an LPA
    Then I am on the add an LPA triage page

  @ui
  Scenario Outline: A user with an activation key is taken to the add an LPA page
    Given I am on the add an LPA triage page
    When I select <option> whether I have an activation key
    Then I will be taken to the appropriate <page> to add an lpa

    Examples:
      | option | page                             |
      | Yes    | Add a lasting power of attorney  |
      | No     | Ask for an activation key        |

  @ui
  Scenario: The user is shown an error message if they do not select either option
    Given I am on the add an LPA triage page
    When I do not select an option for whether I have an activation key
    Then I will be told that I must select whether I have an activation key

  @ui
  Scenario: Check cancel button on add an LPA triage page
    Given I am on the add an LPA triage page
    When I click the Cancel link on the page
    Then I should be taken to the <dashboard> page

  @ui
  Scenario: The user is shown additional content if they do not have an activation key
    Given I am on the add an LPA triage page
    When I say I do not have an activation key
    Then I am shown content explaining why I can not use this service

  @ui
  Scenario: The user is taken to request activation key
    Given I am on the add an LPA triage page
    When I say I do not have an activation key
    Then I am taken to page to ask for an activation key

  @ui
  Scenario: The user can go back and change their answers
    Given I have requested an activation key with valid details
    When I request to go back and change my answers
    Then I am taken back to previous page where I can see my answers and change them

  @ui @integration
  Scenario: The user can add an older LPA to their account
    Given I am on the add an older LPA page
    When I provide the details from a valid paper document
    And I confirm that those details are correct
    Then a letter is requested containing a one time use code

  @integration
  Scenario: The user cannot add an old LPA to their account as the data does not match
    Given I am on the add an older LPA page
    When I provide details that do not match a valid paper document
    And I confirm that those details are correct
    Then I am informed that an LPA could not be found with these details

  @integration
  Scenario: The user cannot add an older LPA to their account as their LPA is registered before Sept 2019
    Given I am on the add an older LPA page
    When I provide details from an LPA registered before Sept 2019
    And I confirm that those details are correct
    Then I am told that I cannot request an activation key

  @integration
  Scenario: The user cannot add an older LPA to their account if they have an activation key
    Given I am on the add an older LPA page
    And I already have a valid activation key for my LPA
    When I provide the details from a valid paper document
    And I confirm that those details are correct
    Then I am told that I have an activation key for this LPA and where to find it
