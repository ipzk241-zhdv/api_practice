<?php

namespace App\Controller;

use App\Service\ServiceSchedule;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class FacultyController extends AbstractController
{
    private $ScheduleService;

    public function __construct(ServiceSchedule $scheduleService)
    {
        $this->ScheduleService = $scheduleService;
    }

    // Метод для создания нового факультета
    #[Route('/api/faculty/create', methods: ['POST'])]
    public function createFaculty(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (empty($data['name']) || empty($data['shortname'])) {
            return $this->ScheduleService->jsonResponse(false, 'Both "name" and "shortname" are required', status: 400);
        }

        $faculty = $this->ScheduleService->find($data['shortname']);
        if (!$faculty instanceof JsonResponse) {
            return $this->ScheduleService->jsonResponse(false, 'Faculty with this "shortname" already exist', status: 400);
        }

        $newFaculty = [
            'name' => $data['name'],
            'shortname' => $data['shortname'],
            'course' => []
        ];

        return $this->ScheduleService->saveFaculty($newFaculty);
    }
    
    // Метод для оновлення факультету
    #[Route('/api/{oldFacultyShortName}', methods: ['PATCH'])]
    public function updateFaculty(string $oldFacultyShortName, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name']) && !isset($data['shortname'])) {
            return $this->ScheduleService->jsonResponse(false, 'At least one of "name" or "shortname" fields are required', status: 400);
        }

        $faculty = $this->ScheduleService->find($oldFacultyShortName);
        if ($faculty instanceof JsonResponse) {
            return $faculty;
        }

        if (isset($data['name'])) {
            $faculty['faculty']['name'] = $data['name'];
        }
        if (isset($data['shortname'])) {
            $faculty['faculty']['shortname'] = $data['shortname'];
        }

        return $this->ScheduleService->saveFaculty($faculty['faculty'], $oldFacultyShortName);
    }

    // Метод для удаления факультета и его вмісту
    #[Route('/api/{facultyShortName}', methods: ['DELETE'])]
    public function deleteFaculty(string $facultyShortName): JsonResponse
    {
        return $this->ScheduleService->deleteFaculty($facultyShortName);
    }
}
