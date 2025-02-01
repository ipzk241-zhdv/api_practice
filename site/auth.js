// Режим формы: 'login' или 'register'
let mode = "login";

document.addEventListener("DOMContentLoaded", () => {
    const loginTab = document.getElementById("login-tab");
    const registerTab = document.getElementById("register-tab");
    const formTitle = document.getElementById("form-title");
    const confirmPasswordGroup = document.getElementById("confirm-password-group");
    const submitButton = document.getElementById("submit-button");
    const authForm = document.getElementById("auth-form");
    const messageDiv = document.getElementById("message");

    // Переключатель режимів
    loginTab.addEventListener("click", () => {
        mode = "login";
        loginTab.classList.add("active");
        registerTab.classList.remove("active");
        formTitle.textContent = "Вхід";
        submitButton.textContent = "Увійти";
        confirmPasswordGroup.style.display = "none";
        messageDiv.textContent = "";
        console.log(confirmPasswordGroup);
    });

    registerTab.addEventListener("click", () => {
        mode = "register";
        registerTab.classList.add("active");
        loginTab.classList.remove("active");
        formTitle.textContent = "Реєстрація";
        submitButton.textContent = "Зареєструватися";
        confirmPasswordGroup.style.display = "block";
        messageDiv.textContent = "";
    });

    authForm.addEventListener("submit", async (event) => {
        event.preventDefault(); // відміняємо стандартну поведінку форми

        // Отримуємо дані з форми
        const login = document.getElementById("login").value.trim();
        const password = document.getElementById("password").value.trim();

        // Для реєстрації перевіряємо підтвердження пароля
        let confirmPassword = null;
        if (mode === "register") {
            confirmPassword = document.getElementById("confirm-password").value.trim();
            if (password !== confirmPassword) {
                messageDiv.textContent = "Паролі не співпадають";
                return;
            }
        }

        // Формуємо дані для запиту
        const payload = {
            username: login,
            password: password,
        };

        // Визначаємо URL запиту в залежності від режиму
        let url = "";
        if (mode === "login") {
            url = "http://127.0.0.1:8000/api/login";
        } else if (mode === "register") {
            url = "http://127.0.0.1:8000/api/register";
        }

        try {
            const response = await fetch(url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            if (response.ok) {
                // Очікуємо, що API поверне JWT-токен
                const token = data.token;
                localStorage.setItem("token", token);
                // Перенаправляємо на головну сторінку (наприклад, index.html)
                window.location.href = "index.html";
            } else {
                messageDiv.textContent = data.error || "Сталася помилка. Спробуйте ще раз.";
            }
        } catch (error) {
            console.error("Помилка запиту:", error);
            messageDiv.textContent = "Помилка з'єднання з сервером";
        }
    });
});
