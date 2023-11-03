@onelogin
  Feature: Authenticate One Login

    @ui @actor @ff:allow_gov_one_login:true
    Scenario: I initiate authentication via one login
      Given I am on the temporary one login page
      When I click the one login button
      Then I am redirected to the redirect page in English

    @ui @actor @ff:allow_gov_one_login:true @welsh
    Scenario: I initiate authentication via one login in Welsh
      Given I am on the temporary one login page
      And I select the Welsh language
      When I click the one login button
      Then I am redirected to the redirect page in Welsh