if (!localStorage.getItem("token")) {
    window.location.href = "auth.html";
}

// Змінні для відстеження стану
let currentFaculty = null;
let currentCourse = null;
let currentGroup = null;

// Отримання факультетів при завантаженні сторінки
window.onload = function () {
    fetchFaculties();
};

// Функція для отримання токену з localStorage
function getAuthHeaders() {
    const token = localStorage.getItem("token");
    return {
        "Content-Type": "application/json",
        Authorization: `Bearer ${token}`, // Додаємо токен в заголовок
    };
}

// Функція для отримання факультетів
function fetchFaculties() {
    fetch("http://127.0.0.1:8000/api/faculties", {
        method: "GET", // Вказуємо метод
        headers: getAuthHeaders(), // Додаємо авторизацію
    })
        .then((response) => response.json())
        .then((data) => {
            const facultiesTableBody = document.querySelector("#faculties-table tbody");
            facultiesTableBody.innerHTML = "";
            data.forEach((faculty) => {
                const row = document.createElement("tr");
                const nameCell = document.createElement("td");
                nameCell.textContent = faculty.name;
                const shortnameCell = document.createElement("td");
                shortnameCell.textContent = faculty.shortname;
                row.appendChild(nameCell);
                row.appendChild(shortnameCell);
                row.addEventListener("click", () => loadCourses(faculty.shortname));
                facultiesTableBody.appendChild(row);
            });
            document.getElementById("faculties-section").style.display = "block";
            document.getElementById("courses-section").style.display = "none";
            document.getElementById("groups-section").style.display = "none";
            document.getElementById("schedule-section").style.display = "none"; // Сховати розклад
        })
        .catch((error) => console.error("Error fetching faculties:", error));
}

// Функція для отримання курсів факультету
function loadCourses(facultyShortname) {
    currentFaculty = facultyShortname;
    fetch(`http://127.0.0.1:8000/api/${facultyShortname}/courses`, {
        method: "GET", // Вказуємо метод
        headers: getAuthHeaders(), // Додаємо авторизацію
    })
        .then((response) => response.json())
        .then((data) => {
            const coursesTableBody = document.querySelector("#courses-table tbody");
            coursesTableBody.innerHTML = "";
            data.forEach((courseName) => {
                const row = document.createElement("tr");
                const nameCell = document.createElement("td");
                nameCell.textContent = courseName;
                row.appendChild(nameCell);
                row.addEventListener("click", () => loadGroups(facultyShortname, courseName));
                coursesTableBody.appendChild(row);
            });
            document.getElementById("faculties-section").style.display = "none";
            document.getElementById("courses-section").style.display = "block";
            document.getElementById("groups-section").style.display = "none";
            document.getElementById("schedule-section").style.display = "none"; // Сховати розклад
        })
        .catch((error) => console.error("Error fetching courses:", error));
}

// Функція для отримання груп курсу
function loadGroups(facultyShortname, courseName) {
    currentCourse = courseName;
    fetch(`http://127.0.0.1:8000/api/${facultyShortname}/${courseName}/groups`, {
        method: "GET", // Вказуємо метод
        headers: getAuthHeaders(), // Додаємо авторизацію
    })
        .then((response) => response.json())
        .then((data) => {
            const groupsTableBody = document.querySelector("#groups-table tbody");
            groupsTableBody.innerHTML = "";
            data.forEach((groupName) => {
                const row = document.createElement("tr");
                const nameCell = document.createElement("td");
                nameCell.textContent = groupName;
                row.appendChild(nameCell);
                row.addEventListener("click", () => loadSchedule(facultyShortname, courseName, groupName)); // Додаємо слухач події для завантаження розкладу
                groupsTableBody.appendChild(row);
            });
            document.getElementById("faculties-section").style.display = "none";
            document.getElementById("courses-section").style.display = "none";
            document.getElementById("groups-section").style.display = "block";
            document.getElementById("schedule-section").style.display = "none"; // Сховати розклад
        })
        .catch((error) => console.error("Error fetching groups:", error));
}

// Функція для отримання розкладу групи
function loadSchedule(facultyShortname, courseName, groupName) {
    currentGroup = groupName;
    fetch(`http://127.0.0.1:8000/api/schedule/${facultyShortname}/${courseName}/${groupName}/`, {
        method: "GET", // Вказуємо метод
        headers: getAuthHeaders(), // Додаємо авторизацію
    })
        .then((response) => response.json())
        .then((data) => {
            const firstWeekTableBody = document.querySelector("#first-week-schedule tbody");
            const secondWeekTableBody = document.querySelector("#second-week-schedule tbody");

            console.log(firstWeekTableBody);
            console.log(secondWeekTableBody);

            // Очищаємо таблиці перед заповненням
            firstWeekTableBody.innerHTML = "";
            secondWeekTableBody.innerHTML = "";

            const timeSlots = ["8:30-9:50", "10:00-11:20", "11:40-13:00", "13:30-14:50", "15:00-16:20", "16:30-17:50", "18:00-19:20"];

            // Функція для створення рядка таблиці
            function createRow(timeSlot, weekSchedule, tableBody) {
                const row = document.createElement("tr");
                const timeCell = document.createElement("td");
                timeCell.textContent = timeSlot;
                row.appendChild(timeCell);

                // Перебираємо кожен день тижня
                ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday"].forEach((day) => {
                    const cell = document.createElement("td");
                    const scheduleForDay = weekSchedule && weekSchedule[day];

                    if (scheduleForDay) {
                        // Шукаємо інформацію для конкретного часу
                        const scheduleInfo = scheduleForDay.find((item) => item.time === timeSlot);

                        if (scheduleInfo) {
                            const cellContent = document.createElement("div");
                            cellContent.classList.add("schedule-cell");

                            const discipline = document.createElement("div");
                            discipline.classList.add("discipline");
                            discipline.textContent = scheduleInfo.discipline;
                            cellContent.appendChild(discipline);

                            const teacher = document.createElement("div");
                            teacher.classList.add("teacher");
                            teacher.textContent = scheduleInfo.teacher;
                            cellContent.appendChild(teacher);

                            const auditory = document.createElement("div");
                            auditory.classList.add("auditory");
                            auditory.textContent = scheduleInfo.auditory;
                            cellContent.appendChild(auditory);

                            // Вставляємо в клітинку
                            cell.appendChild(cellContent);
                        } else {
                            // Якщо немає заняття на цей час, ставимо порожню клітинку
                            cell.classList.add("empty");
                        }
                    } else {
                        // Якщо для дня немає жодного заняття, ставимо порожню клітинку
                        cell.classList.add("empty");
                    }

                    row.appendChild(cell);
                });

                tableBody.appendChild(row);
            }

            // Створюємо таблиці для кожного тижня
            timeSlots.forEach((timeSlot) => {
                createRow(timeSlot, data.schedule.firstweek, firstWeekTableBody);
                createRow(timeSlot, data.schedule.secondweek, secondWeekTableBody);
            });

            // Показуємо таблиці
            document.getElementById("schedule-section").style.display = "block";
            document.getElementById("groups-section").style.display = "none";
        })
        .catch((error) => console.error("Error fetching schedule:", error));
}

// Обробник для кнопки "Назад до факультетів"
document.getElementById("back-to-faculties").addEventListener("click", () => {
    fetchFaculties();
});

// Обробник для кнопки "Назад до курсів"
document.getElementById("back-to-courses").addEventListener("click", () => {
    loadCourses(currentFaculty);
});

// Обробник для кнопки "Назад до груп"
document.getElementById("back-to-groups").addEventListener("click", () => {
    loadGroups(currentFaculty, currentCourse);
});
