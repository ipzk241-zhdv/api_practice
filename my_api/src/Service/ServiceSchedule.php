<?php
namespace App\Service;

class ServiceSchedule
{
    private $filePath;

    public function __construct()
    {
        $this->filePath = __DIR__ . '/../data/all.json';
    }

    // Загрузка данных из JSON
    public function loadScheduleData(): array
    {
        $jsonContent = file_get_contents($this->filePath);
        return json_decode($jsonContent, true);
    }

    // Поиск факультета по shortname
    public function findFacultyByShortname(array $scheduleData, string $facultyShortname): ?array
    {
        foreach ($scheduleData as $faculty) {
            if ($faculty['shortname'] === $facultyShortname) {
                return $faculty;
            }
        }
        return null;
    }

    // Поиск курса по имени
    public function findCourseByName(array $courses, string $courseName): ?array
    {
        foreach ($courses as $course) {
            if ($course['name'] === $courseName) {
                return $course;
            }
        }
        return null;
    }

    // Поиск группы по имени
    public function findGroupByName(array $groups, string $groupName): ?array
    {
        foreach ($groups as $group) {
            if ($group['group_name'] === $groupName) {
                return $group;
            }
        }
        return null;
    }
}
