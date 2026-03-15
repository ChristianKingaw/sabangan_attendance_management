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

    .app-footer {
      padding: 16px 24px;
      color: var(--ink-2);
      border-top: 1px solid rgba(255, 106, 0, 0.1);
      background: #fff;
    }

    .upload-hero {
      text-align: center;
      max-width: 820px;
      margin: 0 auto;
      padding: 24px 12px 8px;
    }

    .upload-title {
      font-size: 2.6rem;
      line-height: 3.2rem;
      font-weight: 800;
      margin-bottom: 12px;
      color: var(--ink-1);
    }

    .upload-subtitle {
      font-size: 1rem;
      line-height: 1.6rem;
      color: var(--ink-2);
      margin-bottom: 24px;
    }

    .upload-card {
      background: #fff;
      border: 1px solid rgba(255, 106, 0, 0.18);
      border-radius: 18px;
      padding: 22px;
      box-shadow: 0 12px 28px rgba(255, 106, 0, 0.08);
    }

    .upload-action {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 12px;
    }

    .upload-btn {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      border: none;
      background: linear-gradient(90deg, var(--orange-1), var(--orange-2));
      color: #fff;
      padding: 12px 22px;
      border-radius: 999px;
      font-weight: 700;
      box-shadow: 0 12px 24px rgba(255, 106, 0, 0.28);
    }

    .upload-btn:focus {
      outline: 2px solid rgba(255, 106, 0, 0.4);
      outline-offset: 2px;
    }

    .upload-drop {
      width: 100%;
      border: 2px dashed rgba(255, 106, 0, 0.35);
      border-radius: 16px;
      padding: 18px;
      color: var(--ink-2);
      background: rgba(255, 210, 166, 0.15);
      transition: border-color 160ms ease, background 160ms ease;
    }

    .upload-drop.active {
      border-color: var(--orange-1);
      background: rgba(255, 210, 166, 0.3);
    }

    .upload-note {
      font-size: 0.9rem;
      color: var(--ink-2);
    }

    .loading-overlay {
      position: fixed;
      inset: 0;
      background: rgba(255, 247, 239, 0.85);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 2000;
      backdrop-filter: blur(2px);
    }

    .loading-overlay.active {
      display: flex;
    }

    .loading-card {
      background: #fff;
      border-radius: 16px;
      padding: 24px 28px;
      border: 1px solid rgba(255, 106, 0, 0.2);
      box-shadow: 0 20px 50px rgba(255, 106, 0, 0.2);
      text-align: center;
      min-width: 260px;
    }

    .loading-text {
      margin-top: 12px;
      color: var(--ink-2);
    }

    @media (max-width: 992px) {
      .upload-title {
        font-size: 2.1rem;
        line-height: 2.7rem;
      }
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
          <button class="btn btn-light btn-sm">New Attendance</button>
          
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
          <a class="nav-link active" href="{{ url('/') }}">Generate</a>
          <a class="nav-link" href="{{ route('attendance.index') }}">Attendance</a>
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

        <form action="{{ route('documents.upload') }}" method="post" enctype="multipart/form-data">
          @csrf

          <div class="upload-hero">
            <h1 class="upload-title">Offline DTR Document Generator</h1>
            <p class="upload-subtitle">
              Drag and drop your XLS file, or paste it with Ctrl + V. The system will auto‑fill your DOCX template and
              save it locally.
            </p>
          </div>

          <div class="upload-card">
            <div class="upload-action">
              <input class="visually-hidden" type="file" id="xlsInput" name="xls_file" accept=".xls" required>
              <button class="upload-btn" type="button" id="uploadButton">
                <span aria-hidden="true">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M5.734 8.438C6.008 5.64 8.387 3.5 11.208 3.5a5.5 5.5 0 0 1 5.422 4.562.24.24 0 0 0 .202.19c3.026.232 5.418 2.795 5.418 5.866 0 3.25-2.642 5.882-5.896 5.882H14.75a.75.75 0 0 1 0-1.5h1.604a4.389 4.389 0 0 0 4.396-4.382c0-2.287-1.788-4.199-4.033-4.371a1.738 1.738 0 0 1-1.565-1.433A4 4 0 0 0 11.208 5C9.151 5 7.425 6.563 7.227 8.584a1.69 1.69 0 0 1-1.096 1.42 4.384 4.384 0 0 0-2.881 4.114A4.389 4.389 0 0 0 7.646 18.5h.104A4.25 4.25 0 0 0 12 14.25v-.95L9.99 15.32a.75.75 0 1 1-1.063-1.059l3.276-3.29a.75.75 0 0 1 1.063 0l3.276 3.29a.75.75 0 1 1-1.063 1.059L13.5 13.33v.919A5.75 5.75 0 0 1 7.75 20h-.104c-3.254 0-5.896-2.631-5.896-5.882a5.884 5.884 0 0 1 3.865-5.523.191.191 0 0 0 .12-.157Z" fill="currentColor"></path>
                  </svg>
                </span>
                Upload your XLS
              </button>

              <div class="upload-drop" id="pasteZone">
                <div class="upload-note">or drop it here / press Ctrl + V</div>
                <div class="small mt-2" id="pastedFileName"></div>
              </div>

              <button class="btn btn-warning mt-2" type="submit">Generate Docx</button>
              <div class="upload-note">Attendance documents are saved to `storage/app/attendance`.</div>
            </div>
          </div>
        </form>

      </main>
    </div>

    <footer class="app-footer d-flex flex-wrap align-items-center justify-content-between">
      <div class="small">Attendance Management System</div>
      <div class="small">Offline mode ready • Data synced locally</div>
    </footer>
  </div>

  <div class="loading-overlay" id="loadingOverlay" aria-hidden="true">
    <div class="loading-card">
      <div class="spinner-border text-warning" role="status" aria-hidden="true"></div>
      <div class="loading-text">Generating documents...</div>
    </div>
  </div>

  <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script>
    const pasteZone = document.getElementById('pasteZone');
    const fileInput = document.getElementById('xlsInput');
    const pastedFileName = document.getElementById('pastedFileName');
    const uploadButton = document.getElementById('uploadButton');
    const uploadForm = document.querySelector('form[action="{{ route('documents.upload') }}"]');
    const loadingOverlay = document.getElementById('loadingOverlay');

    if (pasteZone && fileInput) {
      pasteZone.addEventListener('click', () => pasteZone.focus());
      pasteZone.setAttribute('tabindex', '0');

      if (uploadButton) {
        uploadButton.addEventListener('click', () => fileInput.click());
      }

      fileInput.addEventListener('change', () => {
        const file = fileInput.files?.[0];
        if (file) {
          pastedFileName.textContent = `Attached: ${file.name}`;
        }
      });

      pasteZone.addEventListener('dragover', (event) => {
        event.preventDefault();
        pasteZone.classList.add('active');
      });

      pasteZone.addEventListener('dragleave', () => {
        pasteZone.classList.remove('active');
      });

      pasteZone.addEventListener('drop', (event) => {
        event.preventDefault();
        pasteZone.classList.remove('active');
        const files = event.dataTransfer?.files || [];
        if (files.length === 0) {
          pastedFileName.textContent = 'No file detected.';
          return;
        }
        const file = files[0];
        if (!file.name.toLowerCase().endsWith('.xls')) {
          pastedFileName.textContent = 'Only .xls files are allowed.';
          return;
        }
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        fileInput.files = dataTransfer.files;
        pastedFileName.textContent = `Attached: ${file.name}`;
      });

      pasteZone.addEventListener('paste', (event) => {
        const items = event.clipboardData?.items || [];
        for (const item of items) {
          if (item.kind === 'file') {
            const file = item.getAsFile();
            if (!file) {
              continue;
            }
            if (!file.name.toLowerCase().endsWith('.xls')) {
              pastedFileName.textContent = 'Only .xls files are allowed.';
              return;
            }

            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            fileInput.files = dataTransfer.files;
            pastedFileName.textContent = `Attached: ${file.name}`;
            return;
          }
        }
        pastedFileName.textContent = 'No file detected in clipboard.';
      });
    }

    if (uploadForm && loadingOverlay) {
      uploadForm.addEventListener('submit', () => {
        loadingOverlay.classList.add('active');
      });
    }
  </script>
</body>
</html>
