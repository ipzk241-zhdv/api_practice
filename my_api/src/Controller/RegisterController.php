<?php

namespace App\Controller;

use App\Entity\Users;
use App\Service\ServiceSchedule;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface; // Используем интерфейс

class RegisterController extends AbstractController
{
    private $entityManager;
    private $ScheduleService;

    public function __construct(EntityManagerInterface $entityManager, ServiceSchedule $scheduleService)
    {
        $this->entityManager = $entityManager;
        $this->ScheduleService = $scheduleService;
    }

    // Реєстрація, jwt + sql server
    #[Route('/api/register', methods: ['POST'])]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);        
        if (!isset($data['username'], $data['password'])) {
            return $this->ScheduleService->jsonResponse(false, "Invalid credentials", status: 400);
        }

        $login = $data['username'];
        $password = $data['password'];

        $loginAlreadyInUse = (bool) $this->entityManager->getRepository(Users::class)->findOneBy(['login' => $login]);
        if ($loginAlreadyInUse) {
            return $this->ScheduleService->jsonResponse(false, "Login already in use", status: 409);
        }

        $user = new Users();
        $user->setLogin($login);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $entityManager->persist($user);
        $entityManager->flush();

        $token = $JWTManager->create($user);

        return $this->ScheduleService->jsonResponse(true, "User registered successfully", ['token' => $token]);
    }
}
