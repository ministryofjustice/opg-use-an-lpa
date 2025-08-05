<?php

declare(strict_types=1);

namespace Common\Service\User;

use Common\Entity\User;
use Common\Exception\ApiException;
use Common\Service\ApiClient\Client as ApiClient;
use Common\Service\Log\EventCodes;
use Common\Service\Log\Output\Email;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Mezzio\Authentication\UserInterface;
use Mezzio\Authentication\UserRepositoryInterface;
use ParagonIE\HiddenString\HiddenString;
use Psr\Log\LoggerInterface;
use RuntimeException;

class UserService
{
    public function __construct(private ApiClient $apiClient, private LoggerInterface $logger)
    {
    }

    public function deleteAccount(string $accountId): void
    {
        try {
            $user = $this->apiClient->httpDelete('/v1/delete-account/' . $accountId);

            $this->logger->notice(
                'Successfully deleted account with id {id} and email hash {email}',
                [
                    'event_code' => EventCodes::ACCOUNT_DELETED,
                    'id'         => $accountId,
                    'email'      => new Email($user['Email']),
                ]
            );
        } catch (ApiException $ex) {
            $this->logger->notice(
                'Failed to delete account for userId {userId} - status code {code}',
                [
                    'userId' => $accountId,
                    'code'   => $ex->getCode(),
                ]
            );

            throw $ex;
        }
    }
}
