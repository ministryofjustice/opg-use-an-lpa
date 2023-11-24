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

    @ui @actor @ff:allow_gov_one_login:true
    Scenario Outline: One Login returns an access denied error
      Given I am on the temporary one login page
      And I click the one login button
      When One Login returns a <error_type> error
      Then I am redirected to the login page with a <error_type> error message
      And I see the text <error_message>

    Examples:
      | error_type              | error_message                           |
      | access_denied           | Tried to login however access is denied |
      | temporarily_unavailable | One Login is temporarily unavailable    |
