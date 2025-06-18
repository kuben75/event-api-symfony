<?php

namespace App\Tests;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class UserRevokeRoleCommandTest extends KernelTestCase
{
    public function testExecuteRevokesRoleSuccessfully(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:user:revoke-role');
        $commandTester = new CommandTester($command);

        $userRepository = static::getContainer()->get(UserRepository::class);

        $testUser = $userRepository->findOneBy(['email' => 'organizer@example.com']);

        $this->assertContains('ROLE_ORGANIZER', $testUser->getRoles());

        $commandTester->execute([
            'email' => 'organizer@example.com',
            'role'  => 'ROLE_ORGANIZER',
        ]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Role "ROLE_ORGANIZER" has been successfully revoked', $output);

        $updatedUser = $userRepository->findOneBy(['email' => 'organizer@example.com']);
        $this->assertNotContains('ROLE_ORGANIZER', $updatedUser->getRoles());
    }
}
