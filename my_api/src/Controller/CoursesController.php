<?php

namespace App\Controller;

use App\Service\ServiceSchedule;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CoursesController extends AbstractController
{
    private $ScheduleService;

    public function __construct(ServiceSchedule $scheduleService)
    {
        $this->ScheduleService = $scheduleService;
    }

    // Метод для создания нового курса
    #[Route('/api/{facultyShortName}/createCourse', methods: ['POST'])]
    public function createCourse(string $facultyShortName, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (empty($data['name'])) {
            return $this->ScheduleService->jsonResponse(false, 'Course "name" are required', status: 400);
        }

        $course = $this->ScheduleService->find($facultyShortName, $data['name']);
        if (!$course instanceof JsonResponse) {
            return $this->ScheduleService->jsonResponse(false, "Course with this name already exist", status: 400);
        }

        $newCourse = [
            'name' => $data['name'],
            'groups' => []
        ];

        $faculty = $this->ScheduleService->find($facultyShortName);
        if ($faculty instanceof JsonResponse) {
            return $faculty;
        }

        $faculty['faculty']['course'][] = $newCourse;

        return $this->ScheduleService->saveFaculty($faculty['faculty']);
    }

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

        $entities['course']['name'] = $data['name'];

        return $this->ScheduleService->saveCourse($facultyShortName, $entities['course']);
    }

    // Метод для удаления курса и его вмісту
    #[Route('/api/{facultyShortName}/{courseName}', methods: ['DELETE'])]
    public function deleteCourse(string $facultyShortName, string $courseName): JsonResponse
    {
        $faculty = $this->ScheduleService->find($facultyShortName);

        $courseKey = array_search($courseName, array_column($faculty['faculty']['course'], 'name'));

        if ($courseKey === false) {
            return $this->ScheduleService->jsonResponse(false, "Couse not found", status: 404);
        }

        array_splice($faculty['faculty']['course'], $courseKey, 1);

        return $this->ScheduleService->saveFaculty($faculty['faculty']);
    }
}
