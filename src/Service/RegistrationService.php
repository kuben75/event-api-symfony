<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\Registration;
use App\Entity\User;
use App\Repository\RegistrationRepository;
use Doctrine\ORM\EntityManagerInterface;

class RegistrationService
{
    private EntityManagerInterface $entityManager;
    private RegistrationRepository $registrationRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        RegistrationRepository $registrationRepository
    ) {
        $this->entityManager = $entityManager;
        $this->registrationRepository = $registrationRepository;
    }

    public function registerUserForEvent(User $user, Event $event): Registration
    {
        $existingRegistration = $this->registrationRepository->findOneBy(['attendee' => $user, 'event' => $event]);
        if ($existingRegistration) {
            throw new \RuntimeException('User is already registered for this event.', 409);
        }

        $totalRegistrations = $this->registrationRepository->count(['event' => $event]);
        if ($totalRegistrations >= $event->getCapacity()) {
            throw new \RuntimeException('No available spots for this event.', 409);
        }

        $registration = new Registration();
        $registration->setAttendee($user);
        $registration->setEvent($event);

        $this->entityManager->persist($registration);
        $this->entityManager->flush();

        return $registration;
    }
}
