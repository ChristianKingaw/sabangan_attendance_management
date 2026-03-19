<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendance Management</title>
  <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
  <style>
    :root {
      --orange-1: #ff6a00;
      --orange-2: #ff8a1f;
      --orange-3: #ffd2a6;
      --ink-1: #1f1f1f;
      --ink-2: #4a4a4a;
      --paper: #fff7ef;
      --danger: #b42318;
    }

    body {
      font-family: "Trebuchet MS", "Segoe UI", sans-serif;
      color: var(--ink-1);
      background:
        radial-gradient(1100px 520px at 80% -10%, rgba(255, 138, 31, 0.35), transparent 70%),
        radial-gradient(900px 420px at 0% 20%, rgba(255, 106, 0, 0.22), transparent 65%),
        linear-gradient(180deg, #fff7ef, #fffaf6 40%, #fff 100%);
      min-height: 100vh;
    }

    .page {
      min-height: 100vh;
      display: grid;
      grid-template-rows: auto 1fr auto;
    }

    .app-header {
      background: linear-gradient(90deg, var(--orange-1), var(--orange-2));
      color: #fff;
      border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }

    .brand-mark {
      font-family: "Georgia", "Times New Roman", serif;
      letter-spacing: 0.5px;
      font-weight: 700;
    }

    .content-grid {
      display: grid;
      grid-template-columns: 260px 1fr;
      gap: 24px;
      padding: 24px;
    }

    .sidebar {
      background: #fff;
      border: 1px solid rgba(255, 106, 0, 0.2);
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(255, 106, 0, 0.08);
      padding: 18px;
      position: sticky;
      top: 20px;
      height: fit-content;
    }

    .sidebar .nav-link {
      color: var(--ink-2);
      border-radius: 10px;
      padding: 10px 12px;
      margin-bottom: 6px;
      transition: background 160ms ease, color 160ms ease, transform 160ms ease;
    }

    .sidebar .nav-link.active,
    .sidebar .nav-link:hover {
      background: rgba(255, 106, 0, 0.12);
      color: var(--orange-1);
      transform: translateX(2px);
    }

    .main-panel {
      background: #fff;
      border-radius: 18px;
      border: 1px solid rgba(0, 0, 0, 0.04);
      padding: 24px;
      box-shadow: 0 14px 30px rgba(0, 0, 0, 0.06);
    }

    .tag {
      display: inline-block;
      font-size: 0.8rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      background: rgba(255, 106, 0, 0.1);
      color: var(--orange-1);
      padding: 4px 10px;
      border-radius: 999px;
      font-weight: 700;
    }

    .danger-card {
      border: 1px solid rgba(180, 35, 24, 0.2);
      border-radius: 16px;
      background: rgba(180, 35, 24, 0.04);
      padding: 20px;
      max-width: 700px;
    }

    .danger-title {
      color: var(--danger);
      font-size: 1.2rem;
      font-weight: 700;
      margin-bottom: 8px;
    }

    .danger-note {
      color: #5a1b17;
      margin-bottom: 16px;
    }

    .app-footer {
      padding: 16px 24px;
      color: var(--ink-2);
      border-top: 1px solid rgba(255, 106, 0, 0.1);
      background: #fff;
    }

    @media (max-width: 992px) {
      .content-grid {
        grid-template-columns: 1fr;
      }

      .sidebar {
        position: static;
      }
    }
  </style>
</head>
<body>
  <div class="page">
    <header class="app-header">
      <div class="container-fluid py-3 px-4 d-flex flex-wrap align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle bg-white text-dark d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
            AM
          </div>
          <div>
            <div class="brand-mark h5 mb-0">Attendance Management</div>
            <div class="small opacity-75">Offline-ready dashboard</div>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2 mt-3 mt-md-0">
          @if (session('admin_id'))
            <form action="{{ route('logout') }}" method="post" class="d-inline">
              @csrf
              <button class="btn btn-outline-light btn-sm" type="submit">Logout</button>
            </form>
          @endif
        </div>
      </div>
    </header>

    <div class="content-grid">
      <aside class="sidebar">
        <div class="mb-3">
          <span class="tag">Navigation</span>
        </div>
        <nav class="nav flex-column">
          <a class="nav-link" href="{{ url('/') }}">Generate</a>
          <a class="nav-link" href="{{ route('attendance.index') }}">Attendance</a>
          <a class="nav-link active" href="{{ route('settings.index') }}">Settings</a>
        </nav>
      </aside>

      <main class="main-panel">
        @if (session('status'))
          <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <div class="d-flex align-items-center justify-content-between mb-3">
          <h1 class="h4 mb-0">Settings</h1>
          <div class="text-muted small">System maintenance actions</div>
        </div>

        <section class="danger-card">
          <div class="danger-title">Delete Attendance</div>
          <p class="danger-note mb-3">
            This removes all attendance records from the database and deletes all generated attendance documents from storage.
            This action cannot be undone.
          </p>

          <form action="{{ route('settings.attendance.destroyAll') }}" method="post" onsubmit="return confirm('Delete all attendance records and files? This cannot be undone.');">
            @csrf
            @method('DELETE')
            <button class="btn btn-danger" type="submit">Delete Attendance</button>
          </form>
        </section>
      </main>
    </div>

    <footer class="app-footer d-flex flex-wrap align-items-center justify-content-between">
      <div class="small">Attendance Management System</div>
      <div class="small">Offline mode ready - Data synced locally</div>
    </footer>
  </div>

  <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
