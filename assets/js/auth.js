// Authentication JavaScript
document.addEventListener("DOMContentLoaded", function () {
  // Login Form Handler
  const loginForm = document.getElementById("loginForm");
  if (loginForm) {
    loginForm.addEventListener("submit", handleLogin);
  }

  // Forgot Password Link
  const forgotPasswordLink = document.getElementById("forgotPasswordLink");
  if (forgotPasswordLink) {
    forgotPasswordLink.addEventListener("click", function (e) {
      e.preventDefault();
      openForgotPasswordModal();
    });
  }

  // Forgot Password Form
  const forgotPasswordForm = document.getElementById("forgotPasswordForm");
  if (forgotPasswordForm) {
    forgotPasswordForm.addEventListener("submit", handleForgotPassword);
  }

  // Email validation on blur
  const emailInput = document.getElementById("email");
  if (emailInput) {
    emailInput.addEventListener("blur", validateEmail);
  }

  // Password validation on blur
  const passwordInput = document.getElementById("password");
  if (passwordInput) {
    passwordInput.addEventListener("blur", validatePassword);
  }
});

// Handle Login
async function handleLogin(e) {
  e.preventDefault();

  const email = document.getElementById("email").value;
  const password = document.getElementById("password").value;
  const loginBtn = e.target.querySelector('button[type="submit"]');
  const loginBtnText = document.getElementById("loginBtnText");
  const loginSpinner = document.getElementById("loginSpinner");

  // Validate
  if (!validateEmail() || !validatePassword()) {
    return;
  }

  // Show loading
  loginBtn.disabled = true;
  loginBtnText.style.display = "none";
  loginSpinner.style.display = "block";

  try {
    const response = await fetch("api/auth.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        action: "login",
        email: email,
        password: password,
      }),
    });

    const data = await response.json();

    if (data.success) {
      // Check if user is approved
      if (!data.user.is_approved && data.user.role !== "admin") {
        showError(
          "Your account is pending admin approval. Please wait for approval before logging in.",
        );
        loginBtn.disabled = false;
        loginBtnText.style.display = "block";
        loginSpinner.style.display = "none";
        return;
      }

      // Redirect based on role
      if (data.user.role === "admin") {
        window.location.href = "admin/index.php";
      } else {
        window.location.href = "dashboard/newsfeed.php";
      }
    } else {
      showError(data.message || "Login failed. Please check your credentials.");
      loginBtn.disabled = false;
      loginBtnText.style.display = "block";
      loginSpinner.style.display = "none";
    }
  } catch (error) {
    console.error("Login error:", error);
    showError("An error occurred. Please try again.");
    loginBtn.disabled = false;
    loginBtnText.style.display = "block";
    loginSpinner.style.display = "none";
  }
}

// Validate Email
function validateEmail() {
  const emailInput = document.getElementById("email");
  const emailError = document.getElementById("emailError");
  const email = emailInput.value.trim();

  if (!email) {
    emailInput.classList.add("is-invalid");
    emailError.textContent = "Email is required";
    return false;
  }

  // Allow admin@gmail.com for admin login
  if (email === "admin@gmail.com") {
    emailInput.classList.remove("is-invalid");
    emailInput.classList.add("is-valid");
    emailError.textContent = "";
    return true;
  }

  // Validate UIU email format
  const uiuEmailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.uiu\.ac\.bd$/;
  if (!uiuEmailRegex.test(email)) {
    emailInput.classList.add("is-invalid");
    emailError.textContent =
      "Email must be in UIU format: username@domain.uiu.ac.bd";
    return false;
  }

  emailInput.classList.remove("is-invalid");
  emailInput.classList.add("is-valid");
  emailError.textContent = "";
  return true;
}

// Validate Password
function validatePassword() {
  const passwordInput = document.getElementById("password");
  const passwordError = document.getElementById("passwordError");
  const password = passwordInput.value;

  if (!password) {
    passwordInput.classList.add("is-invalid");
    passwordError.textContent = "Password is required";
    return false;
  }

  if (password.length < 6) {
    passwordInput.classList.add("is-invalid");
    passwordError.textContent = "Password must be at least 6 characters";
    return false;
  }

  passwordInput.classList.remove("is-invalid");
  passwordInput.classList.add("is-valid");
  passwordError.textContent = "";
  return true;
}

// Show Error Message
function showError(message) {
  const errorMessage = document.getElementById("errorMessage");
  const errorText = document.getElementById("errorText");

  errorText.textContent = message;
  errorMessage.style.display = "flex";
  errorMessage.classList.add("animate-shake");

  setTimeout(() => {
    errorMessage.classList.remove("animate-shake");
  }, 500);

  // Auto-hide after 5 seconds
  setTimeout(() => {
    errorMessage.style.display = "none";
  }, 5000);
}

// Open Forgot Password Modal
function openForgotPasswordModal() {
  const modal = document.getElementById("forgotPasswordModal");
  modal.classList.add("active");
  document.body.style.overflow = "hidden";
}

// Close Forgot Password Modal
function closeForgotPasswordModal() {
  const modal = document.getElementById("forgotPasswordModal");
  modal.classList.remove("active");
  document.body.style.overflow = "auto";

  // Reset form
  document.getElementById("forgotPasswordForm").reset();
  const resetEmail = document.getElementById("resetEmail");
  resetEmail.classList.remove("is-invalid", "is-valid");
  document.getElementById("resetEmailError").textContent = "";
}

// Handle Forgot Password
async function handleForgotPassword(e) {
  e.preventDefault();

  const resetEmail = document.getElementById("resetEmail").value.trim();
  const resetEmailError = document.getElementById("resetEmailError");
  const resetEmailInput = document.getElementById("resetEmail");
  const submitBtn = e.target.querySelector('button[type="submit"]');

  // Validate email
  if (!resetEmail) {
    resetEmailInput.classList.add("is-invalid");
    resetEmailError.textContent = "Email is required";
    return;
  }

  const uiuEmailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.uiu\.ac\.bd$/;
  if (!uiuEmailRegex.test(resetEmail)) {
    resetEmailInput.classList.add("is-invalid");
    resetEmailError.textContent =
      "Email must be in UIU format: username@domain.uiu.ac.bd";
    return;
  }

  resetEmailInput.classList.remove("is-invalid");
  resetEmailInput.classList.add("is-valid");
  resetEmailError.textContent = "";

  // Show loading
  submitBtn.disabled = true;
  submitBtn.textContent = "Sending...";

  try {
    const response = await fetch("api/auth.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        action: "forgot_password",
        email: resetEmail,
      }),
    });

    const data = await response.json();

    if (data.success) {
      // Show success message
      alert("Password reset link sent! Check your email for instructions.");
      closeForgotPasswordModal();
    } else {
      resetEmailInput.classList.add("is-invalid");
      resetEmailError.textContent = data.message || "Failed to send reset link";
    }
  } catch (error) {
    console.error("Forgot password error:", error);
    resetEmailInput.classList.add("is-invalid");
    resetEmailError.textContent = "An error occurred. Please try again.";
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = "Send Reset Link";
  }
}
