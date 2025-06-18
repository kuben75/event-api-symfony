<?php

namespace App\Controller;

use App\Entity\Event;
use App\Formatter\ApiResponseFormatter;
use App\Service\RegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class RegistrationController extends AbstractController
{
    private RegistrationService $registrationService;
    private ApiResponseFormatter $apiFormatter;

    public function __construct(RegistrationService $registrationService, ApiResponseFormatter $apiFormatter)
    {
        $this->registrationService = $registrationService;
        $this->apiFormatter = $apiFormatter;
    }

    #[Route('/api/events/{id<\d+>}/register', name: 'api_event_register', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function register(Event $event): JsonResponse
    {
        try {
            $registration = $this->registrationService->registerUserForEvent($this->getUser(), $event);
        } catch (\Exception $e) {
            return $this->apiFormatter
                ->addError($e->getMessage())
                ->withStatusCode($e->getCode() > 0 ? $e->getCode() : Response::HTTP_BAD_REQUEST)
                ->createResponse();
        }

        $responseData = [
            'registration_id' => $registration->getId(),
            'event_title' => $event->getTitle(),
            'message' => 'Successfully registered for the event.'
        ];

        return $this->apiFormatter->withData($responseData)->withStatusCode(Response::HTTP_CREATED)->createResponse();
    }
}
