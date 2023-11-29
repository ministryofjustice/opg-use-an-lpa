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

    @ui @actor @ff:allow_gov_one_login:true @welsh
    Scenario Outline: One Login returns a specific error
      Given I have logged in to one login in <language>
      When One Login returns a "<error_type>" error
      Then I am redirected to the login page with a "<error_type>" error and "<error_message>"

    Examples:
      | language | error_type              | error_message                           |
      | English  | access_denied           | Tried to login however access is denied |
      | English  | temporarily_unavailable | One Login is temporarily unavailable    |
      | Welsh    | access_denied           | Mae problem                             |
      | Welsh    | temporarily_unavailable | Mae problem                             |

    @ui @actor @ff:allow_gov_one_login:true
    Scenario Outline: One Login returns a generic error
      Given I have logged in to one login in English
      When One Login returns a "<error_type>" error
      Then I should be shown an error page

      Examples:
        | error_type                |
        | unauthorized_client       |
        | invalid_request           |
        | invalid_scope             |
        | unsupported_response_type |
        | server_error              |

    @ui @actor @ff:allow_gov_one_login:true @welsh
    Scenario Outline: One Login returns a generic error in Welsh
      Given I have logged in to one login in Welsh
      When One Login returns a "<error_type>" error
      Then I should be shown an error page in Welsh

      Examples:
        | error_type                |
        | unauthorized_client       |
        | invalid_request           |
        | invalid_scope             |
        | unsupported_response_type |
        | server_error              |