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

// --- Універсальна функція для отримання заголовків з токеном ---
function getAuthHeaders() {
    const token = localStorage.getItem("token");
    return {
        "Content-Type": "application/json",
        Authorization: `Bearer ${token}`,
    };
}

// --- Універсальна функція для відправки запитів ---
// sendRequest приймає:
//   method - HTTP-метод (GET, POST, PATCH, DELETE)
//   url - повна URL-адреса
//   data - (необов’язково) дані, які відправляються (буде перетворено у JSON)
function sendRequest(method, url, data = null) {
    const options = {
        method: method,
        headers: getAuthHeaders(),
    };
    if (data) {
        options.body = JSON.stringify(data);
    }
    return fetch(url, options).then((response) => {
        if (!response.ok) {
            if (response.status === 401) {
                window.location.href = "auth.html";
            }
            return response.json().then((json) => {
                throw new Error(json.message || "Unknown error");
            });
        }
        return response.json();
    });
}

// --- Універсальна функція для побудови таблиці ---
// tableSelector - CSS-селектор для <tbody>
// data - масив даних для побудови рядків
// rowGenerator - функція, яка приймає один елемент даних і повертає DOM-рядок (<tr>)
// extraRowGenerator - (опційно) функція, яка повертає останній рядок (наприклад, для створення нового елемента)
function buildTable(tableSelector, data, rowGenerator, extraRowGenerator = null) {
    const tbody = document.querySelector(tableSelector);
    tbody.innerHTML = "";
    data.forEach((item) => {
        tbody.appendChild(rowGenerator(item));
    });
    if (extraRowGenerator) {
        tbody.appendChild(extraRowGenerator());
    }
}

// Оновлення факультету (PATCH)
function updateFaculty(oldFacultyShortName, newName, newShortname) {
    sendRequest("PATCH", `http://127.0.0.1:8000/api/${oldFacultyShortName}`, { name: newName, shortname: newShortname })
        .then((data) => {
            console.log("Факультет оновлено:", data);
            fetchFaculties();
        })
        .catch((error) => console.error("Error updating faculty:", error));
}

// Створення факультету (POST)
function createFaculty(name, shortname) {
    sendRequest("POST", "http://127.0.0.1:8000/api/faculty/create", { name, shortname })
        .then((data) => {
            console.log("Факультет створено:", data);
            fetchFaculties();
        })
        .catch((error) => console.error("Error creating faculty:", error));
}

// Видалення факультету (DELETE)
function deleteFaculty(facultyShortName) {
    if (!confirm(`Ви впевнені, що хочете видалити факультет "${facultyShortName}"?`)) return;
    sendRequest("DELETE", `http://127.0.0.1:8000/api/${facultyShortName}`)
        .then((data) => {
            console.log("Факультет видалено:", data);
            fetchFaculties();
        })
        .catch((error) => console.error("Error deleting faculty:", error));
}

// Отримання факультетів (GET)
function fetchFaculties() {
    sendRequest("GET", "http://127.0.0.1:8000/api/faculties")
        .then((data) => {
            const rowGenerator = (faculty) => {
                const row = document.createElement("tr");

                // Назва факультету
                const nameCell = document.createElement("td");
                const nameInput = document.createElement("input");
                nameInput.value = faculty.name;
                nameInput.style.width = "95%";
                nameCell.appendChild(nameInput);
                row.appendChild(nameCell);

                // Коротка назва
                const shortnameCell = document.createElement("td");
                const shortnameInput = document.createElement("input");
                shortnameInput.value = faculty.shortname;
                shortnameInput.style.width = "95%";
                shortnameCell.appendChild(shortnameInput);
                row.appendChild(shortnameCell);

                // Дії (видалення, детальніше)
                const actionsCell = document.createElement("td");
                const detailsBtn = document.createElement("button");
                detailsBtn.textContent = "Детальніше";
                detailsBtn.style.marginLeft = "10px";
                detailsBtn.addEventListener("click", () => {
                    loadCourses(faculty.shortname);
                });
                actionsCell.appendChild(detailsBtn);

                const deleteBtn = document.createElement("button");
                deleteBtn.textContent = "Видалити";
                deleteBtn.style.backgroundColor = "#e74c3c";
                deleteBtn.style.color = "#fff";
                deleteBtn.style.border = "none";
                deleteBtn.style.padding = "5px 10px";
                deleteBtn.style.cursor = "pointer";
                deleteBtn.addEventListener("click", () => {
                    deleteFaculty(faculty.shortname);
                });
                actionsCell.appendChild(deleteBtn);

                row.appendChild(actionsCell);

                // Початкові значення для перевірки змін
                const originalName = faculty.name;
                const originalShortname = faculty.shortname;
                nameInput.addEventListener("blur", () => {
                    const newName = nameInput.value.trim();
                    const newShortname = shortnameInput.value.trim();
                    if (newName !== originalName || newShortname !== originalShortname) {
                        updateFaculty(originalShortname, newName, newShortname);
                    }
                });
                shortnameInput.addEventListener("blur", () => {
                    const newName = nameInput.value.trim();
                    const newShortname = shortnameInput.value.trim();
                    if (newName !== originalName || newShortname !== originalShortname) {
                        updateFaculty(originalShortname, newName, newShortname);
                    }
                });

                return row;
            };

            // Останній рядок для створення нового факультету
            const extraRow = () => {
                const row = document.createElement("tr");
                const cell = document.createElement("td");
                cell.colSpan = 3;
                cell.style.textAlign = "center";
                cell.style.padding = "10px";

                const newNameInput = document.createElement("input");
                newNameInput.placeholder = "Нова назва факультету";
                newNameInput.style.marginRight = "10px";
                const newShortnameInput = document.createElement("input");
                newShortnameInput.placeholder = "Нова коротка назва";
                newShortnameInput.style.marginRight = "10px";
                const createBtn = document.createElement("button");
                createBtn.textContent = "Створити факультет";
                createBtn.addEventListener("click", () => {
                    const newName = newNameInput.value.trim();
                    const newShortname = newShortnameInput.value.trim();
                    if (newName && newShortname) {
                        createFaculty(newName, newShortname);
                    } else {
                        alert("Заповніть обидва поля для створення нового факультету");
                    }
                });

                cell.appendChild(newNameInput);
                cell.appendChild(newShortnameInput);
                cell.appendChild(createBtn);
                row.appendChild(cell);
                return row;
            };

            // Будуємо таблицю факультетів
            buildTable("#faculties-table tbody", data.data, rowGenerator, extraRow);

            // Показуємо секцію факультетів, ховаємо інші
            document.getElementById("faculties-section").style.display = "block";
            document.getElementById("courses-section").style.display = "none";
            document.getElementById("groups-section").style.display = "none";
            document.getElementById("schedule-section").style.display = "none";
        })
        .catch((error) => console.error("Error fetching faculties:", error));
}

function updateCourse(facultyShortname, oldCourseName, newCourseName) {
    fetch(`http://127.0.0.1:8000/api/${facultyShortname}/${oldCourseName}`, {
        method: "PATCH",
        headers: getAuthHeaders(),
        body: JSON.stringify({
            name: newCourseName,
        }),
    })
        .then((response) => {
            if (!response.ok) {
                if (response.status === 401) {
                    window.location.href = "auth.html";
                }
                throw new Error("Помилка оновлення курсу");
            }
            return response.json();
        })
        .then((data) => {
            console.log("Курс оновлено:", data);
            // Оновлюємо список курсів для відображення змін
            loadCourses(facultyShortname);
        })
        .catch((error) => console.error("Error updating course:", error));
}

function updateCourse(facultyShortname, oldCourseName, newCourseName) {
    sendRequest("PATCH", `http://127.0.0.1:8000/api/${facultyShortname}/${oldCourseName}`, { name: newCourseName })
        .then((data) => {
            console.log("Курс оновлено:", data);
            loadCourses(facultyShortname);
        })
        .catch((error) => console.error("Error updating course:", error));
}

function createCourse(facultyShortname, courseName) {
    sendRequest("POST", `http://127.0.0.1:8000/api/${facultyShortname}/createCourse`, { name: courseName })
        .then((data) => {
            console.log("Курс створено:", data);
            loadCourses(facultyShortname);
        })
        .catch((error) => console.error("Error creating course:", error));
}

function deleteCourse(facultyShortname, courseName) {
    if (!confirm(`Ви впевнені, що хочете видалити курс "${courseName}"?`)) return;
    sendRequest("DELETE", `http://127.0.0.1:8000/api/${facultyShortname}/${courseName}`)
        .then((data) => {
            console.log("Курс видалено:", data);
            loadCourses(facultyShortname);
        })
        .catch((error) => console.error("Error deleting course:", error));
}

function loadCourses(facultyShortname) {
    currentFaculty = facultyShortname;
    sendRequest("GET", `http://127.0.0.1:8000/api/${facultyShortname}/courses`)
        .then((data) => {
            const rowGenerator = (courseName) => {
                const row = document.createElement("tr");
                const nameCell = document.createElement("td");
                const nameInput = document.createElement("input");
                nameInput.value = courseName;
                nameInput.style.width = "95%";
                nameCell.appendChild(nameInput);
                row.appendChild(nameCell);

                const actionsCell = document.createElement("td");
                const detailsBtn = document.createElement("button");
                detailsBtn.textContent = "Детальніше";
                detailsBtn.style.marginRight = "10px";
                detailsBtn.addEventListener("click", () => {
                    loadGroups(facultyShortname, courseName);
                });
                actionsCell.appendChild(detailsBtn);

                const deleteBtn = document.createElement("button");
                deleteBtn.textContent = "Видалити";
                deleteBtn.style.backgroundColor = "#e74c3c";
                deleteBtn.style.color = "#fff";
                deleteBtn.style.border = "none";
                deleteBtn.style.padding = "5px 10px";
                deleteBtn.style.cursor = "pointer";
                deleteBtn.addEventListener("click", () => {
                    deleteCourse(facultyShortname, courseName);
                });
                actionsCell.appendChild(deleteBtn);

                row.appendChild(actionsCell);

                const originalCourseName = courseName;
                nameInput.addEventListener("blur", () => {
                    const newCourseName = nameInput.value.trim();
                    if (newCourseName && newCourseName !== originalCourseName) {
                        updateCourse(facultyShortname, originalCourseName, newCourseName);
                    }
                });
                return row;
            };

            const extraRow = () => {
                const row = document.createElement("tr");
                const cell = document.createElement("td");
                cell.colSpan = 2;
                cell.style.textAlign = "center";
                cell.style.padding = "10px";

                const newCourseInput = document.createElement("input");
                newCourseInput.placeholder = "Нова назва курсу";
                newCourseInput.style.marginRight = "10px";
                const createBtn = document.createElement("button");
                createBtn.textContent = "Створити курс";
                createBtn.addEventListener("click", () => {
                    const newCourseName = newCourseInput.value.trim();
                    if (newCourseName) {
                        createCourse(facultyShortname, newCourseName);
                    } else {
                        alert("Введіть назву нового курсу");
                    }
                });
                cell.appendChild(newCourseInput);
                cell.appendChild(createBtn);
                row.appendChild(cell);
                return row;
            };

            buildTable("#courses-table tbody", data.data, rowGenerator, extraRow);
            document.getElementById("faculties-section").style.display = "none";
            document.getElementById("courses-section").style.display = "block";
            document.getElementById("groups-section").style.display = "none";
            document.getElementById("schedule-section").style.display = "none";
        })
        .catch((error) => console.error("Error fetching courses:", error));
}

// Оновлення назви групи (PATCH)
function updateGroupName(facultyName, courseName, oldGroupName, newGroupName) {
    sendRequest("PATCH", `http://127.0.0.1:8000/api/${facultyName}/${courseName}/${oldGroupName}`, { group_name: newGroupName })
        .then((data) => {
            console.log("Групу оновлено:", data);
            loadGroups(facultyName, courseName);
        })
        .catch((error) => console.error("Error updating group:", error));
}

// Створення групи (POST)
function createGroup(facultyName, courseName, groupName) {
    sendRequest("POST", `http://127.0.0.1:8000/api/${facultyName}/${courseName}/createGroup`, { group_name: groupName })
        .then((data) => {
            console.log("Групу створено:", data);
            loadGroups(facultyName, courseName);
        })
        .catch((error) => console.error("Error creating group:", error));
}

// Видалення групи (DELETE)
function deleteGroup(facultyName, courseName, groupName) {
    if (!confirm(`Ви впевнені, що хочете видалити групу "${groupName}"?`)) return;
    sendRequest("DELETE", `http://127.0.0.1:8000/api/${facultyName}/${courseName}/${groupName}`)
        .then((data) => {
            console.log("Групу видалено:", data);
            loadGroups(facultyName, courseName);
        })
        .catch((error) => console.error("Error deleting group:", error));
}

// Завантаження груп для вибраного курсу (GET)
function loadGroups(facultyName, courseName) {
    currentCourse = courseName;
    sendRequest("GET", `http://127.0.0.1:8000/api/${facultyName}/${courseName}/groups`)
        .then((data) => {
            const rowGenerator = (groupName) => {
                const row = document.createElement("tr");

                // Комірка з інпутом для назви групи
                const nameCell = document.createElement("td");
                const nameInput = document.createElement("input");
                nameInput.value = groupName;
                nameInput.style.width = "95%";
                nameCell.appendChild(nameInput);
                row.appendChild(nameCell);

                // Комірка з кнопками для дій: "Детальніше" і "Видалити"
                const actionsCell = document.createElement("td");

                // Кнопка "Детальніше" – завантаження розкладу для групи
                const detailsButton = document.createElement("button");
                detailsButton.textContent = "Детальніше";
                detailsButton.style.marginRight = "10px";
                detailsButton.addEventListener("click", () => {
                    loadSchedule(facultyName, courseName, groupName);
                });
                actionsCell.appendChild(detailsButton);

                // Кнопка "Видалити" – видалення групи
                const deleteButton = document.createElement("button");
                deleteButton.textContent = "Видалити";
                deleteButton.style.backgroundColor = "#e74c3c";
                deleteButton.style.color = "#fff";
                deleteButton.style.border = "none";
                deleteButton.style.padding = "5px 10px";
                deleteButton.style.cursor = "pointer";
                deleteButton.addEventListener("click", () => {
                    deleteGroup(facultyName, courseName, groupName);
                });
                actionsCell.appendChild(deleteButton);

                row.appendChild(actionsCell);

                // Зберігаємо початкове значення для перевірки змін
                const originalGroupName = groupName;
                nameInput.addEventListener("blur", () => {
                    const newGroupName = nameInput.value.trim();
                    if (newGroupName && newGroupName !== originalGroupName) {
                        updateGroupName(facultyName, courseName, originalGroupName, newGroupName);
                    }
                });

                return row;
            };

            // Функція для генерації останнього рядка (створення нової групи)
            const extraRow = () => {
                const row = document.createElement("tr");
                const cell = document.createElement("td");
                cell.colSpan = 2;
                cell.style.textAlign = "center";
                cell.style.padding = "10px";

                const newGroupInput = document.createElement("input");
                newGroupInput.placeholder = "Нова назва групи";
                newGroupInput.style.marginRight = "10px";
                const createBtn = document.createElement("button");
                createBtn.textContent = "Створити групу";
                createBtn.addEventListener("click", () => {
                    const newGroupName = newGroupInput.value.trim();
                    if (newGroupName) {
                        createGroup(facultyName, courseName, newGroupName);
                    } else {
                        alert("Введіть назву нової групи");
                    }
                });

                cell.appendChild(newGroupInput);
                cell.appendChild(createBtn);
                row.appendChild(cell);
                return row;
            };

            // Будуємо таблицю груп
            buildTable("#groups-table tbody", data.data, rowGenerator, extraRow);

            // Показуємо секцію груп, ховаючи інші
            document.getElementById("faculties-section").style.display = "none";
            document.getElementById("courses-section").style.display = "none";
            document.getElementById("groups-section").style.display = "block";
            document.getElementById("schedule-section").style.display = "none";
        })
        .catch((error) => console.error("Error fetching groups:", error));
}

// Функція для оновлення заняття (PATCH)
function updateClass(facultyName, courseName, groupName, week, day, time, discipline, teacher, auditory) {
    const url = `http://127.0.0.1:8000/api/${facultyName}/${courseName}/${groupName}/classes`;
    const dataPayload = { week, day, time, discipline, teacher, auditory };

    sendRequest("PATCH", url, dataPayload)
        .then((data) => {
            console.log("Заняття оновлено:", data);
            loadSchedule(facultyName, courseName, groupName);
        })
        .catch((error) => console.error("Error updating class:", error));
}

// Функція для створення заняття (POST)
// При створенні значення discipline, teacher, auditory можуть бути передані як порожній рядок
function createClass(facultyName, courseName, groupName, week, day, time, discipline, teacher, auditory) {
    const url = `http://127.0.0.1:8000/api/${facultyName}/${courseName}/${groupName}/classes`;
    const dataPayload = { week, day, time, discipline, teacher, auditory };

    sendRequest("POST", url, dataPayload)
        .then((data) => {
            console.log("Заняття створено:", data);
            loadSchedule(facultyName, courseName, groupName);
        })
        .catch((error) => console.error("Error creating class:", error));
}

// Функція для видалення заняття (DELETE)
function deleteClass(facultyName, courseName, groupName, week, day, time) {
    const url = `http://127.0.0.1:8000/api/${facultyName}/${courseName}/${groupName}/classes`;
    const dataPayload = { week, day, time };

    sendRequest("DELETE", url, dataPayload)
        .then((data) => {
            console.log("Заняття видалено:", data);
            loadSchedule(facultyName, courseName, groupName);
        })
        .catch((error) => console.error("Error deleting class:", error));
}

// Функція для створення одного рядка розкладу (для одного часовго слоту)
// Параметри:
// - timeSlot: рядок з часом (наприклад, "8:30-9:50")
// - weekSchedule: об'єкт розкладу для конкретного тижня (firstweek або secondweek)
// - tableBody: елемент <tbody>, куди додається рядок
// - weekName: "firstweek" або "secondweek"
// - facultyName, courseName, groupName: ідентифікатори для формування запитів
function createRow(timeSlot, weekSchedule, tableBody, weekName, facultyName, courseName, groupName) {
    const row = document.createElement("tr");
    // Перша клітинка – час
    const timeCell = document.createElement("td");
    timeCell.textContent = timeSlot;
    row.appendChild(timeCell);

    // Для кожного дня (понеділок - субота)
    const days = ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday"];
    days.forEach((day) => {
        const cell = document.createElement("td");
        // Отримуємо розклад для конкретного дня (якщо він є)
        let scheduleForDay = weekSchedule && weekSchedule[day];
        let scheduleInfo = null;
        if (scheduleForDay) {
            // Шукаємо інформацію для поточного часового слоту
            scheduleInfo = scheduleForDay.find((item) => item.time === timeSlot);
        }

        if (scheduleInfo) {
            // Якщо заняття знайдено – створюємо блок з трьома інпутами та мітками
            const container = document.createElement("div");
            container.style.border = "1px solid #ccc";
            container.style.padding = "5px";
            container.style.marginBottom = "5px";

            // Дисципліна
            const disciplineLabel = document.createElement("label");
            disciplineLabel.textContent = "Дисципліна:";
            disciplineLabel.style.display = "block";
            const disciplineInput = document.createElement("input");
            disciplineInput.value = scheduleInfo.discipline || "";
            disciplineInput.style.width = "90%";
            disciplineInput.style.display = "block";
            container.appendChild(disciplineLabel);
            container.appendChild(disciplineInput);

            // Викладач
            const teacherLabel = document.createElement("label");
            teacherLabel.textContent = "Викладач:";
            teacherLabel.style.display = "block";
            const teacherInput = document.createElement("input");
            teacherInput.value = scheduleInfo.teacher || "";
            teacherInput.style.width = "90%";
            teacherInput.style.display = "block";
            container.appendChild(teacherLabel);
            container.appendChild(teacherInput);

            // Аудиторія
            const auditoryLabel = document.createElement("label");
            auditoryLabel.textContent = "Аудиторія:";
            auditoryLabel.style.display = "block";
            const auditoryInput = document.createElement("input");
            auditoryInput.value = scheduleInfo.auditory || "";
            auditoryInput.style.width = "90%";
            auditoryInput.style.display = "block";
            container.appendChild(auditoryLabel);
            container.appendChild(auditoryInput);

            // При зміні будь-якого інпуту – відправляємо PATCH‑запит для оновлення заняття
            [disciplineInput, teacherInput, auditoryInput].forEach((input) => {
                input.addEventListener("blur", () => {
                    updateClass(
                        facultyName,
                        courseName,
                        groupName,
                        weekName,
                        day,
                        timeSlot,
                        disciplineInput.value,
                        teacherInput.value,
                        auditoryInput.value
                    );
                });
            });

            // Кнопка для видалення заняття
            const deleteButton = document.createElement("button");
            deleteButton.textContent = "Видалити заняття";
            deleteButton.style.marginTop = "5px";
            deleteButton.style.backgroundColor = "rgb(231, 76, 60)";
            deleteButton.addEventListener("click", () => {
                deleteClass(facultyName, courseName, groupName, weekName, day, timeSlot);
            });
            container.appendChild(deleteButton);

            cell.appendChild(container);
        } else {
            const addButton = document.createElement("button");
            addButton.textContent = "Додати заняття";
            addButton.addEventListener("click", () => {
                createClass(facultyName, courseName, groupName, weekName, day, timeSlot, "", "", "");
            });
            cell.appendChild(addButton);
        }

        row.appendChild(cell);
    });

    tableBody.appendChild(row);
}

// Функція для отримання розкладу групи
// Функція для завантаження розкладу групи
// Приклад очікує, що API повертає об'єкт виду:
// {
//   schedule: {
//       firstweek: { monday: [...], tuesday: [...], ... },
//       secondweek: { monday: [...], tuesday: [...], ... }
//   }
// }
function loadSchedule(facultyName, courseName, groupName) {
    currentGroup = groupName;
    fetch(`http://127.0.0.1:8000/api/schedule/${facultyName}/${courseName}/${groupName}/`, {
        method: "GET",
        headers: getAuthHeaders(),
    })
        .then((response) => {
            if (!response.ok) {
                if (response.status === 401) {
                    window.location.href = "auth.html";
                }
            }
            return response.json();
        })
        .then((data) => {
            const firstWeekTableBody = document.querySelector("#first-week-schedule tbody");
            const secondWeekTableBody = document.querySelector("#second-week-schedule tbody");
            // Очищаємо таблиці перед заповненням
            firstWeekTableBody.innerHTML = "";
            secondWeekTableBody.innerHTML = "";

            const timeSlots = ["8:30-9:50", "10:00-11:20", "11:40-13:00", "13:30-14:50", "15:00-16:20", "16:30-17:50", "18:00-19:20"];

            // Для кожного часового слоту створюємо рядки для першого та другого тижнів
            timeSlots.forEach((timeSlot) => {
                createRow(timeSlot, data.schedule.firstweek, firstWeekTableBody, "firstweek", facultyName, courseName, groupName);
                createRow(timeSlot, data.schedule.secondweek, secondWeekTableBody, "secondweek", facultyName, courseName, groupName);
            });

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
