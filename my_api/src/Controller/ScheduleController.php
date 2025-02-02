<?php

namespace App\Controller;

use App\Service\ServiceSchedule;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ScheduleController extends AbstractController
{
    private $ScheduleService;

    public function __construct(ServiceSchedule $scheduleService)
    {
        $this->ScheduleService = $scheduleService;
    }

    // Список факультетів
    #[Route('/api/faculties', methods: ['GET'])]
    public function getFacultiesList(): JsonResponse
    {
        return $this->ScheduleService->jsonResponse(
            true,
            "List of faculties retrieved successfully",
            $this->ScheduleService->FacultiesList()
        );
    }

    // Список курсів вказаного факультету
    #[Route('/api/{facultyShortname}/courses', methods: ['GET'])]
    public function getCoursesList(string $facultyShortname): JsonResponse
    {
        $result = $this->ScheduleService->FacultyCoursesList($facultyShortname);
        if ($result instanceof JsonResponse) {
            return $result;
        }

        return $this->ScheduleService->jsonResponse(true, "List of courses retrieved successfully", $result);
    }

    // Список груп вказаного курсу і факультету
    #[Route('/api/{facultyShortname}/{courseName}/groups', methods: ['GET'])]
    public function getGroupsList(string $facultyShortname, string $courseName): JsonResponse
    {
        $result = $this->ScheduleService->CourseGroupsList($facultyShortname, $courseName);
        if ($result instanceof JsonResponse) {
            return $result;
        }

        return $this->ScheduleService->jsonResponse(true, "List of courses retrieved successfully", $result);
    }
}
