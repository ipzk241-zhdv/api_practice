<?php

namespace App\Controller;

use App\Service\ServiceSchedule;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CoursesController extends AbstractController
{
    private $ScheduleService;

    public function __construct(ServiceSchedule $scheduleService)
    {
        $this->ScheduleService = $scheduleService;
    }

    // Створення нового курсу
    #[IsGranted("ROLE_ADMIN")]
    #[Route('/api/{facultyShortName}/createCourse', methods: ['POST'])]
    public function createCourse(string $facultyShortName, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (empty($data['name'])) {
            return $this->ScheduleService->jsonResponse(false, 'Course "name" are required', status: 400);
        }

        $entities = $this->ScheduleService->find($facultyShortName, $data['name']);
        if (!$entities instanceof JsonResponse) {
            return $this->ScheduleService->jsonResponse(false, "Course with this name already exist", status: 409);
        }

        $entities = $this->ScheduleService->find($facultyShortName);
        if ($entities instanceof JsonResponse) {
            return $entities;
        }

        $newCourse = [
            'name' => $data['name'],
            'groups' => []
        ];

        return $this->ScheduleService->saveCourse($facultyShortName, $newCourse);
    }

    // Зміна назви курсу
    #[IsGranted("ROLE_ADMIN")]
    #[Route('/api/{facultyShortName}/{oldCourseName}', methods: ['PATCH'])]
    public function updateCourse(string $facultyShortName, string $oldCourseName, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (empty($data['name'])) {
            return $this->ScheduleService->jsonResponse(false, 'New course "name" is required', status: 400);
        }

        $entities = $this->ScheduleService->find($facultyShortName, $oldCourseName);
        if ($entities instanceof JsonResponse) {
            return $entities;
        }
        $course = $entities['course'];

        $entities = $this->ScheduleService->find($facultyShortName, $data['name']);
        if (!$entities instanceof JsonResponse) {
            return $this->ScheduleService->jsonResponse(false, "Course with this name already exist", status: 409);
        }

        $course['name'] = $data['name'];
        return $this->ScheduleService->saveCourse($facultyShortName, $course, $oldCourseName);
    }

    // Видалення курсу
    #[IsGranted("ROLE_ADMIN")]
    #[Route('/api/{facultyShortName}/{courseName}', methods: ['DELETE'])]
    public function deleteCourse(string $facultyShortName, string $courseName): JsonResponse
    {
        $entities = $this->ScheduleService->find($facultyShortName);
        if ($entities instanceof JsonResponse) {
            return $entities;
        }

        $faculty = $entities['faculty'];
        $courseKey = array_search($courseName, array_column($faculty['course'], 'name'));

        if ($courseKey === false) {
            return $this->ScheduleService->jsonResponse(false, "Course not found", status: 404);
        }

        array_splice($faculty['course'], $courseKey, 1);
        return $this->ScheduleService->saveFaculty($faculty);
    }
}
