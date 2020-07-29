@actor @gov-uk-link

Feature: Gov-UK hyperlink takes user to gov uk homepage
  As a actor/viewer
  I want to be able to access the Gov UK site
  So I can access other Government services

  @ui
  Scenario: The user can access the Gov Uk site from the banner across our site
    Given I am on the triage page
    When I navigate to the gov uk page
    Then I expect to be on the Gov uk homepage
