<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;

class ServiceSchedule
{
    private $filePath;

    public function __construct()
    {
        $this->filePath = __DIR__ . '/../data/all.json';
    }

    private function loadScheduleData(): array
    {
        $jsonContent = file_get_contents($this->filePath);
        return json_decode($jsonContent, true);
    }

    private function findFacultyByShortname(array $scheduleData, string $facultyShortname): ?array
    {
        foreach ($scheduleData as $faculty) {
            if ($faculty['shortname'] === $facultyShortname) {
                return $faculty;
            }
        }
        return null;
    }

    private function findCourseByName(array $courses, string $courseName): ?array
    {
        foreach ($courses as $course) {
            if ($course['name'] === $courseName) {
                return $course;
            }
        }
        return null;
    }

    private function findGroupByName(array $groups, string $groupName): ?array
    {
        foreach ($groups as $group) {
            if ($group['group_name'] === $groupName) {
                return $group;
            }
        }
        return null;
    }

    public function find(string $facultyName, ?string $courseName = null, ?string $groupName = null)
    {
        $scheduleData = $this->loadScheduleData();
        $faculty = $this->findFacultyByShortName($scheduleData, $facultyName);

        if ($faculty === null) {
            return $this->jsonResponse(false, "Faculty not found", status: 404);
        }
        $result = ['faculty' => $faculty];

        if (isset($courseName)) {
            $course = $this->findCourseByName($faculty['course'], $courseName);
            if ($course === null) {
                return $this->jsonResponse(false, "Course not found", status: 404);
            }
            $result['course'] = $course;
        }

        if (isset($groupName)) {
            $group = $this->findGroupByName($course['groups'], $groupName);
            if ($group === null) {
                return $this->jsonResponse(false, "Group not found", status: 404);
            }
            $result['group'] = $group;
        }

        return $result;
    }

    public function FacultiesList()
    {
        $scheduleData = $this->loadScheduleData();

        $faculties = array_map(function ($faculty) {
            return [
                'name' => $faculty['name'],
                'shortname' => $faculty['shortname']
            ];
        }, $scheduleData);

        return $faculties;
    }

    public function FacultyCoursesList($facultyShortname)
    {
        $scheduleData = $this->loadScheduleData();
        $faculty = $this->findFacultyByShortname($scheduleData, $facultyShortname);

        if ($faculty === null) {
            return $this->jsonResponse(false, "Faculty not found", status: 404);
        }

        $courseNames = array_map(fn($course) => $course['name'], $faculty['course']);

        return $courseNames;
    }

    public function CourseGroupsList($facultyShortname, $courseName)
    {
        $scheduleData = $this->loadScheduleData();
        $course = $this->find($facultyShortname, $courseName);
        if ($course instanceof JsonResponse) {
            return $course;
        }

        $groupNames = array_map(fn($group) => $group['group_name'], $course);

        return $groupNames;
    }

    public function deleteFaculty(string $facultyShortname): JsonResponse
    {
        $scheduleData = $this->loadScheduleData();
        $facultyFound = false;

        foreach ($scheduleData as $index => $faculty) {
            if ($faculty['shortname'] === $facultyShortname) {
                unset($scheduleData[$index]);
                $facultyFound = true;
                break;
            }
        }

        if (!$facultyFound) {
            return $this->jsonResponse(false, "Faculty not found", 404);
        }

        $scheduleData = array_values($scheduleData);
        $this->saveScheduleData($scheduleData);

        return $this->jsonResponse(true, "Faculty deleted successfully", 200);
    }

    public function saveFaculty(array $facultyData, ?string $oldFacultyShortName = null): JsonResponse
    {
        $scheduleData = $this->loadScheduleData();

        foreach ($scheduleData as &$faculty) {
            if ($oldFacultyShortName === null ? $facultyData['shortname'] : $oldFacultyShortName === $faculty['shortname']) {
                $faculty = $facultyData;
                $this->saveScheduleData($scheduleData);
                return $this->jsonResponse(true, 'Faculty updated successfully', $faculty);
            }
        }

        $scheduleData[] = $facultyData;
        $this->saveScheduleData($scheduleData);
        return $this->jsonResponse(true, 'Faculty added successfully', $facultyData);
    }

    public function saveCourse(string $facultyShortname, array $courseData, ?string $oldCourseName = null): JsonResponse
    {
        $scheduleData = $this->loadScheduleData();
        foreach ($scheduleData as &$faculty) {
            if ($faculty['shortname'] === $facultyShortname) {
                foreach ($faculty['course'] as &$course) {
                    if ($oldCourseName === $course['name']) {
                        $course = $courseData;
                        $this->saveScheduleData($scheduleData);
                        return $this->jsonResponse(true, 'Course updated successfully', $courseData);
                    }
                }
                $faculty['course'][] = $courseData;
                $this->saveScheduleData($scheduleData);
                return $this->jsonResponse(true, 'Course added successfully', $courseData);
            }
        }
        return $this->jsonResponse(false, 'Faculty not found', null, 404);
    }

    public function saveGroup(string $facultyShortname, string $courseName, array $groupData, ?string $oldGroupName): JsonResponse
    {
        $scheduleData = $this->loadScheduleData();
        foreach ($scheduleData as &$faculty) {
            if ($faculty['shortname'] === $facultyShortname) {
                foreach ($faculty['course'] as &$course) {
                    if ($course['name'] === $courseName) {
                        foreach ($course['groups'] as &$group) {
                            if ($oldGroupName === $group['group_name']) {
                                $group = $groupData;
                                $this->saveScheduleData($scheduleData);
                                return $this->jsonResponse(true, 'Group updated successfully', $groupData);
                            }
                        }
                        $course['groups'][] = $groupData;
                        $this->saveScheduleData($scheduleData);
                        return $this->jsonResponse(true, 'Group added successfully', $groupData);
                    }
                }
            }
        }
        return $this->jsonResponse(false, 'Faculty or Course not found', null, 404);
    }

    private function saveScheduleData(array $scheduleData): void
    {
        file_put_contents($this->filePath, json_encode($scheduleData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    public function jsonResponse(bool $successful, string $message, $data = null, int $status = 200): JsonResponse
    {
        return new JsonResponse([
            'successful' => $successful,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
