<?php

namespace App\Controller;

use App\Entity\User;
use App\Formatter\ApiResponseFormatter;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class NewUserRegistrationController extends AbstractController
{
    private ApiResponseFormatter $apiFormatter;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private ValidatorInterface $validator;

    public function __construct(
        ApiResponseFormatter $apiFormatter,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator
    ) {
        $this->apiFormatter = $apiFormatter;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->validator = $validator;
    }

    #[Route('/api/register', name: 'api_user_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = $request->toArray();
        $email = $data['email'] ?? null;
        $plainPassword = $data['password'] ?? null;

        if (empty($email) || empty($plainPassword)) {
            return $this->apiFormatter->addError('Email and password are required.')->withStatusCode(Response::HTTP_BAD_REQUEST)->createResponse();
        }

        if ($this->userRepository->findOneBy(['email' => $email])) {
            return $this->apiFormatter->addError('User with this email already exists.')->withStatusCode(Response::HTTP_CONFLICT)->createResponse();
        }

        $user = new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            return $this->apiFormatter->withErrors($errorMessages)->withStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)->createResponse();
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $responseData = ['id' => $user->getId(), 'email' => $user->getEmail(), 'roles' => $user->getRoles()];
        return $this->apiFormatter->withData($responseData)->addMessage('User created successfully.')->withStatusCode(Response::HTTP_CREATED)->createResponse();
    }
}
