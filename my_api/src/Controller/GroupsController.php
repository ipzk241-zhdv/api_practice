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
        if (!isset($data['group_name']) || strlen($data['group_name'] == 0)) {
            return $this->ScheduleService->jsonResponse(false, '"group_name" is required', status: 400);
        }

        $faculty = $this->ScheduleService->find($facultyName, $courseName, $data['group_name']);
        if (!$faculty instanceof JsonResponse) {
            return $this->ScheduleService->jsonResponse(false, 'Group with this "group_name" already exist', status: 400);
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

        $group = $this->ScheduleService->find($facultyName, $courseName, $oldGroupName);
        if ($group instanceof JsonResponse) {
            return $group;
        }

        $group['group_name'] = $data['group_name'];
        return $this->ScheduleService->saveGroup($facultyName, $courseName, $group);
    }

    // Метод для видалення групи
    #[Route('/schedule/{facultyName}/{courseName}/{groupName}', methods: ['DELETE'])]
    public function deleteGroup(string $facultyName, string $courseName, string $groupName): JsonResponse
    {
        $course = $this->ScheduleService->find($facultyName, $courseName);
        if ($course instanceof JsonResponse) {
            return $course;
        }

        $groupKey = array_search($groupName, array_column($course['groups'], 'group_name'));
        if (!$groupKey) {
            return $this->ScheduleService->jsonResponse(false, "Group not found", status: 404);
        }

        array_splice($course['groups'], $groupKey, 1);
        return $this->ScheduleService->saveCourse($facultyName, $course);
    }


    // TODO
    // // Метод для створення заняття
    // #[Route('/schedule/{facultyName}/{courseName}/groups/{groupName}/classes', methods: ['POST'])]
    // public function createClass(Request $request, string $facultyName, string $courseName, string $groupName): JsonResponse
    // {
    //     return;
    // }

    // // Метод для редагування занятия
    // #[Route('/schedule/{facultyName}/{courseName}/groups/{groupName}/classes/{index}', methods: ['PATCH'])]
    // public function updateClass(Request $request, string $facultyName, string $courseName, string $groupName, int $index): JsonResponse
    // {
    //     return;
    // }
}
