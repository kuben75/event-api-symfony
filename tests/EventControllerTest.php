<?php

namespace App\Tests;

use App\Repository\EventRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class EventControllerTest extends WebTestCase
{
    public function testIndexReturns401WhenNotAuthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/events');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testIndexReturnsUpcomingEventsForAuthenticatedUser(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneByEmail('user@example.com');
        $client->loginUser($testUser);

        $client->request('GET', '/api/events');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/json');

        $responseContent = $client->getResponse()->getContent();
        $responseData = json_decode($responseContent, true);

        $this->assertArrayHasKey('data', $responseData);

        $this->assertCount(2, $responseData['data']);

        $titles = array_column($responseData['data'], 'title');
        $this->assertContains('Konferencja Symfony 2025', $titles);
        $this->assertNotContains('Test Event (past)', $titles);
    }

    /**
     * @throws \JsonException
     */
    public function testCreateEventSuccessForOrganizer(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $organizer = $userRepository->findOneByEmail('organizer@example.com');
        $client->loginUser($organizer);

        $client->request('POST', '/api/events', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => 'Nowe wydarzenie od organizatora',
                'description' => 'Testowe wydarzenie z testu funkcjonalnego.',
                'startDate' => '2026-01-15 10:00:00',
                'capacity' => 50,
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertJson($client->getResponse()->getContent());
        $this->assertStringContainsString('Event created successfully!', $client->getResponse()->getContent());
    }

    public function testCreateEventForbiddenForRegularUser(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $regularUser = $userRepository->findOneByEmail('user@example.com');
        $client->loginUser($regularUser);

        $client->request('POST', '/api/events', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['title' => 'Próba stworzenia wydarzenia'], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUpdateEventSuccessAsOwner(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $eventRepository = static::getContainer()->get(EventRepository::class);

        $organizer = $userRepository->findOneByEmail('organizer@example.com');
        $client->loginUser($organizer);

        $event = $eventRepository->findOneBy(['title' => 'Warsztaty z Doctrine ORM']);
        $this->assertNotNull($event, 'Test event not found in fixtures.');

        $newTitle = 'Zaktualizowany tytuł warsztatów';

        $client->request(
            'PUT',
            '/api/events/' . $event->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['title' => $newTitle], JSON_THROW_ON_ERROR)
        );

        self::assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('title', $responseData['data']);
        $this->assertEquals($newTitle, $responseData['data']['title']);
    }

    /**
     * @throws \JsonException
     */
    public function testUpdateEventForbiddenForNonOwner(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $eventRepository = static::getContainer()->get(EventRepository::class);

        $organizer = $userRepository->findOneByEmail('organizer@example.com');
        $client->loginUser($organizer);

        $adminEvent = $eventRepository->findOneBy(['title' => 'Konferencja Symfony 2025']);
        $this->assertNotNull($adminEvent, 'Admin event not found in fixtures.');

        $client->request('PUT', '/api/events/' . $adminEvent->getId(), [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['title' => 'Próba hackowania tytułu'], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testDeleteEventSuccessAsAdmin(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $eventRepository = static::getContainer()->get(EventRepository::class);

        $admin = $userRepository->findOneByEmail('admin@example.com');
        $client->loginUser($admin);


        $organizer = $userRepository->findOneByEmail('organizer@example.com');
        $eventsOfOrganizer = $eventRepository->findBy(['organizer' => $organizer]);
        $eventToDelete = $eventsOfOrganizer[0] ?? null;

        $this->assertNotNull($eventToDelete, 'Event to delete not found in fixtures.');
        $eventId = $eventToDelete->getId();

        $client->request('DELETE', '/api/events/' . $eventId);

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);


        static::getContainer()->get('doctrine')->getManager()->clear();
        $deletedEvent = $eventRepository->find($eventId);
        $this->assertNull($deletedEvent);
    }
}
