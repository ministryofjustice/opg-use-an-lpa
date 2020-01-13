@viewer @pdfdownload
Feature: PDF download
  As a viewer of a LPA
  I can download that LPA as PDF document
  So I can use it as a part of my business processes

  @integration
  Scenario: The user can download a document version of the LPA they're viewing
    Given I am viewing a valid LPA
    When I choose to download a document version of the LPA
    Then a PDF is downloaded

