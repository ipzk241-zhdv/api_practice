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

    // Метод для получения групп на курсе
    #[Route('/api/schedule/{facultyName}/{courseName}', methods: ['GET'])]
    public function getScheduleCourseByFaculty(string $facultyName, string $courseName): JsonResponse
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

        return $this->json($course['groups']);
    }

    // Метод для создания нового курса
    #[Route('/api/{facultyShortName}/createCourse', methods: ['POST'])]
    public function createCourse(string $facultyShortName, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Перевірка, чи надано назву курсу
        if (empty($data['name'])) {
            return $this->json(['error' => 'Course name is required'], 400);
        }

        // Завантажуємо дані розкладу
        $scheduleData = $this->ScheduleService->loadScheduleData();
        $faculty = $this->ScheduleService->findFacultyByShortName($scheduleData, $facultyShortName);

        if ($faculty === null) {
            return $this->json(['error' => 'Faculty not found'], 404);
        }

        // Перевірка, чи існує вже курс з такою назвою
        foreach ($faculty['course'] as $course) {
            if ($course['name'] === $data['name']) {
                return $this->json(['error' => 'Course with this name already exists'], 400);
            }
        }

        // Створення нового курсу
        $newCourse = [
            'name' => $data['name'],
            'groups' => [] // Початково порожній масив груп
        ];

        // Додавання курсу до факультету
        $faculty['course'][] = $newCourse;

        // Збереження оновлених даних
        file_put_contents('schedule.json', json_encode($scheduleData, JSON_PRETTY_PRINT));

        return $this->json(['message' => 'Course created successfully', 'course' => $newCourse], 201);
    }

    // Метод для обновления названия курса
    #[Route('/api/{facultyShortName}/{courseName}', methods: ['PATCH'])]
    public function updateCourse(string $facultyShortName, string $courseName, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Перевірка, чи надано нову назву курсу
        if (empty($data['name'])) {
            return $this->json(['error' => 'New course name is required'], 400);
        }

        // Завантажуємо дані розкладу
        $scheduleData = $this->ScheduleService->loadScheduleData();
        $faculty = $this->ScheduleService->findFacultyByShortName($scheduleData, $facultyShortName);

        if ($faculty === null) {
            return $this->json(['error' => 'Faculty not found'], 404);
        }

        // Знаходимо курс для оновлення
        $course = $this->ScheduleService->findCourseByName($faculty['course'], $courseName);

        if ($course === null) {
            return $this->json(['error' => 'Course not found'], 404);
        }

        // Оновлення назви курсу
        $course['name'] = $data['name'];

        // Збереження оновлених даних
        file_put_contents('schedule.json', json_encode($scheduleData, JSON_PRETTY_PRINT));

        return $this->json(['message' => 'Course name updated successfully', 'course' => $course]);
    }

    // Метод для удаления курса и его вмісту
    #[Route('/api/{facultyShortName}/{courseName}', methods: ['DELETE'])]
    public function deleteCourse(string $facultyShortName, string $courseName): JsonResponse
    {
        // Завантаження даних розкладу
        $scheduleData = $this->ScheduleService->loadScheduleData();

        // Пошук факультету за коротким ім'ям
        $faculty = $this->ScheduleService->findFacultyByShortName($scheduleData, $facultyShortName);

        if ($faculty === null) {
            return $this->json(['error' => 'Faculty not found'], 404);
        }

        // Пошук курсу за назвою
        $courseKey = array_search($courseName, array_column($faculty['course'], 'name'));

        if ($courseKey === false) {
            return $this->json(['error' => 'Course not found'], 404);
        }

        // Видалення курсу і всього його вмісту
        array_splice($faculty['course'], $courseKey, 1);

        // Збереження оновлених даних
        file_put_contents('schedule.json', json_encode($scheduleData, JSON_PRETTY_PRINT));

        return $this->json(['message' => 'Course and its contents deleted successfully']);
    }
}
