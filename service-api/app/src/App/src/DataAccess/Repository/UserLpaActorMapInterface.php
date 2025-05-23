<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

use Common\Form\Fieldset\Date;
use DateInterval;

/**
 * Represents access to mappings between a User Account, an LPA, and the associated Actor on the LPA.
 *
 * @psalm-type UserLpaActorMap = array{
 *     Id: string,
 *     UserId: string,
 *     SiriusUid?: string,
 *     LpaUid?: string,
 *     Added: string,
 *     ActorId?: int,
 *     ActivationCode?: string,
 *     ActivateBy?: int,
 *     DueBy?: string,
 *     ActivatedOn?: string,
 * }
 */
interface UserLpaActorMapInterface
{
    /**
     * Creates a new mapping in the DB
     *
     * @param string            $userId          The UserID of the actors account
     * @param string            $siriusUid       The Sirius formatted UID that is associated with an LPA in the system
     * @param string|null       $actorId         The Sirius formatted UID that is associated with an actor in the system
     * @param DateInterval|null $expiryInterval  The interval of when this record should expire.
     *                                           If null the record will not expire
     * @param DateInterval|null $intervalTillDue The interval specifying the expected delivery date of correspondence
     *                                           about the LPA
     * @param string|null       $code            The Activation code which has been used to add the LPA to the users
     *                                           account
     * @return string The lpaActorToken of the newly created mapping
     */
    public function create(
        string $userId,
        string $siriusUid,
        ?string $actorId,
        ?DateInterval $expiryInterval = null,
        ?DateInterval $intervalTillDue = null,
        ?string $code = null,
    ): string;

    /**
     * Returns the LPA relation record for the given token.
     *
     * @param string $lpaActorToken
     * @psalm-return UserLpaActorMap|null
     * @return ?array
     */
    public function get(string $lpaActorToken): ?array;

    /**
     * Returns LPA relation records for the given user_id.
     *
     * @param string $userId
     * @psalm-return UserLpaActorMap[]|null
     * @return ?array
     */
    public function getByUserId(string $userId): ?array;

    /**
     * Deletes a LPA relation. Should only be called if a rollback is needed.
     *
     * @param string $lpaActorToken
     * @return array The record that was deleted
     */
    public function delete(string $lpaActorToken): array;

    /**
     * Activates a LPA relation record, enabling it for use by the user
     *
     * @param string $lpaActorToken
     * @param string $actorId
     * @param string $activationCode
     * @return array The record that was activated
     */
    public function activateRecord(string $lpaActorToken, string $actorId, string $activationCode): array;

    /**
     * Renews the LPA relation records activation period and due by date using the supplied intervals.
     * Optionally allows the actors Sirius UID to be changed.
     *
     * @see https://www.php.net/manual/en/dateinterval.construct.php#refsect1-dateinterval.construct-parameters
     *
     * @param string       $lpaActorToken
     * @param DateInterval $expiryInterval  The interval of when this record should expire
     * @param DateInterval $intervalTillDue The interval of when an action will be due on the LPA
     * @param string|null  $actorId         The actor related to the record if users details have matched
     *
     * @return array The record that was renewed
     */
    public function updateRecord(
        string $lpaActorToken,
        DateInterval $expiryInterval,
        DateInterval $intervalTillDue,
        ?string $actorId,
    ): array;
}
