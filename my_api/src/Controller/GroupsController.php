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

    // Метод для получения группы на факультете и курсе
    #[Route('/api/schedule/{facultyName}/{courseName}/{groupName}', methods: ['GET'])]
    public function getScheduleGroupByCourse(string $facultyName, string $courseName, string $groupName): JsonResponse
    {
        $scheduleData = $this->ScheduleService->loadScheduleData();
        $faculty = $this->ScheduleService->findFacultyByShortName($scheduleData, $facultyName);

        if ($faculty === null) {
            return $this->json(['error' => 'Faculty not found'], 404);
        }

        $course = $this->ScheduleService->findCourseByName($faculty['course'], $courseName);

        if ($course === null) {
            return $this->json(['error' => 'Course not found'], 404);
        }

        $group = $this->ScheduleService->findGroupByName($course['groups'], $groupName);
        if ($group === null) {
            return $this->json(['error' => 'Group not found'], 404);
        }

        return $this->json($group);
    }

    // Метод для создания новой группы
    #[Route('/api/schedule/{facultyName}/{courseName}/groups', methods: ['POST'])]
    public function createGroup(Request $request, string $facultyName, string $courseName): JsonResponse
    {
        $scheduleData = $this->ScheduleService->loadScheduleData();
        $faculty = $this->ScheduleService->findFacultyByShortName($scheduleData, $facultyName);

        if ($faculty === null) {
            return $this->json(['error' => 'Faculty not found'], 404);
        }

        $course = $this->ScheduleService->findCourseByName($faculty['course'], $courseName);

        if ($course === null) {
            return $this->json(['error' => 'Course not found'], 404);
        }

        // Отримання імені нової групи з тіла запиту
        $data = json_decode($request->getContent(), true);
        if (!isset($data['group_name'])) {
            return $this->json(['error' => 'Group name is required'], 400);
        }

        // Створення нової групи з базовим розкладом
        $newGroup = [
            'group_name' => $data['group_name'],
            'schedule' => [
                'firstweek' => [
                    'monday' => [],
                    'tuesday' => [],
                    'wednesday' => [],
                    'thursday' => [],
                    'friday' => [],
                    'saturday' => []
                ],
                'secondweek' => [
                    'monday' => [],
                    'tuesday' => [],
                    'wednesday' => [],
                    'thursday' => [],
                    'friday' => [],
                    'saturday' => []
                ],
            ]
        ];

        // Додавання нової групи до курсу
        $course['groups'][] = $newGroup;

        // Збереження оновлених даних у файл
        file_put_contents('schedule.json', json_encode($scheduleData, JSON_PRETTY_PRINT));

        return $this->json($newGroup, 201);
    }

    // Метод для изменения названия группы
    #[Route('/schedule/{facultyName}/{courseName}/groups/{oldGroupName}', methods: ['PATCH'])]
    public function updateGroupName(Request $request, string $facultyName, string $courseName, string $oldGroupName): JsonResponse
    {
        $scheduleData = $this->ScheduleService->loadScheduleData();
        $faculty = $this->ScheduleService->findFacultyByShortName($scheduleData, $facultyName);

        if ($faculty === null) {
            return $this->json(['error' => 'Faculty not found'], 404);
        }

        $course = $this->ScheduleService->findCourseByName($faculty['course'], $courseName);

        if ($course === null) {
            return $this->json(['error' => 'Course not found'], 404);
        }

        $group = $this->ScheduleService->findGroupByName($course['groups'], $oldGroupName);
        if ($group === null) {
            return $this->json(['error' => 'Group not found'], 404);
        }

        // Отримання нової назви групи з тіла запиту
        $data = json_decode($request->getContent(), true);
        if (!isset($data['group_name'])) {
            return $this->json(['error' => 'New group name is required'], 400);
        }

        // Зміна назви групи
        $group['group_name'] = $data['group_name'];

        // Збереження оновлених даних у файл
        file_put_contents('schedule.json', json_encode($scheduleData, JSON_PRETTY_PRINT));

        return $this->json($group);
    }

    // Метод для видалення групи
    #[Route('/schedule/{facultyName}/{courseName}/groups/{groupName}', methods: ['DELETE'])]
    public function deleteGroup(string $facultyName, string $courseName, string $groupName): JsonResponse
    {
        // Завантаження даних розкладу
        $scheduleData = $this->ScheduleService->loadScheduleData();

        // Пошук факультету за коротким ім'ям
        $faculty = $this->ScheduleService->findFacultyByShortName($scheduleData, $facultyName);

        if ($faculty === null) {
            return $this->json(['error' => 'Faculty not found'], 404);
        }

        // Пошук курсу за назвою
        $course = $this->ScheduleService->findCourseByName($faculty['course'], $courseName);

        if ($course === null) {
            return $this->json(['error' => 'Course not found'], 404);
        }

        // Пошук групи за назвою
        $groupKey = array_search($groupName, array_column($course['groups'], 'group_name'));

        if ($groupKey === false) {
            return $this->json(['error' => 'Group not found'], 404);
        }

        // Видалення групи
        array_splice($course['groups'], $groupKey, 1);

        // Збереження оновлених даних
        file_put_contents('schedule.json', json_encode($scheduleData, JSON_PRETTY_PRINT));

        return $this->json(['message' => 'Group deleted successfully']);
    }

    // Метод для создания занятия
    #[Route('/schedule/{facultyName}/{courseName}/groups/{groupName}/classes', methods: ['POST'])]
    public function createClass(Request $request, string $facultyName, string $courseName, string $groupName): JsonResponse
    {
        // Завантаження даних розкладу
        $scheduleData = $this->ScheduleService->loadScheduleData();

        // Пошук факультету
        $faculty = $this->ScheduleService->findFacultyByShortName($scheduleData, $facultyName);
        if ($faculty === null) {
            return $this->json(['error' => 'Faculty not found'], 404);
        }

        // Пошук курсу
        $course = $this->ScheduleService->findCourseByName($faculty['course'], $courseName);
        if ($course === null) {
            return $this->json(['error' => 'Course not found'], 404);
        }

        // Пошук групи
        $group = $this->ScheduleService->findGroupByName($course['groups'], $groupName);
        if ($group === null) {
            return $this->json(['error' => 'Group not found'], 404);
        }

        // Отримання даних з тіла запиту
        $data = json_decode($request->getContent(), true);

        // Перевірка наявності обов'язкових параметрів
        if (empty($data['classroom']) || empty($data['time']) || empty($data['teacher'])) {
            return $this->json(['error' => 'Classroom, time, and teacher are required'], 400);
        }

        // Створення нового заняття
        $newClass = [
            'classroom' => $data['classroom'],
            'time' => $data['time'],
            'teacher' => $data['teacher']
        ];

        // Додавання заняття до групи на кожному тижні
        $group['schedule']['firstweek'][] = $newClass;
        $group['schedule']['secondweek'][] = $newClass;

        // Збереження оновлених даних
        file_put_contents('schedule.json', json_encode($scheduleData, JSON_PRETTY_PRINT));

        return $this->json(['message' => 'Class created successfully', 'class' => $newClass], 201);
    }

    // Метод для редагування занятия
    #[Route('/schedule/{facultyName}/{courseName}/groups/{groupName}/classes/{index}', methods: ['PATCH'])]
    public function updateClass(Request $request, string $facultyName, string $courseName, string $groupName, int $index): JsonResponse
    {
        // Завантаження даних розкладу
        $scheduleData = $this->ScheduleService->loadScheduleData();

        // Пошук факультету
        $faculty = $this->ScheduleService->findFacultyByShortName($scheduleData, $facultyName);
        if ($faculty === null) {
            return $this->json(['error' => 'Faculty not found'], 404);
        }

        // Пошук курсу
        $course = $this->ScheduleService->findCourseByName($faculty['course'], $courseName);
        if ($course === null) {
            return $this->json(['error' => 'Course not found'], 404);
        }

        // Пошук групи
        $group = $this->ScheduleService->findGroupByName($course['groups'], $groupName);
        if ($group === null) {
            return $this->json(['error' => 'Group not found'], 404);
        }

        // Перевірка, чи є таке заняття в розкладі
        if (!isset($group['schedule']['firstweek'][$index]) && !isset($group['schedule']['secondweek'][$index])) {
            return $this->json(['error' => 'Class not found'], 404);
        }

        // Отримання даних з тіла запиту
        $data = json_decode($request->getContent(), true);
        $updatedClass = false;

        // Оновлення даних заняття
        if (isset($data['classroom'])) {
            $group['schedule']['firstweek'][$index]['classroom'] = $data['classroom'];
            $group['schedule']['secondweek'][$index]['classroom'] = $data['classroom'];
            $updatedClass = true;
        }
        if (isset($data['time'])) {
            $group['schedule']['firstweek'][$index]['time'] = $data['time'];
            $group['schedule']['secondweek'][$index]['time'] = $data['time'];
            $updatedClass = true;
        }
        if (isset($data['teacher'])) {
            $group['schedule']['firstweek'][$index]['teacher'] = $data['teacher'];
            $group['schedule']['secondweek'][$index]['teacher'] = $data['teacher'];
            $updatedClass = true;
        }

        // Якщо немає оновлень, повертаємо помилку
        if (!$updatedClass) {
            return $this->json(['error' => 'At least one of classroom, time, or teacher must be provided'], 400);
        }

        // Збереження оновлених даних
        file_put_contents('schedule.json', json_encode($scheduleData, JSON_PRETTY_PRINT));

        return $this->json(['message' => 'Class updated successfully']);
    }
}
