@actor @requestActivationKey
Feature: Ask for an activation key
  As a user
  I want to be able to ask for an activation key
  So that I can add an older LPA to my account

  Background:
    Given I am a user of the lpa application
    And I am currently signed in

  @ui @ff:allow_meris_lpas:false
  Scenario Outline: The user cannot request an activation key with an invalid LPA reference number
    Given I am on the request an activation key page
    When I request an activation key with an invalid lpa reference number format of "<reference number>"
    Then I am told that my input is invalid because <reason>

    Examples:
      | reference number | reason                                             |
      | 70000000000      | The LPA reference number you entered is too short  |
      | 7000-0000-00000  | The LPA reference number you entered is too long   |
      | 70000000ABCD     | Enter the 12 numbers of the LPA reference number. Do not include letters or other characters |
      |                  | Enter the LPA reference number                     |
      | 700000000253     | The LPA reference number provided is not correct   |
      | 100000000253     | LPA reference numbers that are 12 numbers long must begin with a 7   |
      | 700000000@53     | Enter the 12 numbers of the LPA reference number. Do not include letters or other characters|


  @ui
  Scenario Outline: The user cannot request an activation key without inputting their name
    Given I am on the ask for your name page
    When I request an activation key without entering my <data>
    Then I am told that my input is invalid because <reason>

    Examples:
      | data       | reason |
      | firstnames | Enter your first names   |
      | last name  | Enter your last name     |

  @ui
  Scenario Outline: The user cannot request an activation key with an invalid dob
    Given I am on the ask for your date of birth page
    When I request an activation key with an invalid DOB format of "<day>" "<month>" "<year>"
    Then I am told that my input is invalid because <reason>

    Examples:
      | day | month | year | reason                                                                                      |
      | 32  | 05    | 1975 | Date of birth must be a real date                                                           |
      | 10  | 13    | 1975 | Date of birth must be a real date                                                           |
      | XZ  | 10    | 1975 | Date of birth must be a real date                                                           |
      |     | 10    |      | Date of birth must include a day Date of birth must include a year                          |
      |     |       | 1975 | Date of birth must include a day Date of birth must include a month                         |
      | XZ  |       |      | Date of birth must include a month Date of birth must include a year                        |
      | 10  | 05    | 3000 | Date of birth must be in the past                                                           |
      |     |       |      | Enter your date of birth                                                                    |
      | 05  | 12    | 2020 | Check your date of birth is correct - you cannot be an attorney or donor if you’re under 18 |

  @ui
  Scenario Outline: The user cannot request an activation key when they give an invalid response to if they live in the uk
    Given I am on the do you live in the UK page
    When I request an activation key with an invalid live in the UK answer <live_in_uk> <postcode>
    Then I am told that my input is invalid because <reason>

    Examples:
    | live_in_uk | postcode | reason                       |
    | Yes        |          | Enter your postcode          |
    |            |          | Select yes if you live in the UK |
    
  @ui
  Scenario: The user is taken to check their answers when they request an activation key with valid details
    Given I am on the request an activation key page
    When I request an activation key with valid details
    Then I am asked to check my answers before requesting an activation key

  @ui
  Scenario: As a user I am unable to visit the your name page without filling in my other details before
    Given I am on the request an activation key page
    When I visit the Your Name page without filling out the form
    Then I am redirected to the reference number page

  @ui
  Scenario: As a user I am unable to visit the date of birth page without filling in my other details before
    Given I am on the request an activation key page
    When I visit the Date of Birth page without filling out the form
    Then I am redirected to the reference number page

  @ui
  Scenario: As a user I am unable to visit the postcode page without filling in my other details before
    Given I am on the request an activation key page
    When I visit the Postcode page without filling out the form
    Then I am redirected to the reference number page

  @ui @ff:allow_meris_lpas:true
  Scenario Outline: The user cannot request an activation key with an invalid Meris reference number
    Given I am on the request an activation key page
    When I request an activation key with an invalid lpa reference number format of "<reference number>"
    Then I am told that my input is invalid because <reason>

    Examples:
      | reference number | reason |
      | 70000000000      | Enter an LPA reference number that is either 7 or 12 numbers long |
      | 7000045          | LPA reference numbers that are 7 numbers long must begin with a 2 or 3  |
      | 500000000526     | LPA reference numbers that are 12 numbers long must begin with a 7 |
