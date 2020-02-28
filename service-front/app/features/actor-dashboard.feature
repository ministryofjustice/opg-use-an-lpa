@actor @dashboard
Feature: The user is able to see correct information on their dashboard
  As a user
  I want to be able to see any LPA's I have added on my dashboard
  So that I can see their details and perform actions on them

  Background:
    Given I am a user of the lpa application
    And I am currently signed in
    And I have added an LPA to my account

  @ui @integration
  Scenario Outline: As a user I can see the number of active access codes an LPA has
    Given I have 2 active codes for one of my LPAs
    When I am on the dashboard page
    Then I can see that my LPA has 2 active <code1> <code2>

    Examples:
    | code1 | code2
    |  'SiriusUid' => $this->lpa->uId, 'Added' => '2020-01-01T23:59:59+00:00', 'Organisation' => $this->organisation, 'UserLpaActor' => $this->userLpaActorToken, 'ViewerCode' => $this->accessCode, 'Expires' => '2021-01-05T23:59:59+00:00', 'Viewed' => false, 'ActorId' => $this->actorId | 'SiriusUid' => $this->lpa->uId, 'Added' => '2020-01-01T23:59:59+00:00', 'Organisation' => $this->organisation, 'UserLpaActor' => $this->userLpaActorToken, 'ViewerCode' => $this->accessCode, 'Expires' => '2021-01-05T23:59:59+00:00', 'Viewed' => false, 'ActorId' => $this->actorId |
    |  'SiriusUid' => $this->lpa->uId, 'Added' => '2020-01-01T23:59:59+00:00', 'Organisation' => $this->organisation, 'UserLpaActor' => $this->userLpaActorToken, 'ViewerCode' => $this->accessCode, 'Expires' => '2020-01-01T23:59:59+00:00', 'Viewed' => false, 'ActorId' => $this->actorId | 'SiriusUid' => $this->lpa->uId, 'Added' => '2020-01-01T23:59:59+00:00', 'Organisation' => $this->organisation, 'UserLpaActor' => $this->userLpaActorToken, 'ViewerCode' => $this->accessCode, 'Expires' => '2021-01-05T23:59:59+00:00', 'Viewed' => false, 'ActorId' => $this->actorId |
    |  'SiriusUid' => $this->lpa->uId, 'Added' => '2020-01-01T23:59:59+00:00', 'Organisation' => $this->organisation, 'UserLpaActor' => $this->userLpaActorToken, 'ViewerCode' => $this->accessCode, 'Expires' => '2020-01-05T23:59:59+00:00', 'Viewed' => false, 'ActorId' => $this->actorId | 'SiriusUid' => $this->lpa->uId, 'Added' => '2020-01-01T23:59:59+00:00', 'Organisation' => $this->organisation, 'UserLpaActor' => $this->userLpaActorToken, 'ViewerCode' => $this->accessCode, 'Expires' => '2020-01-05T23:59:59+00:00', 'Viewed' => false, 'ActorId' => $this->actorId |

#  @ui @integration
#  Scenario: As a user I can see the number of active access codes an LPA has
#    Given I have an active code and an inactive code for one of my LPAs
#    When I am on the dashboard page
#    Then I can see that my LPA has 1 active code