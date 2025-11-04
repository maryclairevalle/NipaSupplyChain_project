<?php // index.php ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Nipa Supply Chain Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://rsms.me/" />
  <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
  <style>
    :root { font-family: 'Inter', sans-serif; }
    @supports (font-variation-settings: normal) {
      :root { font-family: 'Inter var', sans-serif; }
    }
    .nipa-background {
      background-image: url('https://img.freepik.com/premium-photo/leaves-leaf-logo-design-vector-black-background-picture-ai-generated-art_853163-6107.jpg?w=740');
      background-repeat: repeat;
      background-color: #0f172a;
      opacity: 0.95;
    }
    .nipa-panel { background-color: #0f172a; }
    .modal-enter { transform: scale(0.95); opacity: 0; transition: all 0.2s ease-out; }
    .modal-enter-active { transform: scale(1); opacity: 1; }
    .modal-exit-active { transform: scale(0.95); opacity: 0; transition: all 0.15s ease-in; }
  </style>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
  <div class="flex flex-col lg:flex-row w-full max-w-6xl mx-auto rounded-3xl shadow-2xl overflow-hidden">
    
    <!-- LEFT PANEL -->
    <div class="p-8 lg:p-16 flex flex-col justify-top items-top text-center lg:w-1/2 min-h-60 lg:min-h-[700px] bg-slate-900 nipa-background nipa-panel">
      <h1 class="text-4xl font-extrabold text-white mb-2">Nipa Supply Chain</h1>
      <p class="text-violet-200 text-lg font-light italic">Your product tracker from harvest to market.</p>
    </div>

    <!-- RIGHT PANEL -->
    <div id="authContainer" class="p-8 sm:p-12 lg:p-16 flex flex-col justify-center lg:w-1/2 bg-white">
      
      <!-- SIGN IN -->
      <div id="signInView">
        <h2 class="text-3xl font-bold text-gray-800 mb-2">Welcome Back</h2>
        <p class="text-gray-500 mb-8">Sign in to access your supply chain dashboard.</p>

        <form id="loginForm" class="space-y-6" method="POST" action="login.php">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
            <input type="email" name="email" required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-violet-500 focus:border-violet-500 transition"
              placeholder="Enter your email">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <input type="password" name="password" required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-violet-500 focus:border-violet-500 transition"
              placeholder="Enter your password">
          </div>
          <button type="submit"
            class="w-full py-3 px-4 rounded-lg text-lg font-semibold text-white bg-violet-600 hover:bg-violet-700 transition">
            Sign In
          </button>
        </form>

        <div class="mt-4 text-center text-sm text-gray-500">
          Don’t have an account?
          <button id="switchToSignUp" class="font-semibold text-violet-600 hover:text-violet-500 focus:outline-none">Sign up here</button>
        </div>
      </div>

      <!-- SIGN UP -->
      <div id="signUpView" class="hidden">
        <h2 class="text-3xl font-bold text-gray-800 mb-2">Create Account</h2>
        <p class="text-gray-500 mb-8">Register to start tracking your supply chain.</p>

        <form id="signupForm" class="space-y-6" method="POST" action="signup.php">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
            <input type="email" name="email" required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-violet-500 focus:border-violet-500 transition"
              placeholder="Email Address">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <input type="password" name="password" required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-violet-500 focus:border-violet-500 transition"
              placeholder="••••••••">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
            <input type="password" name="confirmPassword" required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-violet-500 focus:border-violet-500 transition"
              placeholder="••••••••">
          </div>
          <button type="submit"
            class="w-full py-3 px-4 rounded-lg text-lg font-semibold text-white bg-green-600 hover:bg-green-700 transition">
            Sign Up
          </button>
        </form>

        <div class="mt-6 text-center text-sm text-gray-500">
          Already have an account?
          <button id="switchToSignIn" class="font-semibold text-violet-600 hover:text-violet-500 focus:outline-none">Sign in</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ALERT MODAL -->
  <div id="alertModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div id="alertBox" class="bg-white rounded-2xl shadow-2xl w-11/12 max-w-md p-6 text-center transform transition-all modal-enter">
      <div id="alertIcon" class="mx-auto mb-4 w-16 h-16 flex items-center justify-center rounded-full bg-gray-200 text-3xl">ℹ️</div>
      <h3 id="alertTitle" class="text-2xl font-bold text-gray-800 mb-2">Alert</h3>
      <p id="alertMessage" class="text-gray-600 mb-6">This is an alert message.</p>
      <button id="closeAlertBtn"
        class="px-6 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-lg focus:outline-none transition">
        OK
      </button>
    </div>
  </div>

  <script>
    // Toggle between Sign In and Sign Up
    document.addEventListener('DOMContentLoaded', function() {
      const signInView = document.getElementById('signInView');
      const signUpView = document.getElementById('signUpView');
      document.getElementById('switchToSignUp').addEventListener('click', () => {
        signInView.classList.add('hidden');
        signUpView.classList.remove('hidden');
      });
      document.getElementById('switchToSignIn').addEventListener('click', () => {
        signUpView.classList.add('hidden');
        signInView.classList.remove('hidden');
      });
    });

    // Modal logic
    function showAlert(type, title, message) {
      const modal = document.getElementById('alertModal');
      const box = document.getElementById('alertBox');
      const icon = document.getElementById('alertIcon');
      const alertTitle = document.getElementById('alertTitle');
      const alertMessage = document.getElementById('alertMessage');
      const colors = {
        success: 'bg-green-100 text-green-600',
        error: 'bg-red-100 text-red-600',
        warning: 'bg-yellow-100 text-yellow-600',
        info: 'bg-blue-100 text-blue-600'
      };
      const icons = { success: '✔️', error: '❌', warning: '⚠️', info: 'ℹ️' };
      icon.className = `mx-auto mb-4 w-16 h-16 flex items-center justify-center rounded-full text-3xl ${colors[type] || colors.info}`;
      icon.textContent = icons[type] || icons.info;
      alertTitle.textContent = title;
      alertMessage.textContent = message;
      modal.classList.remove('hidden');
      setTimeout(() => box.classList.add('modal-enter-active'), 10);
    }

    document.getElementById('closeAlertBtn').addEventListener('click', () => {
      const modal = document.getElementById('alertModal');
      const box = document.getElementById('alertBox');
      box.classList.remove('modal-enter-active');
      box.classList.add('modal-exit-active');
      setTimeout(() => {
        modal.classList.add('hidden');
        box.classList.remove('modal-exit-active');
      }, 150);
    });
  </script>

  <!-- Auto trigger alerts based on PHP query params -->
  <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid'): ?>
    <script>document.addEventListener('DOMContentLoaded',()=>showAlert('error','Login Failed','Invalid email or password.'));</script>
  <?php elseif (isset($_GET['registered'])): ?>
    <script>document.addEventListener('DOMContentLoaded',()=>showAlert('success','Registration Successful','You can now log in with your new account.'));</script>
  <?php elseif (isset($_GET['error']) && $_GET['error'] === 'email'): ?>
    <script>document.addEventListener('DOMContentLoaded',()=>showAlert('warning','Email Already Registered','This email is already in use.'));</script>
  <?php elseif (isset($_GET['error']) && $_GET['error'] === 'password'): ?>
    <script>document.addEventListener('DOMContentLoaded',()=>showAlert('warning','Password Mismatch','Passwords do not match.'));</script>
  <?php endif; ?>
</body>
</html>
