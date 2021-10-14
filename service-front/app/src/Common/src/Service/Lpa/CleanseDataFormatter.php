<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\Service\Features\FeatureEnabled;
use Psr\Log\LoggerInterface;

class CleanseDataFormatter
{
    public function __construct()
    {
    }

    /**
     *
     * @return string
     */
    public function __invoke($data): string
    {
        // Full or part match "Part Match - cleanse and send activation key"
        $fullMatch = array_key_exists('actor-id', $data);

        $additionalInformation = ($fullMatch ?
                'Full Match (' . $data['actor-id'] . ')' :
                'Part Match ') . "- cleanse and send activation key \n";
        // Requester Name "Requester: Albert Test"
        $additionalInformation =
            $additionalInformation . 'Requestor: ' . $data['first_names'] . ' ' . $data['last_name'] . "\n";
        //If Part Match
        if (!$fullMatch) {
            $additionalInformation =
                $additionalInformation .
                $data['dob']['day'] . '/' . $data['dob']['month'] . '/' . $data['dob']['year'] . "\n";

            $additionalInformation = $additionalInformation . $data['postcode'] . "\n";
        }

        $additionalInformation = $additionalInformation . ($data['telephone'] ?? 'Phone number not provided') . "\n";

        $additionalInformation = $additionalInformation . $data['email'] . "\n";

        if (strtolower($data['actor_role']) === 'attorney') {
            $additionalInformation =
                $additionalInformation . 'Donor : ' . $data['donor_first_names'] . ' ' . $data['donor_last_name'];

            $additionalInformation = $additionalInformation . ', ' . $data['donor_dob']->format('d/m/Y');
        }

        return $additionalInformation;
    }
}
