@actor @addAnOlderLpa
Feature: Add an older LPA
  As a user
  I expect to be able to add an LPA registered after 31st August 2019 to my account
  So that I can manage access to the LPA digitally

  Background:
    Given I have been given access to use an LPA via a paper document
    And I am a user of the lpa application
    And I am currently signed in

  @ui @integration @ff:allow_older_lpas:true
  Scenario: The user cannot add an old LPA which does not have a registered status
    Given I am on the add an older LPA page
    When I provide details of an LPA that is not registered
    And I confirm the details I provided are correct
    Then I am informed that an LPA could not be found with this reference number

  @ui @integration @ff:allow_older_lpas:false
  Scenario: The user cannot add an old LPA which does not have a registered status
    Given I am on the add an older LPA page
    When I provide details of an LPA that is not registered
    And I confirm the details I provided are correct
    Then I am informed that an LPA could not be found with these details

  @ui @integration
  Scenario: The user can add an older LPA to their account
    Given I am on the add an older LPA page
    And I provide the details from a valid paper document
    And I confirm the details I provided are correct
    And I am shown the details of an LPA
    When I confirm details shown to me of the found LPA are correct
    Then a letter is requested containing a one time use code
    And I receive an email confirming activation key request

  @ui @integration @ff:allow_older_lpas:false
  Scenario: The user cannot add an old LPA to their account as the data does not match
    Given I am on the add an older LPA page
    When I provide details that do not match a valid paper document
    And I confirm the details I provided are correct
    Then I am informed that an LPA could not be found with these details

  @ui @integration @ff:allow_older_lpas:false
  Scenario: The user cannot add an old LPA to their account when the LPA number does not exist
    Given I am on the add an older LPA page
    When I provide an LPA number that does not exist
    And I confirm the details I provided are correct
    Then I am informed that an LPA could not be found with these details

  @ui @integration @ff:allow_older_lpas:false
  Scenario: The user cannot add an older LPA to their account as their LPA is registered before Sept 2019
    Given I am on the add an older LPA page
    When I provide details from an LPA registered before Sept 2019
    And I confirm the details I provided are correct
    Then I am told that I cannot request an activation key

  @ui @integration
  Scenario: The user is informed when trying to add an older LPA to their account if an activation key already exists
    Given I am on the add an older LPA page
    And I already have a valid activation key for my LPA
    When I provide the details from a valid paper document
    And I confirm the details I provided are correct
    Then I am told that I have an activation key for this LPA and where to find it

  @ui @integration
  Scenario: The user is able to generate a new key even if an activation key already exists
    Given I am on the add an older LPA page
    And I already have a valid activation key for my LPA
    And I provide the details from a valid paper document
    And I confirm the details I provided are correct
    And I am told that I have an activation key for this LPA and where to find it
    When I request for a new activation key again
    Then I am told a new activation key is posted to the provided postcode

  @ui @integration
  Scenario: The user is unable to request key for an LPA that they have already added
    Given I am on the add an older LPA page
    And I have added an LPA to my account
    When I provide the details from a valid paper LPA which I have already added to my account
    And I confirm the details I provided are correct
    Then I should be told that I have already added this LPA

  # Older Older LPA Journey

  @ui @integration @ff:allow_older_lpas:true
  Scenario: The user cannot add an older LPA to their account that is not cleansed and reg date before Sep 2019
    Given I am on the Check we've found the right LPA page
    And My LPA was registered 'before' 1st September 2019 and LPA is 'not marked' as clean
    When I confirm details of the found LPA are correct
    Then I am asked for my contact details

  @ui @integration @ff:allow_older_lpas:true
  Scenario: The user can add an older LPA to their account that is cleansed and reg date before Sep 2019
    Given I am on the Check we've found the right LPA page
    And My LPA was registered 'before' 1st September 2019 and LPA is 'marked' as clean
    When I confirm details of the found LPA are correct
    Then a letter is requested containing a one time use code

  @ui @integration @ff:allow_older_lpas:true
  Scenario: The user can add an older LPA to their account that is not cleansed but reg date on or after Sep 2019
    Given I am on the Check we've found the right LPA page
    And My LPA was registered 'on or after' 1st September 2019 and LPA is 'not marked' as clean
    When I confirm details of the found LPA are correct
    Then a letter is requested containing a one time use code

  @ui @integration @ff:allow_older_lpas:true
  Scenario: The user can add an older LPA to their account that is cleansed and reg date on or after Sep 2019
    Given I am on the Check we've found the right LPA page
    And My LPA was registered 'on or after' 1st September 2019 and LPA is 'marked' as clean
    When I confirm details of the found LPA are correct
    Then a letter is requested containing a one time use code

  @ui @ff:allow_older_lpas:true
  Scenario: The user is taken to Check details and consent page and only shown contact details
    Given I am on the Check we've found the right LPA page
    And My LPA was registered 'before' 1st September 2019 and LPA is 'not marked' as clean
    And I confirm details of the found LPA are correct
    And I am asked for my contact details
    And I enter my telephone number
    When I am asked to consent and confirm my details
    Then I can only see my telephone number

  @ui @ff:allow_older_lpas:true
  Scenario: The user is asked for the donor's details if they are the attorney on the LPA
    Given My LPA has been found but my details did not match
    And I am asked for my role on the LPA
    When I confirm that I am the Attorney
    Then I am asked to provide the donor's details to verify that I am the attorney

  @ui @ff:allow_older_lpas:true
  Scenario: The attorney is asked for their contact details after providing donor details
    Given I am on the donor details page
    When I provide the donor's details
    Then I am asked for my contact details

  @ui @ff:allow_older_lpas:true
  Scenario: The user is asked for their contact details if they are the donor on the LPA
    Given My LPA has been found but my details did not match
    And I am asked for my role on the LPA
    When I confirm that I am the Donor
    Then I am asked for my contact details

  @ui @ff:allow_older_lpas:true
  Scenario: The user is not shown a warning on the check answers page if allow older lpas flag is on
    Given I am on the add an older LPA page
    When I provide the details from a valid paper document
    Then I am not shown a warning that my details must match the information on record

  @ui @ff:allow_older_lpas:false
  Scenario: The user is shown a warning on the check answers page if allow older lpas flag is on
    Given I am on the add an older LPA page
    When I provide the details from a valid paper document
    Then I am shown a warning that my details must match the information on record

  @ui @ff:allow_older_lpas:true
  Scenario: The user can add an older LPA to their account
    Given I am on the add an older LPA page
    And I provide the details from a valid paper document
    When I confirm the details I provided are correct
    Then I being the donor on the LPA I am not shown the donor name back again

  @ui @ff:allow_older_lpas:true
  Scenario: The user must enter a telephone number or click the no phone box
    Given I have reached the contact details page
    When I enter nothing
    Then I am told that I must enter a phone number or select that I cannot take calls

  @ui @ff:allow_older_lpas:true
  Scenario: The user is shown an error message when entering a telephone number and ticking the checkbox
    Given I have reached the contact details page
    When I enter both a telephone number and select that I cannot take calls
    Then I am told that I must enter a phone number or select that I cannot take calls

  @ui @ff:allow_older_lpas:true
  Scenario: The user can is shown the correct information on the check and consent page
    Given My LPA has been found but my details did not match
    And I confirm that I am the Donor
    When I enter my telephone number
    Then I am asked to consent and confirm my details
    And I can see my donor role and telephone number

  @ui @ff:allow_older_lpas:true
  Scenario: The user can is shown the correct information on the check and consent page
    Given My LPA has been found but my details did not match
    And I confirm that I am the Donor
    When I select that I cannot take calls
    Then I am asked to consent and confirm my details
    And I can see my donor role and that I have not provided a telephone number

  @ui @ff:allow_older_lpas:true
  Scenario: The user can is shown the correct information on the check and consent page
    Given My LPA has been found but my details did not match
    And I confirm that I am the Attorney
    And I provide the donor's details
    When I enter my telephone number
    Then I am asked to consent and confirm my details
    And I can see my attorney role, donor details and telephone number

  @ui @ff:allow_older_lpas:true
  Scenario: The user can is shown the correct information on the check and consent page
    Given My LPA has been found but my details did not match
    And I confirm that I am the Attorney
    And I provide the donor's details
    When I select that I cannot take calls
    Then I am asked to consent and confirm my details
    And I can see my attorney role, donor details and that I have not provided a telephone number

  @ui @integration @ff:allow_older_lpas:true
  Scenario: The user cannot add an old LPA to their account as the LPA reference number could not be found
    Given I am on the add an older LPA page
    When I provide an LPA number that does not exist
    And I confirm the details I provided are correct
    Then I am informed that an LPA could not be found with this reference number

  @ui @ff:allow_older_lpas:true @ff:streamline_cleansing_lpas:false
  Scenario: The user is asked for their role on the LPA if the data does not match
    Given I am on the add an older LPA page
    And I provide details that do not match a valid paper document
    When I confirm that those details are correct
    Then I am asked for my role on the LPA

  @ui @ff:allow_older_lpas:true @ff:streamline_cleansing_lpas:true
  Scenario: The user is asked for their role on the LPA if the data does not match
    Given I am on the add an older LPA page
    And I provide details of LPA registered after 1st September 2019 where do not match a valid paper document
    When I confirm the details I provided are correct
    Then I am informed that an LPA could not be found with this reference number

  # The following scenarios are for testing navigation of the OOL partial match journey

  @ui @ff:allow_older_lpas:true
  Scenario: The user skips to final consent page when they go back and change details
    Given I have reached the check details and consent page as the Attorney
    And I request to change the donors name
    When I change the donors name
    Then I am taken back to the consent and check details page
    And I can see the donors name is now correct

  @ui @ff:allow_older_lpas:true
  Scenario: The user skips to final consent page when they go back and change details
    Given I have reached the check details and consent page as the Attorney
    And I request to change my role
    When I confirm that I am the Donor
    Then I am taken back to the consent and check details page
    And I can see my role is now correctly set as the Donor

  @ui @ff:allow_older_lpas:true
  Scenario: The user skips to final consent page when they go back and change details
    Given I have reached the check details and consent page as the Donor
    And I request to change my role
    When I confirm that I am the Attorney
    And I provide the donor's details
    Then I am taken back to the consent and check details page
    And I can see my role is now correctly set as the Attorney

  @ui
  Scenario: The user is taken back to start of activation request if the found LPA is incorrect
    Given I am on the Check we've found the right LPA page
    When  I realise this is not the correct LPA
    Then I am taken back to the start of the "request an activation key" process

  @ui
  Scenario: The user can add an older LPA to their account
    Given I am on the add an older LPA page
    When I provide the details from a valid paper document
    And I confirm the details I provided are correct
    Then I being the donor on the LPA I am not shown the donor name back again

  @ui @ff:allow_older_lpas:true
  Scenario: The user is shown an error message when entering a telephone number and ticking the checkbox
    Given I have reached the contact details page
    When I enter both a telephone number and select that I cannot take calls
    Then I am told that I must enter a phone number or select that I cannot take calls

  @ui @ff:allow_older_lpas:true
  Scenario Outline: The user is shown an error message when entering invalid donor details
    Given My LPA has been found but my details did not match
    And I am asked for my role on the LPA
    And I confirm that I am the Attorney
    When I provide invalid donor details of <firstnames> <surname> <dob>
    Then I am told that my input is invalid because <reason>

    Examples:
      | firstnames | surname | dob        | reason                            |
      | Donor      | Person  |            | Enter the donor's date of birth   |
      |            | Person  | 01-01-1980 | Enter the donor's first names     |
      | Donor      |         | 01-01-1980 | Enter the donor's last name       |
      | Donor      | Person  | 41-01-1980 | Date of birth must be a real date |

  @ui @ff:allow_older_lpas:true
  Scenario: The user is taken back to the check answers page when lpa details match but is not cleansed
    Given I am on the Check we've found the right LPA page
    And My LPA was registered 'before' 1st September 2019 and LPA is 'not marked' as clean
    And I confirm details of the found LPA are correct
    And I am asked for my contact details
    When I click the Back link on the page
    Then I am taken back to the check answers page

  @ui @ff:allow_older_lpas:true
  Scenario: The user taken to contact details page, when LPA is not cleansed even though a key was requested previously
    Given I have previously requested an activation key
    And The activation key not been received or was lost
    And My LPA was registered 'before' 1st September 2019 and LPA is 'not marked' as clean
    When I request a new activation key
    Then I am asked for my contact details

  @ui @ff:allow_older_lpas:true
  Scenario: The user is  generated an activation key again, when LPA is cleansed
    Given I have previously requested an activation key
    And The activation key not been received or was lost
    And My LPA was registered 'on or after' 1st September 2019 and LPA is 'marked' as clean
    When I request a new activation key
    Then a letter is requested containing a one time use code

  @ui @integration @ff:allow_older_lpas:true
  Scenario: User receives a confirmation that key will be sent in 6 weeks, when lpa trying to be added is not cleansed but is a full match
    Given I have provided valid details that match the Lpa
    And My LPA was registered 'before' 1st September 2019 and LPA is 'not marked' as clean
    And I provide my contact details
    And I am asked to consent and confirm my details
    When I confirm that the data is correct and click the confirm and submit button
    Then I am told my activation key request has been received
    And I should expect it within 6 weeks time
    And I will receive an email confirming this information

  @ui @integration @ff:allow_older_lpas:true
  Scenario: User receives a confirmation that key will be sent in 6 weeks, when lpa trying to be added is not cleansed but is partial match
    Given My LPA has been found but my details did not match
    And My LPA was registered 'before' 1st September 2019 and LPA is 'not marked' as clean
    And I provide the additional details asked
    And I am asked to consent and confirm my details
    When I confirm that the data is correct and click the confirm and submit button
    Then I am told my activation key request has been received
    And I should expect it within 6 weeks time
    And I will receive an email confirming this information

  @ui @integration @ff:allow_older_lpas:true
  Scenario: User receives a confirmation that key will be sent in 2 weeks, when lpa trying to be added is cleansed and full match
    Given I have provided valid details that match the Lpa
    And My LPA was registered 'before' 1st September 2019 and LPA is 'marked' as clean
    When I confirm details shown to me of the found LPA are correct
    Then I am told my activation key is being sent
    And I should expect it within 2 weeks time
    And I will receive an email confirming this information
