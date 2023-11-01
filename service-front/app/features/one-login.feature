@onelogin
  Feature: Authenticate One Login

    @ui @actor @ff:allow_gov_one_login:true
    Scenario: I initiate authentication via one login
      Given I am on the temporary one login page
      When I click the one login button
      Then I am redirected to the redirect page

    @ui @actor @ff:allow_gov_one_login:true @welsh
    Scenario: I initiate authentication via one login in Welsh
      Given I am on the temporary one login page
      And I request to view the content in welsh
      When I click the one login button in welsh
      Then I am redirected to the redirect page