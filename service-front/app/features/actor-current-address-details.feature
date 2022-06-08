@actor @actorCurrentAddress
Feature: Say if the given address is on paper LPA
  As a business user
  I would like to know if the user has provided their address as in paper LPA
  So that the cleansing team find it easier to check the validity of their request

  Background:
    Given I have been given access to use an LPA via a paper document
    And I am a user of the lpa application
    And I am currently signed in

  @ui @ff:allow_older_lpas:true
  Scenario: An attorney user is asked for their address if they have a partial match
    Given My LPA has been found but my details did not match
    Then I will be asked for my full address

  @ui @ff:allow_older_lpas:true
  Scenario: A user is taken to selecting their role page when address on paper provided
    Given My LPA has been found but my details did not match
    When I will be asked for my full address
    And I have provided my current address
    Then I am asked for my role on the LPA

  @ui @ff:allow_older_lpas:true
  Scenario: A user is taken to selecting their role page when they are not sure of address on paper provided
    Given My LPA has been found but my details did not match
    When  I select I am not sure the address is same as on paper LPA
    Then I am asked for my role on the LPA

  @ui @ff:allow_older_lpas:true
  Scenario: A user is asked for their address on paper LPA if they have not provided the same address as in paper
    Given My LPA has been found but my details did not match
    When I will be asked for my full address
    And I select the address is not same as on paper LPA
    And I am asked for my address from the paper LPA

  @ui @ff:allow_older_lpas:true
  Scenario: An attorney user is taken back to address page  provided
    Given My LPA has been found but my details did not match
    When I will be asked for my full address
    And I have provided my current address
    And  I am asked for my role on the LPA
    When I click the Back link on the page
    Then I will be navigated back to more details page

  @ui @ff:allow_older_lpas:true
  Scenario: A user is asked for their paper address on paper LPA if they have not provided the same address as in paper
    Given My LPA has been found but my details did not match
    When I will be asked for my full address
    And I select the address is not same as on paper LPA
    And I am asked for my address from the paper LPA
    When I click the Back link on the page
    Then I will be navigated back to address on paper page

  @ui @ff:allow_older_lpas:true
  Scenario Outline: The user is shown an error message when user does not make entries on full address page
    Given My LPA has been found but my details did not match
    When I will be asked for my full address
    When I do not provide required entries for <address_line_1> <town> <address_as_on_lpa> on the LPA
    Then I am told that my input is invalid because <reason>

    Examples:
      | address_line_1    | town        | address_as_on_lpa  | reason                   |
      |                   | town1       |    Yes             | Enter your address       |
      |   abc  house      |             |    Yes             | Enter your town or city  |
      |                   |             |    Yes             | Enter your address       |

  @ui @ff:allow_older_lpas:true
  Scenario: The user is shown an error message when user does not tell us if it's their current address
    Given My LPA has been found but my details did not match
    When I will be asked for my full address
    And I do not provide any selections for current address on the LPA
    Then I am shown an error telling me to select if current address on the LPA