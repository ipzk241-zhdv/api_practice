<?php

namespace App\Controller;

use App\Service\ServiceSchedule;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class GroupsController extends AbstractController
{
    private $ScheduleService;

    public function __construct(ServiceSchedule $scheduleService)
    {
        $this->ScheduleService = $scheduleService;
    }

    // Розклад певної групи
    #[Route('/api/schedule/{facultyName}/{courseName}/{groupName}', methods: ['GET'])]
    public function getScheduleGroupByCourse(string $facultyName, string $courseName, string $groupName): JsonResponse
    {
        $result = $this->ScheduleService->find($facultyName, $courseName, $groupName);
        if ($result instanceof JsonResponse) {
            return $result;
        }

        return $this->json($result['group']);
    }

    // Створення групи
    #[Route('/api/{facultyName}/{courseName}/createGroup', methods: ['POST'])]
    public function createGroup(Request $request, string $facultyName, string $courseName): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['group_name'])) {
            return $this->ScheduleService->jsonResponse(false, '"group_name" is required', status: 409);
        }

        $entities = $this->ScheduleService->find($facultyName, $courseName, $data['group_name']);
        if (!$entities instanceof JsonResponse) {
            return $this->ScheduleService->jsonResponse(false, 'Group with this "group_name" already exist', status: 409);
        }

        $newGroup = [
            'group_name' => $data['group_name'],
            'schedule' => [
                'firstweek' => [
                    'monday'    => [],
                    'tuesday'   => [],
                    'wednesday' => [],
                    'thursday'  => [],
                    'friday'    => [],
                    'saturday'  => []
                ],
                'secondweek' => [
                    'monday'    => [],
                    'tuesday'   => [],
                    'wednesday' => [],
                    'thursday'  => [],
                    'friday'    => [],
                    'saturday'  => []
                ],
            ]
        ];

        return $this->ScheduleService->saveGroup($facultyName, $courseName, $newGroup);
    }

    // Зміна назви групи
    #[Route('/api/{facultyName}/{courseName}/{oldGroupName}', methods: ['PATCH'])]
    public function updateGroupName(Request $request, string $facultyName, string $courseName, string $oldGroupName): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['group_name'])) {
            return $this->ScheduleService->jsonResponse(false, '"group_name" is required', status: 400);
        }

        $entities = $this->ScheduleService->find($facultyName, $courseName, $oldGroupName);
        if ($entities instanceof JsonResponse) {
            return $entities;
        }
        $group = $entities['group'];

        $entities = $this->ScheduleService->find($facultyName, $courseName, $data['group_name']);
        if (!$entities instanceof JsonResponse) {
            return $this->ScheduleService->jsonResponse(false, 'Group with this "group_name" already exist', status: 409);
        }

        $group['group_name'] = $data['group_name'];
        return $this->ScheduleService->saveGroup($facultyName, $courseName, $group, $oldGroupName);
    }

    // Видалення групи
    #[Route('/api/{facultyName}/{courseName}/{groupName}', methods: ['DELETE'])]
    public function deleteGroup(string $facultyName, string $courseName, string $groupName): JsonResponse
    {
        $entities = $this->ScheduleService->find($facultyName, $courseName);
        if ($entities instanceof JsonResponse) {
            return $entities;
        }

        $groupKey = array_search($groupName, array_column($entities['course']['groups'], 'group_name'));
        if ($groupKey === false) {
            return $this->ScheduleService->jsonResponse(false, "Group not found", status: 404);
        }

        array_splice($entities['course']['groups'], $groupKey, 1);
        return $this->ScheduleService->saveCourse($facultyName, $entities['course']);
    }

    // Створення заняття
    #[Route('/api/{facultyName}/{courseName}/{groupName}/classes', methods: ['POST'])]
    public function createClass(Request $request, string $facultyName, string $courseName, string $groupName): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['time'], $data['week'], $data['day'])) {
            return $this->ScheduleService->jsonResponse(false, '"week", "day" and "time" and at least one of "discipline", "auditory", "teacher" are required', status: 400);
        }

        if (!isset($data['auditory']) && !isset($data['teacher']) && !isset($data['discipline'])) {
            return $this->ScheduleService->jsonResponse(false, 'At least one of "discipline", "auditory", "teacher" are required', status: 400);
        }

        $entities = $this->ScheduleService->find($facultyName, $courseName, $groupName);
        if ($entities instanceof JsonResponse) {
            return $entities;
        }

        foreach ($data as $key => $value) {
            $$key = $value;
        }

        $group = $entities['group'];
        array_push($group['schedule'][$week][$day], [
            'time' => $time,
            'discipline' => $discipline ?? '',
            'teacher' => $teacher ?? '',
            'auditory' => $auditory ?? ''
        ]);

        return $this->ScheduleService->saveGroup($facultyName, $courseName, $group);
    }

    // Редагування заннятя
    #[Route('/api/{facultyName}/{courseName}/{groupName}/classes', methods: ['PATCH'])]
    public function updateClass(Request $request, string $facultyName, string $courseName, string $groupName): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['time'], $data['week'], $data['day'])) {
            return $this->ScheduleService->jsonResponse(false, '"week", "day" and "time" and at least one of "discipline", "auditory", "teacher" are required', status: 400);
        }

        if (!isset($data['auditory']) && !isset($data['teacher']) && !isset($data['discipline'])) {
            return $this->ScheduleService->jsonResponse(false, 'At least one of "discipline", "auditory", "teacher" are required', status: 400);
        }

        $entities = $this->ScheduleService->find($facultyName, $courseName, $groupName);
        if ($entities instanceof JsonResponse) {
            return $entities;
        }

        $group = $entities['group'];
        foreach ($data as $key => $value) {
            $$key = $value;
        }

        $classesKey = array_search($time, array_column($group['schedule'][$week][$day], 'time'));
        if ($classesKey === false) {
            return $this->ScheduleService->jsonResponse(false, "Classes on that time not found", status: 404);
        }

        if (isset($teacher)) $group['schedule'][$week][$day][$classesKey]['teacher'] = $teacher;
        if (isset($discipline)) $group['schedule'][$week][$day][$classesKey]['discipline'] = $discipline;
        if (isset($auditory)) $group['schedule'][$week][$day][$classesKey]['auditory'] = $auditory;

        return $this->ScheduleService->saveGroup($facultyName, $courseName, $group);
    }

        // Видалення заннятя
        #[Route('/api/{facultyName}/{courseName}/{groupName}/classes', methods: ['DELETE'])]
        public function deleteClass(Request $request, string $facultyName, string $courseName, string $groupName): JsonResponse
        {
            $data = json_decode($request->getContent(), true);
            if (!isset($data['time'], $data['week'], $data['day'])) {
                return $this->ScheduleService->jsonResponse(false, '"time", "week" and "day" are required', status: 400);
            }
    
            $entities = $this->ScheduleService->find($facultyName, $courseName, $groupName);
            if ($entities instanceof JsonResponse) {
                return $entities;
            }
    
            $group = $entities['group'];
            foreach ($data as $key => $value) {
                $$key = $value;
            }
    
            $classesKey = array_search($time, array_column($group['schedule'][$week][$day], 'time'));
            if ($classesKey === false) {
                return $this->ScheduleService->jsonResponse(false, "Classes on that time not found", status: 404);
            }

            array_splice($group['schedule'][$week][$day], $classesKey, 1);
            return $this->ScheduleService->saveGroup($facultyName, $courseName, $group);
        }
}
