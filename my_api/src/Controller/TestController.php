<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Console\Output\ConsoleOutput;
// $output = new ConsoleOutput();
// $output->writeln(print_r($data, true));

class TestController extends AbstractController
{
    #[Route('/schedule/{week?}/{day?}', name: 'app_schedule_day', methods: ['GET'])]
    public function getDaySchedule(?string $week, ?string $day): JsonResponse
    {
        $filePath = $this->getParameter('kernel.project_dir') . '/src/data/schedule.json';

        if (!file_exists($filePath)) {
            return new JsonResponse(['error' => 'Файл не знайдено'], Response::HTTP_NOT_FOUND);
        }

        $jsonData = file_get_contents($filePath);
        $data = json_decode($jsonData, true);

        if (!$week && !$day) {
            return new JsonResponse($jsonData, Response::HTTP_OK, [], true);
        }

        if ($week && !$day) {
            if (!isset($data["schedule"][$week])) {
                return new JsonResponse(['error' => 'Дані для вказаного тижня і дня не знайдено'], Response::HTTP_NOT_FOUND);
            }
            return new JsonResponse(json_encode($data["schedule"][$week]), Response::HTTP_OK, [], true);
        }

        if ($week && $day) {
            if (!isset($data["schedule"][$week]) || !isset($data["schedule"][$week][$day])) {
                return new JsonResponse(['error' => 'Дані для вказаного тижня або дня не знайдено'], Response::HTTP_NOT_FOUND);
            }

            $daySchedule = $data["schedule"][$week][$day];
            return new JsonResponse(json_encode($daySchedule), Response::HTTP_OK, [], true);
        }

        return new JsonResponse(['error' => 'Неправильні параметри запиту'], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/schedule/{week}/{day}/{time}', name: 'app_schedule_day_update', methods: ['POST'])]
    public function updateDaySchedule(Request $request, string $week, string $day, string $time): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || (!isset($data['discipline']) && !isset($data['teacher']) && !isset($data['auditory']))) {
            return new JsonResponse(['error' => 'Недостатньо даних'], Response::HTTP_BAD_REQUEST);
        }

        $filePath = $this->getParameter('kernel.project_dir') . '/src/data/schedule.json';

        if (!file_exists($filePath)) {
            return new JsonResponse(['error' => 'Файл не знайдено'], Response::HTTP_NOT_FOUND);
        }

        $jsonData = file_get_contents($filePath);
        $scheduleData = json_decode($jsonData, true);

        if (!isset($scheduleData['schedule'][$week]) || !isset($scheduleData['schedule'][$week][$day])) {
            return new JsonResponse(['error' => 'Дані для вказаного тижня або дня не знайдено'], Response::HTTP_NOT_FOUND);
        }

        $lessons = &$scheduleData['schedule'][$week][$day];
        $lessonIndex = null;

        foreach ($lessons as $index => $lesson) {
            if ($lesson['time'] === $time) {
                $lessonIndex = $index;
                break;
            }
        }

        if ($lessonIndex === null) {
            return new JsonResponse(['error' => 'Не знайдено уроку на цей час'], Response::HTTP_NOT_FOUND);
        }

        if (isset($data['discipline'])) {
            $lessons[$lessonIndex]['discipline'] = $data['discipline'];
        }
        if (isset($data['teacher'])) {
            $lessons[$lessonIndex]['teacher'] = $data['teacher'];
        }
        if (isset($data['auditory'])) {
            $lessons[$lessonIndex]['auditory'] = $data['auditory'];
        }

        file_put_contents($filePath, json_encode($scheduleData, JSON_UNESCAPED_UNICODE));

        return new JsonResponse(['success' => 'Успішно збережено'], Response::HTTP_OK);
    }
}


// TODO
// delete schedule for specified time (clear btn in site)
// add schedule for specified time