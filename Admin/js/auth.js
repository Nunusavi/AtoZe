document.getElementById("login-form").addEventListener("submit", async (e) => {
    e.preventDefault();

    const username = document.getElementById("username").value.trim();
    const password = document.getElementById("password").value;

    try {
        const res = await fetch("/Admin/api/login", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ username, password })
        });

        if (res.status === 200) {
            const { token } = await res.json();
            localStorage.setItem("session_token", token);
            showToast("Login successful", "success"); // Green toast for success
            window.location.href = "/Admin/dashboard.html";
        } else {
            const errorData = await res.json();
            showToast(errorData.message || "Invalid credentials", "error"); // Red toast for errors
        }
    } catch (err) {
        showToast("An error occurred. Please try again.", "error"); // Red toast for unexpected errors
    }
});
