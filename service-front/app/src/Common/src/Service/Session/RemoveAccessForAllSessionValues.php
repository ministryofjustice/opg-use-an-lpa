<?php

namespace Common\Service\Session;

use Mezzio\Session\SessionInterface;

class RemoveAccessForAllSessionValues
{
    /**
     * Cleans the session values removing any post check answers session values, also removes LPA reference number
     * @param SessionInterface $session the users session
     */
    public function cleanAccessForAllSessionValues(SessionInterface $session): void
    {
        $session->unset('opg_reference_number');
        $this->removePostLPAMatchSessionValues($session);
    }

    public function removePostLPAMatchSessionValues(SessionInterface $session): void
    {
        $session->unset('actor_id');
        $session->unset('actor_role');
        $session->unset('donor_first_names');
        $session->unset('donor_last_name');
        $session->unset('donor_dob');
        $session->unset('telephone_option');
        $session->unset('lpa_full_match_but_not_cleansed');
    }
}
