# 23.  Legacy versus Modernise data formats and proposed new data format

Date: 2024-06-11

## Status

Accepted

## Context

Use an LPA now needs to support both Legacy LPAs from Sirius and Modernise LPAs from the Data Store. We need to reconcile the two and have a data format that can support both.

## Decision
The difference between legacy and Modernise data formats are as follows


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
* channel - Use doesn't need this at person level, only at top level

New fields for Modernise not used in Use An LPA
--------------------------------------------------
* contactLanguagePreference - we will ignore because instead we use the Use account's preferences
* identityCheck
* signedAt  ** ???  only needed for donor - move to legacy field
* phone - (only Attorney ) not used in Use

Present in Legacy but Missing in Modernise
-----------------------------------------------
* county -  Modernise just uses lines (1-3) instead 
* address -> type "Primary" doesn't apply, in fact all Legacy addresses are primary
* companyName (wthin the person block) now appears to be "name"  within Trust Corporation block
* systemStatus  - boolean  ** ??  person is active or not - Mdoernise has a field to map to this

Renamed fields
-----------------
* Legacy OtherNames field equivalent to Modernise otherNamesKnownBy
* Legacy surnName field equivalent to Modernise lastName
* Legacy dob equivalent to Mdoernise dateOfBirth - format the same (YYYY-MM-DD)
* Legacy addressLineN becomes lineN in Modernise (within address block)
* "applicationHasRestrictions": true, and "applicationHasGuidance": false, in Modernise are covered by hasRestrictionsAndConditions

Other differences
----------------
* Legacy allowes multiple addresses.  But multiples not used in practice, so can just use 1st one for Legacy, use the only one for Modernise
* Legacy has firstName and middleName,  vs Modernise has these merged into 1 as firstNames  -  ?? TODO support BOTH formats, as legacy requires name to be split

Trust Corporations
----------------------
Same changes as for People above. Additionally, companyName is renamed name in Modernise. Modernise appears not to have companyNumber, but we don't use that in Use

Other fields (top level in json):
-------------------------
* uid same but it is within properties block in Modernise (and M on the front, for Modernise)
* Legacy "applicationType": "Classic", doesn't have Modernise equivalent but should not be needed
status same but within properties block in Modernise,  capital Registered or Processing in legacy, lowercase enum registered or processing in Modernise
statusDate only exists in Legacy, should be able to manage without this for Use?
* legacy lpaDonorSignatureDate (YYYY-MM-DD) appears to be replaced with signedAt (2024-01-10T23:00:00Z) for Modernise , IS needed for Use an LPA
* channel (top-level) - may be needed in future by Use to deduce whether IaP is image or text

Renamed fields
-----------------
*  attorneyActDecisions is renamed whenTheLpaCanBeUsed
* "caseAttorneyJointly": true, "caseAttorneyJointlyAndJointlyAndSeverally": false, "caseAttorneyJointlyAndSeverally": false, "caseAttorneySingular": false, are replaced by 1 enum - "howAttorneysMakeDecisions": "jointly"   TODO clarify we will go straight to new enum internally
* "caseSubtype": "hw", is replaced by "lpaType": "personal-welfare"  , "pfa" is replaced by "property-and-affairs"
*  "lifeSustainingTreatment": "Option B", renamed to e:g "lifeSustainingTreatmentOption": "option-a"  - make this an enum internally
*  withdrawnDate renamed withdrawnAt
* "registrationDate": "2024-06-04", renamed registrationAt


new fields in Modernise
-------------------------
"certificateProviderNotRelatedConfirmedAt" -  Not needed for Use

Present in Legacy but Missing in Modernise
----------------------------------------------
* "hasSeveranceWarning": false,   This is yet to be built in Modernise but will appear in notes field. Use will need to show the contents of this field rather than just a standard warning as done for legacy
*  "receiptDate": "2014-09-26", no Modernise equivalwnt but unlikely to be needed by Use an LPA
* "dispatchDate": "2021-10-14", no Modernise equivalwnt but unlikely to be needed by Use an LPA
* "invalidDate": "2024-06-04", no Modernise equivalwnt but unlikely to be needed by Use an LPA
* "rejectedDate": null, no Modernise equivalwnt but unlikely to be needed by Use an LPA
* "lpaIsCleansed": false,  Does not Apply to Modernise
* Legacy "cancellationDate": "2020-03-17", appears not to have an equiv in Modernise - we would need some way of identifying if LPA is cancelled
* "onlineLpaId": "A15527329531"  not needed in Use as we use uid

new fields - jointlyAndSeverally for some and not others


## Consequences
