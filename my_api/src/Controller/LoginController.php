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
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class LoginController extends AbstractController
{
    private $entityManager;
    private $ScheduleService;

    public function __construct(EntityManagerInterface $entityManager, ServiceSchedule $scheduleService)
    {
        $this->entityManager = $entityManager;
        $this->ScheduleService = $scheduleService;
    }

    // Авторизація, jwt + sql server
    #[Route('/api/login', methods: ['POST'])]
    public function login(Request $request, UserPasswordHasherInterface $passwordHasher, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['username'], $data['password'])) {
            return $this->ScheduleService->jsonResponse(false, "Invalid credentials", status: 400);
        }

        $login = $data['username'];
        $password = $data['password'];

        $user = $this->entityManager->getRepository(Users::class)->findOneBy(['login' => $login]);
        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return $this->ScheduleService->jsonResponse(false, "Invalid credentials", status: 401);
        }

        $token = $JWTManager->create($user);
        return $this->ScheduleService->jsonResponse(true, "Authorized successfully", ['token' => $token]);
    }
}
