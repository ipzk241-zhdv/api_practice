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

    // Метод для получения курсов на факультете
    #[Route('/api/schedule/{facultyShortName}', methods: ['GET'])]
    public function getScheduleCoursesByFaculty(string $facultyShortName): JsonResponse
    {
        $scheduleData = $this->ScheduleService->loadScheduleData();
        $faculty = $this->ScheduleService->findFacultyByShortName($scheduleData, $facultyShortName);

        if ($faculty === null) {
            return $this->json(['error' => 'Faculty not found'], 404);
        }

        return $this->json($faculty['course']);
    }

    // Метод для создания нового факультета
    #[Route('/api/faculty/create', methods: ['POST'])]
    public function createFaculty(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Перевірка, чи надано назву та коротку назву
        if (empty($data['name']) || empty($data['shortname'])) {
            return $this->json(['error' => 'Both name and shortname are required'], 400);
        }

        // Логіка для створення факультету (можна додати в Service або безпосередньо тут)
        $newFaculty = [
            'name' => $data['name'],
            'shortname' => $data['shortname'],
            'course' => [] // Початково порожній масив курсів
        ];

        // Можна зберегти новий факультет в базі даних або у файлі
        // Для прикладу, додамо до файлу
        $scheduleData = $this->ScheduleService->loadScheduleData();
        $scheduleData[] = $newFaculty;
        file_put_contents('schedule.json', json_encode($scheduleData, JSON_PRETTY_PRINT));

        return $this->json(['message' => 'Faculty created successfully', 'faculty' => $newFaculty], 201);
    }

    // Метод для оновлення факультету
    #[Route('/api/faculty/{facultyShortName}', methods: ['PATCH'])]
    public function updateFaculty(string $facultyShortName, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Перевірка, чи надано хоча б одне поле для оновлення
        if (empty($data['name']) && empty($data['shortname'])) {
            return $this->json(['error' => 'At least one of name or shortname is required'], 400);
        }

        // Завантаження даних розкладу
        $scheduleData = $this->ScheduleService->loadScheduleData();
        $faculty = $this->ScheduleService->findFacultyByShortName($scheduleData, $facultyShortName);

        if ($faculty === null) {
            return $this->json(['error' => 'Faculty not found'], 404);
        }

        // Оновлення даних факультету
        if (!empty($data['name'])) {
            $faculty['name'] = $data['name'];
        }
        if (!empty($data['shortname'])) {
            $faculty['shortname'] = $data['shortname'];
        }

        // Збереження оновлених даних
        file_put_contents('schedule.json', json_encode($scheduleData, JSON_PRETTY_PRINT));

        return $this->json(['message' => 'Faculty updated successfully', 'faculty' => $faculty]);
    }

    // Метод для удаления факультета и его вмісту
    #[Route('/api/faculty/{facultyShortName}', methods: ['DELETE'])]
    public function deleteFaculty(string $facultyShortName): JsonResponse
    {
        // Завантаження даних розкладу
        $scheduleData = $this->ScheduleService->loadScheduleData();

        // Пошук факультету за коротким ім'ям
        $facultyKey = array_search($facultyShortName, array_column($scheduleData, 'shortname'));

        if ($facultyKey === false) {
            return $this->json(['error' => 'Faculty not found'], 404);
        }

        // Видалення факультету і всього його вмісту
        array_splice($scheduleData, $facultyKey, 1);

        // Збереження оновлених даних
        file_put_contents('schedule.json', json_encode($scheduleData, JSON_PRETTY_PRINT));

        return $this->json(['message' => 'Faculty and its contents deleted successfully']);
    }
}
