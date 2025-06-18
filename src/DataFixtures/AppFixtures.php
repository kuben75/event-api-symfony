<?php

namespace App\DataFixtures;

use App\Entity\Event;
use App\Entity\User;
use App\Entity\Registration;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\EventSetting;
class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, 'password123')
        );
        $manager->persist($user);

        $organizer = new User();
        $organizer->setEmail('organizer@example.com');
        $organizer->setRoles(['ROLE_ORGANIZER']);
        $organizer->setPassword(
            $this->passwordHasher->hashPassword($organizer, 'organizerpass')
        );
        $manager->persist($organizer);

        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, 'adminpassword123')
        );
        $manager->persist($admin);


        $event1 = new Event();
        $event1->setTitle('Konferencja Symfony 2025');
        $event1->setDescription('Największa konferencja poświęcona frameworkowi Symfony w Polsce.');
        $event1->setStartDate(new \DateTime('2025-10-20 09:00:00'));
        $event1->setCapacity(500);
        $event1->setOrganizer($admin);
        $manager->persist($event1);

        $event2 = new Event();
        $event2->setTitle('Warsztaty z Doctrine ORM');
        $event2->setDescription('Intensywne, jednodniowe warsztaty z zaawansowanych technik Doctrine.');
        $event2->setStartDate(new \DateTime('2025-11-15 10:00:00'));
        $event2->setCapacity(30);
        $event2->setOrganizer($organizer);
        $manager->persist($event2);

        $event3 = new Event();
        $event3->setTitle('Test Event (past)');
        $event3->setDescription('Wydarzenie, które już się odbyło.');
        $event3->setStartDate(new \DateTime('2024-01-01 10:00:00'));
        $event3->setCapacity(100);
        $event3->setOrganizer($admin);
        $manager->persist($event3);

        $setting1_event1 = new EventSetting();
        $setting1_event1->setSettingKey('dress_code');
        $setting1_event1->setSettingValue('Business Casual');
        $setting1_event1->setEvent($event1);
        $manager->persist($setting1_event1);

        $setting2_event1 = new EventSetting();
        $setting2_event1->setSettingKey('catering_info');
        $setting2_event1->setSettingValue('Dostępny lunch i przerwy kawowe.');
        $setting2_event1->setEvent($event1);
        $manager->persist($setting2_event1);


        $setting1_event2 = new EventSetting();
        $setting1_event2->setSettingKey('stream_url');
        $setting1_event2->setSettingValue('https://youtube.com/live/stream');
        $setting1_event2->setEvent($event2);
        $manager->persist($setting1_event2);

        $registration1 = new Registration();
        $registration1->setAttendee($user);
        $registration1->setEvent($event1);
        $manager->persist($registration1);

        $registration2 = new Registration();
        $registration2->setAttendee($user);
        $registration2->setEvent($event2);
        $manager->persist($registration2);

        $registration3 = new Registration();
        $registration3->setAttendee($organizer);
        $registration3->setEvent($event1);
        $manager->persist($registration3);
        $manager->flush();
    }
}
