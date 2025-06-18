<?php

namespace App\Controller;

use App\Entity\Event;
use App\Formatter\ApiResponseFormatter;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/events')]
class EventController extends AbstractController
{
    private EventRepository $eventRepository;
    private ApiResponseFormatter $apiFormatter;
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;

    public function __construct(
        EventRepository $eventRepository,
        ApiResponseFormatter $apiFormatter,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ) {
        $this->eventRepository = $eventRepository;
        $this->apiFormatter = $apiFormatter;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    #[Route('', name: 'api_event_index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(): JsonResponse
    {
        $events = $this->eventRepository->findUpcomingEvents();

        $data = [];
        foreach ($events as $event) {
            $organizer = $event->getOrganizer();
            $data[] = [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'description' => $event->getDescription(),
                'startDate' => $event->getStartDate()->format('Y-m-d H:i:s'),
                'endDate' => $event->getEndDate()?->format('Y-m-d H:i:s'),
                'location' => $event->getLocation(),
                'capacity' => $event->getCapacity(),
                'organizer' => [
                    'id' => $organizer->getId(),
                    'email' => $organizer->getEmail(),
                ],
            ];
        }

        return $this->apiFormatter->withData($data)->createResponse();
    }

    #[Route('/{id<\d+>}', name: 'api_event_show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(Event $event): JsonResponse
    {
        $organizer = $event->getOrganizer();

        $data = [
            'id' => $event->getId(),
            'title' => $event->getTitle(),
            'description' => $event->getDescription(),
            'startDate' => $event->getStartDate()->format('Y-m-d H:i:s'),
            'endDate' => $event->getEndDate()?->format('Y-m-d H:i:s'),
            'location' => $event->getLocation(),
            'capacity' => $event->getCapacity(),
            'organizer' => [
                'id' => $organizer->getId(),
                'email' => $organizer->getEmail(),
            ],
        ];

        return $this->apiFormatter->withData($data)->createResponse();
    }

    #[Route('', name: 'api_event_create', methods: ['POST'])]
    #[IsGranted('ROLE_ORGANIZER')]
    public function create(Request $request): JsonResponse
    {
        $data = $request->toArray();

        $event = new Event();
        $event->setTitle($data['title'] ?? null);
        $event->setDescription($data['description'] ?? null);
        $event->setLocation($data['location'] ?? null);
        $event->setCapacity($data['capacity'] ?? null);

        if (!empty($data['startDate'])) {
            try {
                $event->setStartDate(new \DateTime($data['startDate']));
            } catch (\Exception $e) {
                return $this->apiFormatter->addError('Invalid startDate format.')->withStatusCode(Response::HTTP_BAD_REQUEST)->createResponse();
            }
        }
        if (!empty($data['endDate'])) {
            try {
                $event->setEndDate(new \DateTime($data['endDate']));
            } catch (\Exception $e) {
                return $this->apiFormatter->addError('Invalid endDate format.')->withStatusCode(Response::HTTP_BAD_REQUEST)->createResponse();
            }
        }

        $event->setOrganizer($this->getUser());

        $errors = $this->validator->validate($event);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            return $this->apiFormatter->withErrors($errorMessages)->withStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)->createResponse();
        }

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        $responseData = [
            'id' => $event->getId(),
            'title' => $event->getTitle(),
            'message' => 'Event created successfully!'
        ];

        return $this->apiFormatter->withData($responseData)->withStatusCode(Response::HTTP_CREATED)->createResponse();
    }
    #[Route('/{id<\d+>}', name: 'api_event_update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_ORGANIZER')]
    public function update(Request $request, Event $eventToUpdate): JsonResponse
    {
        if ($this->getUser() !== $eventToUpdate->getOrganizer() && !$this->isGranted('ROLE_ADMIN')) {
            return $this->apiFormatter->addError('You are not the organizer of this event.')->withStatusCode(Response::HTTP_FORBIDDEN)->createResponse();
        }

        $data = $request->toArray();

        $eventToUpdate->setTitle($data['title'] ?? $eventToUpdate->getTitle());
        $eventToUpdate->setDescription($data['description'] ?? $eventToUpdate->getDescription());
        $eventToUpdate->setLocation($data['location'] ?? $eventToUpdate->getLocation());
        $eventToUpdate->setCapacity($data['capacity'] ?? $eventToUpdate->getCapacity());

        $this->entityManager->flush();

        $organizer = $eventToUpdate->getOrganizer();
        $responseData = [
            'id' => $eventToUpdate->getId(),
            'title' => $eventToUpdate->getTitle(),
            'description' => $eventToUpdate->getDescription(),
            'startDate' => $eventToUpdate->getStartDate()->format('Y-m-d H:i:s'),
            'endDate' => $eventToUpdate->getEndDate()?->format('Y-m-d H:i:s'),
            'location' => $eventToUpdate->getLocation(),
            'capacity' => $eventToUpdate->getCapacity(),
            'organizer' => [
                'id' => $organizer->getId(),
                'email' => $organizer->getEmail(),
            ],
        ];

        return $this->apiFormatter->withData($responseData)->addMessage('Event updated successfully.')->createResponse();
    }

    #[Route('/{id<\d+>}', name: 'api_event_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ORGANIZER')]
    public function delete(Event $eventToDelete): JsonResponse
    {
        if ($this->getUser() !== $eventToDelete->getOrganizer() && !$this->isGranted('ROLE_ADMIN')) {
            return $this->apiFormatter->addError('You are not the organizer of this event.')->withStatusCode(Response::HTTP_FORBIDDEN)->createResponse();
        }

        $this->entityManager->remove($eventToDelete);
        $this->entityManager->flush();

        return $this->apiFormatter->withStatusCode(Response::HTTP_NO_CONTENT)->createResponse();
    }
    #[Route('/{id<\d+>}/settings', name: 'api_event_settings_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function showSettings(Event $event): JsonResponse
    {
        $settings = $event->getSettings();

        $formattedData = [];
        foreach ($settings as $setting) {
            $formattedData[$setting->getSettingKey()] = $setting->getSettingValue();
        }

        return $this->apiFormatter->withData($formattedData)->createResponse();
    }
}
