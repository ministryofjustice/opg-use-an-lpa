@onelogin
  Feature: Authorise One Login

    @ui @actor @ff:allow_gov_one_login:true
    Scenario: I initiate authorise via one login
      Given I am on the temporary one login page
      When I click the one login button
      Then I am redirected to the redirect page