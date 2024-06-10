Legacy versus Modernise data formats
--------------------------------------

Top level Sections
-------------------
There is a donor section for both Legacy and Modernise
Legacy has attorneys and replacementAttorneys lists,  Modernise has status field for Attorney of which "replacement" is a possible value, we can load Modernise's attorneys marked in this way, into our replacementAttoryneys section
There is TrustCorporations for both
Modernise has a schema version number which could in future be used to do different logic with fields if they are different in a later version


People e:g Donor, Attorneys including Replacement Attorneys  (Use doesn't need to know about Certificate Provider or People to Notify)
------------------------------------------------------------

Same in each
------------
* email
* country   (within address block)
* postcode

New fields for Modernise (all persons) not in Legacy
-------------------------------------------------------
* town -  we will put this in AddressLine3 where needed
* phone - (only Attorney and Certificate Provider) we need to add this to the data model
* channel - maybe be needed in future by Use to deduce whether IaP is image or text

New fields for Modernise not used in Use An LPA
--------------------------------------------------
* contactLanguagePreference - we will ignore because instead we use the Use account's preferences
* identityCheck
* signedAt

Present in Legacy but Missing in Modernise
-----------------------------------------------
* county -  Modernise just uses lines (1-3) instead 
* does not have Modernise equivalent
* address -> type "Primary" doesn't apply, in fact all Legacy addresses are primary
* companyName (wthin the person block) now appears to be "name"  within Trust Corporation block
* systemStatus  - boolean

Renamed fields
-----------------
* Legacy OtherNames field equivalent to Modernise otherNamesKnownBy
* Legacy surnName field equivalent to Modernise lastName
* Legacy dob equivalent to Mdoernise dateOfBirth - format the same (YYYY-MM-DD)
* Legacy addressLineN becomes lineN in Modernise (within address block)

Other differences
----------------
* Legacy allowes multiple addresses.  But multiples not used in practice, so can just use 1st one for Legacy, use the only one for Modernise
* Legacy has firstName and middleName,  vs Modernise has these merged into 1 as firstNames

Trust Corporations
----------------------
Same changes as for People above. Additionally, companyName appears to be renamed companyNumber in Modernise
  "trustCorporations": [],
}
=================================

Other fields (top level in json):
-------------------------
* uid same but it is within properties block in Modernise (and M on the front, for Modernise)
?? Legacy "applicationHasGuidance": false, may not have Modernise equiv  - the field is either there or not
?? Legacy "applicationHasRestrictions": true, may not have Modernise equiv  - the field is either there or not
* Legacy "applicationType": "Classic", doesn't have Modernise equivalent but should not be needed
status same but within properties block in Modernise,  capital Registered or Processing in legacy, lowercase enum registered or processing in Modernise
statusDate only exists in Legacy, should be able to manage without this for Use?
* legacy lpaDonorSignatureDate (YYYY-MM-DD) appears to be replaced with signedAt (2024-01-10T23:00:00Z) for Modernise , probably not needed for Use an LPA
* channel (top-level) - may be needed in future by Use to deduce whether IaP is image or text

Renamed fields
-----------------
*  attorneyActDecisions is renamed whenTheLpaCanBeUsed
* "caseAttorneyJointly": true, "caseAttorneyJointlyAndJointlyAndSeverally": false, "caseAttorneyJointlyAndSeverally": false, "caseAttorneySingular": false, are replaced by 1 enum - "howAttorneysMakeDecisions": "jointly" 
* "caseSubtype": "hw", is replaced by "lpaType": "personal-welfare"
*  "lifeSustainingTreatment": "Option B", renamed to e:g "lifeSustainingTreatmentOption": "option-a"
*  withdrawnDate renamed withdrawnAt
* "registrationDate": "2024-06-04", renamed registrationAt


new fields in Modernise
-------------------------
"certificateProviderNotRelatedConfirmedAt" -  Not needed for Use
"channel": "online",  top-level and for all persons individually-- we may need this for Use in future

Present in Legacy but Missing in Modernise
----------------------------------------------
* "hasSeveranceWarning": false,   This is yet to be defined in Modernise
*  "receiptDate": "2014-09-26", no Modernise equivalwnt but unlikely to be needed by Use an LPA
* "dispatchDate": "2021-10-14", no Modernise equivalwnt but unlikely to be needed by Use an LPA
* "invalidDate": "2024-06-04", no Modernise equivalwnt but unlikely to be needed by Use an LPA
* "rejectedDate": null, no Modernise equivalwnt but unlikely to be needed by Use an LPA
* "lpaIsCleansed": false,  Does not Apply to Modernise
* Legacy "cancellationDate": "2020-03-17", appears not to have an equiv in Modernise - we would need some way of identifying if LPA is cancelled
* "onlineLpaId": "A15527329531"  not needed in Use as we use uid

