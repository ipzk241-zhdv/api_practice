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

    #[Route('/schedule', methods: ['GET'])]
    public function getScheduleAllFaculties(): JsonResponse
    {
        $scheduleData = $this->ScheduleService->loadScheduleData();
        return $this->json($scheduleData);
    }

    // Метод для получения всех факультетов с полями name и shortname
    #[Route('/api/faculties', methods: ['GET'])]
    public function getFacultiesList(): JsonResponse
    {
        $scheduleData = $this->ScheduleService->loadScheduleData();
        $faculties = array_map(function ($faculty) {
            return [
                'name' => $faculty['name'],
                'shortname' => $faculty['shortname']
            ];
        }, $scheduleData);

        return $this->json($faculties);
    }

    // Метод для получения всех курсов на факультете
    #[Route('/api/{facultyShortname}/courses', methods: ['GET'])]
    public function getCoursesList(string $facultyShortname): JsonResponse
    {
        $scheduleData = $this->ScheduleService->loadScheduleData();
        $faculty = $this->ScheduleService->findFacultyByShortname($scheduleData, $facultyShortname);

        if ($faculty === null) {
            return $this->json(['error' => 'Faculty not found'], 404);
        }

        $courseNames = array_map(function ($course) {
            return $course['name'];
        }, $faculty['course']);

        return $this->json($courseNames);
    }

    // Метод для получения всех групп для факультета и курса
    #[Route('/api/{facultyShortname}/{courseName}/groups', methods: ['GET'])]
    public function getGroupsList(string $facultyShortname, string $courseName): JsonResponse
    {
        $scheduleData = $this->ScheduleService->loadScheduleData();
        $faculty = $this->ScheduleService->findFacultyByShortname($scheduleData, $facultyShortname);

        if ($faculty === null) {
            return $this->json(['error' => 'Faculty not found'], 404);
        }

        $course = $this->ScheduleService->findCourseByName($faculty['course'], $courseName);

        if ($course === null) {
            return $this->json(['error' => 'Course not found'], 404);
        }

        $groupNames = array_map(function ($group) {
            return $group['group_name'];
        }, $course['groups']);

        return $this->json($groupNames);
    }
}
