<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — ASCOT BSIT Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Home Page Color Scheme */
        :root {
            --brand: #0f1724;        /* dark navy */
            --accent: #0ea5a4;       /* teal */
            --accent-light: #38bdf8; /* light teal/blue */
            --muted: #6b7280;        /* gray */
            --light-gray: #eef2f6;
            --card: #ffffff;
            --glass: rgba(255, 255, 255, 0.85);
            --border: rgba(15, 23, 36, 0.08);
            --shadow: rgba(15, 23, 36, 0.1);
            --radius: 16px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(180deg, #f3f6f9 0%, var(--light-gray) 100%);
            color: var(--brand);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            line-height: 1.45;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 40px;
        }

        /* Header matching home page */
        .site-header {
            background: var(--brand);
            color: white;
            padding: 12px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            width: 100%;
            max-width: 500px;
            border-radius: 12px 12px 0 0;
            box-shadow: 0 4px 12px var(--shadow);
            margin-bottom: 0;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand img {
            height: 42px;
            width: auto;
            border-radius: 6px;
        }

        .brand h1 {
            font-size: 18px;
            letter-spacing: 0.2px;
            margin: 0;
        }

        .brand .subtitle {
            font-size: 12px;
            color: rgba(255,255,255,0.8);
            margin-top: 3px;
        }

        /* Main Container */
        .register-container {
            width: 100%;
            max-width: 500px;
            margin-top: 0;
        }

        .register-card {
            background: var(--glass);
            border-radius: 0 0 var(--radius) var(--radius);
            padding: 48px 40px;
            box-shadow: 0 12px 40px var(--shadow);
            border: 1px solid var(--border);
            border-top: none;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            background-clip: padding-box;
        }

        /* Decorative top accent */
        .register-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent), var(--accent-light));
            border-radius: var(--radius) var(--radius) 0 0;
            z-index: 1;
        }

        .register-title {
            text-align: center;
            margin-bottom: 32px;
        }

        .kicker {
            background: linear-gradient(90deg, var(--accent), var(--accent-light));
            color: white;
            padding: 8px 16px;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.2px;
            box-shadow: 0 6px 18px rgba(14, 165, 164, 0.2);
            width: fit-content;
            margin: 0 auto 20px;
            text-transform: uppercase;
        }

        .register-title h2 {
            font-size: 28px;
            margin-bottom: 12px;
            color: var(--brand);
            line-height: 1.3;
        }

        .register-title p {
            color: var(--muted);
            font-size: 16px;
            line-height: 1.6;
        }

        /* Form Styles */
        .register-form {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-label {
            color: var(--brand);
            font-weight: 500;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-label.required::after {
            content: "*";
            color: #ef4444;
            margin-left: 4px;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            background: white;
            transition: all 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(14, 165, 164, 0.1);
        }

        .form-control.error {
            border-color: #ef4444;
            background: #fef2f2;
        }

        .form-hint {
            color: var(--muted);
            font-size: 13px;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-hint.error {
            color: #ef4444;
        }

        /* Password Strength */
        .password-strength {
            margin-top: 8px;
        }

        .strength-meter {
            height: 6px;
            background: #e5e7eb;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 6px;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            border-radius: 3px;
            transition: all 0.3s ease;
        }

        .strength-weak {
            background: #ef4444;
            width: 33%;
        }

        .strength-medium {
            background: #f59e0b;
            width: 66%;
        }

        .strength-strong {
            background: #10b981;
            width: 100%;
        }

        .strength-label {
            font-size: 12px;
            color: var(--muted);
            text-align: right;
        }

        /* Terms Checkbox */
        .terms-group {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 16px;
            background: rgba(14, 165, 164, 0.05);
            border-radius: 10px;
            border: 1px solid rgba(14, 165, 164, 0.1);
            margin-top: 8px;
        }

        .terms-group input[type="checkbox"] {
            margin-top: 2px;
            accent-color: var(--accent);
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .terms-group label {
            font-size: 14px;
            color: var(--muted);
            line-height: 1.5;
            cursor: pointer;
        }

        .terms-group a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
        }

        .terms-group a:hover {
            text-decoration: underline;
        }

        /* Buttons */
        .form-actions {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-top: 32px;
        }

        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 16px 24px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
            border: none;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--brand) 0%, #1a2436 100%);
            color: white;
            box-shadow: 0 8px 24px rgba(15, 23, 36, 0.15);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(15, 23, 36, 0.2);
            background: linear-gradient(135deg, #1a2436 0%, var(--brand) 100%);
        }

        .btn-outline {
            background: transparent;
            color: var(--brand);
            border: 1px solid var(--border);
        }

        .btn-outline:hover {
            background: rgba(15, 23, 36, 0.03);
        }

        /* Messages */
        .message-alert {
            background: rgba(14, 165, 164, 0.1);
            border: 1px solid rgba(14, 165, 164, 0.2);
            color: var(--accent);
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error-alert {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #991b1b;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
        }

        .success-alert {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.2);
            color: #166534;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
        }

        /* Login Link */
        .login-link {
            text-align: center;
            color: var(--muted);
            font-size: 14px;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid var(--border);
        }

        .login-link a {
            color: var(--accent);
            font-weight: 600;
            text-decoration: none;
            margin-left: 8px;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 24px 16px;
                padding-top: 24px;
            }
            
            .register-card {
                padding: 40px 32px;
            }
            
            .site-header {
                max-width: 100%;
                flex-direction: column;
                text-align: center;
                padding: 16px;
            }
            
            .brand h1 {
                font-size: 16px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 24px;
            }
            
            .lastname-row {
                grid-template-columns: 1fr;
                gap: 16px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 16px 12px;
                padding-top: 16px;
            }
            
            .register-card {
                padding: 32px 24px;
            }
            
            .register-title h2 {
                font-size: 24px;
            }
            
            .btn {
                padding: 14px 20px;
                font-size: 15px;
            }
            
            .terms-group {
                padding: 12px;
            }
        }

        /* Simplified Password toggle */
        .password-wrapper {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--muted);
            cursor: pointer;
            padding: 4px 8px;
            font-size: 14px;
            font-weight: 500;
        }

        .toggle-password:hover {
            color: var(--accent);
        }

        /* Last name row with extension */
        .lastname-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 16px;
        }
        
        .extension-input .form-control {
            padding: 14px 12px;
        }
    </style>

    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const toggle = document.querySelector(`[onclick="togglePassword('${fieldId}')"]`);
            
            if (field.type === 'password') {
                field.type = 'text';
                toggle.textContent = 'Hide';
            } else {
                field.type = 'password';
                toggle.textContent = 'Show';
            }
        }

        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthMeter = document.getElementById('strength-meter');
            const strengthLabel = document.getElementById('strength-label');
            
            let strength = 0;
            let label = 'Very Weak';
            let color = '#ef4444';
            let width = '0%';
            let className = '';
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            switch(strength) {
                case 0:
                case 1:
                    label = 'Very Weak';
                    color = '#ef4444';
                    width = '20%';
                    className = 'strength-weak';
                    break;
                case 2:
                    label = 'Weak';
                    color = '#ef4444';
                    width = '33%';
                    className = 'strength-weak';
                    break;
                case 3:
                    label = 'Medium';
                    color = '#f59e0b';
                    width = '66%';
                    className = 'strength-medium';
                    break;
                case 4:
                    label = 'Strong';
                    color = '#10b981';
                    width = '90%';
                    className = 'strength-strong';
                    break;
                case 5:
                    label = 'Very Strong';
                    color = '#10b981';
                    width = '100%';
                    className = 'strength-strong';
                    break;
            }
            
            strengthMeter.innerHTML = `<div class="strength-fill ${className}" style="width: ${width}; background: ${color};"></div>`;
            strengthLabel.textContent = label;
            strengthLabel.style.color = color;
        }

        function validateForm() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const terms = document.getElementById('terms');
            
            let isValid = true;
            
            // Check password match
            if (password !== confirmPassword) {
                document.getElementById('confirm-password-error').style.display = 'block';
                document.getElementById('confirm_password').classList.add('error');
                isValid = false;
            } else {
                document.getElementById('confirm-password-error').style.display = 'none';
                document.getElementById('confirm_password').classList.remove('error');
            }
            
            // Check terms
            if (!terms.checked) {
                document.getElementById('terms-error').style.display = 'block';
                isValid = false;
            } else {
                document.getElementById('terms-error').style.display = 'none';
            }
            
            return isValid;
        }
    </script>
</head>
<body>
    <!-- Header matching home page -->
    <div class="site-header">
        <div class="brand">
            <img src="ascot3.png" alt="ASCOT logo">
            <div>
                <h1>ASCOT — BSIT Enrollment Portal</h1>
                <div class="subtitle">Aurora State College of Technology</div>
            </div>
        </div>
        <a href="StudentLogin.php" style="color:white; text-decoration:none; padding:8px 16px; background:rgba(255,255,255,0.1); border-radius:8px; font-size:14px;">
            ← Back
        </a>
    </div>

    <!-- Registration Form -->
    <div class="register-container">
        <div class="register-card">
            <div class="register-title">
                <div class="kicker">Student Registration</div>
                <h2>Create Your Account</h2>
                <p>Create your student account to access enrollment, messages, and academic resources.</p>
            </div>

            <!-- Messages -->
            <?php if (!empty($_SESSION['register_error'])): ?>
                <div class="error-alert">
                    ⚠️ <?php echo htmlspecialchars($_SESSION['register_error']); ?>
                </div>
                <?php unset($_SESSION['register_error']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['register_success'])): ?>
    <div class="success-alert" style="padding: 20px; line-height: 1.6;">
        <?php echo $_SESSION['register_success']; ?>
    </div>
    <?php unset($_SESSION['register_success']); ?>
<?php endif; ?>

            <form class="register-form" action="register_action.php" method="POST" onsubmit="return validateForm()">
                <!-- First Name -->
                <div class="form-group">
                    <label class="form-label required">
                        <span>👤</span> First Name
                    </label>
                    <input type="text" name="first_name" class="form-control" 
                           placeholder="First Name" required
                           value="<?php echo !empty($_SESSION['form_data']['first_name']) ? htmlspecialchars($_SESSION['form_data']['first_name']) : ''; ?>">
                </div>
                
                <!-- Middle Name -->
                <div class="form-group">
                    <label class="form-label">
                        <span>👤</span> Middle Name
                    </label>
                    <input type="text" name="middle_name" class="form-control" 
                           placeholder="Middle Name"
                           value="<?php echo !empty($_SESSION['form_data']['middle_name']) ? htmlspecialchars($_SESSION['form_data']['middle_name']) : ''; ?>">
                </div>
                
                <!-- Last Name with Extension in same row -->
                <div class="lastname-row">
                    <div class="form-group">
                        <label class="form-label required">
                            <span>👤</span> Last Name
                        </label>
                        <input type="text" name="last_name" class="form-control" 
                               placeholder="Last Name" required
                               value="<?php echo !empty($_SESSION['form_data']['last_name']) ? htmlspecialchars($_SESSION['form_data']['last_name']) : ''; ?>">
                        <div class="form-hint">Your family name</div>
                    </div>
                    
                    <div class="form-group extension-input">
                        <label class="form-label">
                            <span>👤</span> Extension
                        </label>
                        <input type="text" name="extension_name" class="form-control" 
                               placeholder="e.g., Jr., Sr., III"
                               value="<?php echo !empty($_SESSION['form_data']['extension_name']) ? htmlspecialchars($_SESSION['form_data']['extension_name']) : ''; ?>">
                    </div>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label class="form-label required">
                        <span>✉️</span> Email Address
                    </label>
                    <input type="email" name="email" class="form-control" 
                           placeholder="example@email.com" required
                           value="<?php echo !empty($_SESSION['form_data']['email']) ? htmlspecialchars($_SESSION['form_data']['email']) : ''; ?>">
                    <div class="form-hint">Use your active email for verification</div>
                </div>

                <!-- Student ID (Optional) -->
<div class="form-group">
    <label class="form-label">
        <span>🎓</span> Student ID
    </label>
    <input type="text" name="student_id" class="form-control" 
           placeholder="e.g., 2023-0001 (Leave blank for auto-generation)"
           value="<?php echo !empty($_SESSION['form_data']['student_id']) ? htmlspecialchars($_SESSION['form_data']['student_id']) : ''; ?>">
    <div class="form-hint">
        <strong>New students:</strong> Leave blank to auto-generate a Student ID<br>
    </div>
    <div class="form-hint">
    <strong>Returning students:</strong> Enter your existing ASCOT Student ID
    </div>
</div>

                <!-- Password -->
                <div class="form-group">
                    <label class="form-label required">
                        <span>🔒</span> Password
                    </label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="Create a strong password" required
                               onkeyup="checkPasswordStrength()">
                        <button type="button" class="toggle-password" onclick="togglePassword('password')">Show</button>
                    </div>
                    <div class="password-strength">
                        <div class="strength-meter" id="strength-meter"></div>
                        <div class="strength-label" id="strength-label">Password Strength</div>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="form-group">
                    <label class="form-label required">
                        <span>🔒</span> Confirm Password
                    </label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                               placeholder="Re-enter your password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">Show</button>
                    </div>
                    <div class="form-hint error" id="confirm-password-error" style="display:none;">
                        ⚠️ Passwords do not match
                    </div>
                </div>

                <!-- Terms and Conditions -->
                <div class="terms-group">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">
                        I agree to the <a href="terms.html" target="_blank">Terms of Service</a> and <a href="privacy.html" target="_blank">Privacy Policy</a>. 
                        I understand that this account will be used for official ASCOT communications.
                    </label>
                </div>
                <div class="form-hint error" id="terms-error" style="display:none;">
                    ⚠️ You must agree to the terms and conditions
                </div>

                <!-- Submit Button -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <span>✅</span> Create Account
                    </button>
                    <a href="StudentLogin.php" class="btn btn-outline">
                        <span>←</span> Already have an account? Log In
                    </a>
                </div>
            </form>

            
        </div>
    </div>

    <?php
    // Clear form data after displaying
    if (isset($_SESSION['form_data'])) {
        unset($_SESSION['form_data']);
    }
    ?>
</body>
</html>