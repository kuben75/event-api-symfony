<?php

namespace App\Controller;

use App\Entity\User;
use App\Formatter\ApiResponseFormatter;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
class UserController extends AbstractController
{
    private ApiResponseFormatter $apiFormatter;
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository, ApiResponseFormatter $apiFormatter)
    {
        $this->userRepository = $userRepository;
        $this->apiFormatter = $apiFormatter;
    }

    #[Route('/me', name: 'api_current_user_profile', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function showCurrentUserProfile(): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $userData = [
            'id' => $currentUser->getId(),
            'email' => $currentUser->getEmail(),
            'roles' => $currentUser->getRoles(),
        ];

        return $this->apiFormatter->withData($userData)->createResponse();
    }

    #[Route('/users', name: 'api_user_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(): JsonResponse
    {
        $users = $this->userRepository->findAll();

        $data = [];
        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ];
        }

        return $this->apiFormatter->withData($data)->createResponse();
    }
}
