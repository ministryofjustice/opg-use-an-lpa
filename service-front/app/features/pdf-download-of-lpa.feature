@viewer @pdfdownload
Feature: PDF download
  As a viewer of a LPA
  I can download that LPA as PDF document
  So I can use it as a part of my business processes

  Background:
    Given I have been given access to an LPA via share code
    And I access the viewer service
    And I give a valid LPA share code
    And I enter an organisation name and confirm the LPA is correct

  @integration @ui
  Scenario: The user can download a document version of the LPA they're viewing
    Given I am viewing a valid LPA
    When I choose to download a document version of the LPA
    Then a PDF is downloaded
