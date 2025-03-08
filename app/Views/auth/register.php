<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            line-height: 1.5;
            max-width: 500px;
            margin: 0 auto;
            padding: 2rem;
        }
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 0.25rem;
        }
        button {
            background-color: #4a5568;
            color: white;
            border: none;
            border-radius: 0.25rem;
            padding: 0.5rem 1rem;
            cursor: pointer;
        }
        button:hover {
            background-color: #2d3748;
        }
        .error {
            color: #e53e3e;
            margin-bottom: 1rem;
        }
        .oauth-buttons {
            margin-top: 2rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .oauth-button {
            display: block;
            text-align: center;
            padding: 0.5rem;
            border-radius: 0.25rem;
            text-decoration: none;
            color: white;
        }
        .oauth-button.firebase {
            background-color: #ffca28;
            color: #333;
        }
        .oauth-button.workos {
            background-color: #6366f1;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Register</h1>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>
    
    <form action="/register" method="POST">
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit">Register</button>
    </form>
    
    <div class="oauth-buttons">
        <a href="/auth/firebase" class="oauth-button firebase">Register with Firebase</a>
        <a href="/auth/workos" class="oauth-button workos">Register with WorkOS</a>
    </div>
    
    <p>Already have an account? <a href="/login">Login</a></p>
</body>
</html>

