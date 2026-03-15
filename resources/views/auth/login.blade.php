<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login</title>
  <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
  <style>
    :root {
      --orange-1: #ff6a00;
      --orange-2: #ff8a1f;
      --ink-1: #1f1f1f;
      --ink-2: #4a4a4a;
    }

    body {
      min-height: 100vh;
      display: grid;
      place-items: center;
      font-family: "Trebuchet MS", "Segoe UI", sans-serif;
      color: var(--ink-1);
      background:
        radial-gradient(900px 520px at 80% -10%, rgba(255, 138, 31, 0.35), transparent 70%),
        radial-gradient(700px 360px at 0% 20%, rgba(255, 106, 0, 0.22), transparent 65%),
        linear-gradient(180deg, #fff7ef, #fffaf6 40%, #fff 100%);
      padding: 24px;
    }

    .login-card {
      width: 100%;
      max-width: 420px;
      background: #fff;
      border-radius: 18px;
      border: 1px solid rgba(255, 106, 0, 0.18);
      padding: 28px;
      box-shadow: 0 16px 40px rgba(255, 106, 0, 0.12);
    }

    .login-title {
      font-size: 2rem;
      font-weight: 800;
      margin-bottom: 8px;
    }

    .login-subtitle {
      color: var(--ink-2);
      margin-bottom: 20px;
    }

    .login-btn {
      background: linear-gradient(90deg, var(--orange-1), var(--orange-2));
      border: none;
      color: #fff;
      font-weight: 700;
      padding: 10px 18px;
      border-radius: 999px;
      width: 100%;
    }
  </style>
</head>
<body>
  <div class="login-card">
    <div class="login-title">Admin Login</div>
    <div class="login-subtitle">Enter your credentials to continue.</div>

    @if ($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form action="{{ route('login.submit') }}" method="post">
      @csrf
      <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input class="form-control" id="username" name="username" type="text" value="{{ old('username') }}" required autofocus>
      </div>
      <div class="mb-4">
        <label for="password" class="form-label">Password</label>
        <input class="form-control" id="password" name="password" type="password" required>
      </div>
      <button class="login-btn" type="submit">Log In</button>
    </form>
  </div>
</body>
</html>
