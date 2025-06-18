<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventSetting;
use App\Formatter\ApiResponseFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EventSettingController extends AbstractController
{
    private ApiResponseFormatter $apiFormatter;
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;

    public function __construct(ApiResponseFormatter $apiFormatter, EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
        $this->apiFormatter = $apiFormatter;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    #[Route('/api/events/{id<\d+>}/settings', name: 'api_event_settings_create', methods: ['POST'])]
    #[IsGranted('ROLE_ORGANIZER')]
    public function create(Request $request, Event $event): JsonResponse
    {
        if ($this->getUser() !== $event->getOrganizer() && !$this->isGranted('ROLE_ADMIN')) {
            return $this->apiFormatter->addError('You are not the organizer of this event.')->withStatusCode(Response::HTTP_FORBIDDEN)->createResponse();
        }

        $data = $request->toArray();
        $setting = new EventSetting();
        $setting->setEvent($event);
        $setting->setSettingKey($data['settingKey'] ?? '');
        $setting->setSettingValue($data['settingValue'] ?? '');

        $errors = $this->validator->validate($setting);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) { $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage(); }
            return $this->apiFormatter->withErrors($errorMessages)->withStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)->createResponse();
        }

        $this->entityManager->persist($setting);
        $this->entityManager->flush();

        $responseData = [
            'id' => $setting->getId(),
            'settingKey' => $setting->getSettingKey(),
            'settingValue' => $setting->getSettingValue(),
            'message' => 'Setting added successfully.'
        ];

        return $this->apiFormatter->withData($responseData)->withStatusCode(Response::HTTP_CREATED)->createResponse();
    }

    #[Route('/api/events/{id<\d+>}/settings', name: 'api_event_settings_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(Event $event): JsonResponse
    {
        $settings = $event->getSettings();
        $formattedData = [];
        foreach ($settings as $setting) {
            $formattedData[$setting->getSettingKey()] = $setting->getSettingValue();
        }
        return $this->apiFormatter->withData($formattedData)->createResponse();
    }

    #[Route('/api/settings/{id<\d+>}', name: 'api_event_setting_update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_ORGANIZER')]
    public function update(Request $request, EventSetting $setting): JsonResponse
    {
        if ($this->getUser() !== $setting->getEvent()->getOrganizer() && !$this->isGranted('ROLE_ADMIN')) {
            return $this->apiFormatter->addError('You are not allowed to edit this setting.')->withStatusCode(Response::HTTP_FORBIDDEN)->createResponse();
        }

        $data = $request->toArray();
        $setting->setSettingValue($data['settingValue'] ?? $setting->getSettingValue());

        $this->entityManager->flush();

        $responseData = ['id' => $setting->getId(), 'settingKey' => $setting->getSettingKey(), 'settingValue' => $setting->getSettingValue()];
        return $this->apiFormatter->withData($responseData)->addMessage('Setting updated successfully.')->createResponse();
    }

    #[Route('/api/settings/{id<\d+>}', name: 'api_event_setting_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ORGANIZER')]
    public function delete(EventSetting $setting): JsonResponse
    {
        if ($this->getUser() !== $setting->getEvent()->getOrganizer() && !$this->isGranted('ROLE_ADMIN')) {
            return $this->apiFormatter->addError('You are not allowed to delete this setting.')->withStatusCode(Response::HTTP_FORBIDDEN)->createResponse();
        }

        $this->entityManager->remove($setting);
        $this->entityManager->flush();

        return $this->apiFormatter->withStatusCode(Response::HTTP_NO_CONTENT)->createResponse();
    }
}
