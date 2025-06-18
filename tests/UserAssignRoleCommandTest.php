<?php

namespace App\Tests\Command;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class UserAssignRoleCommandTest extends KernelTestCase
{
    public function testExecuteAssignsRoleSuccessfully(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:user:assign-role');

        $commandTester = new CommandTester($command);


        $userRepository = static::getContainer()->get(UserRepository::class);

        $testUser = $userRepository->findOneBy(['email' => 'user@example.com']);

        $this->assertNotContains('ROLE_ORGANIZER', $testUser->getRoles());

        $commandTester->execute([
            'email' => 'user@example.com',
            'role'  => 'ROLE_ORGANIZER',
        ]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Role "ROLE_ORGANIZER" has been successfully assigned', $output);

        // static::getContainer()->get('doctrine')->getManager()->refresh($testUser);
        $updatedUser = $userRepository->findOneBy(['email' => 'user@example.com']);
        $this->assertContains('ROLE_ORGANIZER', $updatedUser->getRoles());
    }
}
