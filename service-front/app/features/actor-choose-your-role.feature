@actor @chooseYourRole
Feature: Choose your role
  As a user
  I would like to enter the my role on the LPA
  So that the cleansing team find it easier to check the validity of my request

  Background:
    Given I have been given access to use an LPA via a paper document
    And I am a user of the lpa application
    And I am currently signed in
    And My LPA has been found but my details did not match

  @ui @ff:allow_older_lpas:true
  Scenario: The user is navigated to role page when they are not sure the provided address is as on paper LPA
    Given I select I am not sure the address is same as on paper LPA
    Then I am asked for my role on the LPA

  @ui @ff:allow_older_lpas:true
  Scenario: The user is navigated to role page when they provide address as on paper LPA
    Given I select the address is same as on paper LPA
    Then I am asked for my role on the LPA

  @ui @ff:allow_older_lpas:true
  Scenario: The user is navigated back to correct address page from role page
    Given I select the address is same as on paper LPA
    And I am asked for my role on the LPA  
    When I click the Back link on the page
    Then I will be navigated back to more details page

  @ui @ff:allow_older_lpas:true
  Scenario: The user is navigated back to correct address page from role page
    Given I select I am not sure the address is same as on paper LPA
    And I am asked for my role on the LPA
    When I click the Back link on the page
    Then I will be navigated back to more details page

  @ui @ff:allow_older_lpas:true
  Scenario: The user is shown an error message when user does not make a selection on role page
    Given I select the address is same as on paper LPA
    And I am asked for my role on the LPA
    When I do not provide any selections for my role on the LPA
    Then I am shown an error telling me to select my role on the LPA

  @ui @ff:allow_older_lpas:true
  Scenario: The user is navigated to paper address page when clicking back and it has been previously filled in
    Given I select the address not same as on paper LPA
    And I have given the address on the paper LPA
    When I am asked for my role on the LPA
    And I click the Back link on the page
    Then I am asked for my address from the paper LPA
