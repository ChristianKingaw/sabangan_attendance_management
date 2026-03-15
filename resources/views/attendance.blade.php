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

    .department-card {
      border: 1px solid rgba(255, 106, 0, 0.15);
      border-radius: 16px;
      padding: 16px;
      background: rgba(255, 210, 166, 0.08);
    }

    .employee-card {
      border: 1px solid rgba(0, 0, 0, 0.05);
      border-radius: 12px;
      padding: 12px;
      background: #fff;
    }

    .nav-card {
      border: 1px solid rgba(255, 106, 0, 0.15);
      border-radius: 16px;
      padding: 16px;
      background: rgba(255, 210, 166, 0.08);
    }

    .panel-hidden {
      display: none;
    }

    details summary {
      cursor: pointer;
      list-style: none;
    }

    details summary::-webkit-details-marker {
      display: none;
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
          <a class="nav-link active" href="{{ route('attendance.index') }}">Attendance</a>
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
          <h1 class="h4 mb-0">Attendance</h1>
          <div class="text-muted small">Departments and attendance archives</div>
        </div>

        @if (empty($departmentGroups) || $departmentGroups->isEmpty())
          <div class="text-muted">No attendance records yet. Upload an XLS to generate attendance.</div>
        @else
          <div id="deptView" class="nav-card">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div>
                <div class="fw-semibold">Departments</div>
                <div class="small text-muted">Select a department</div>
              </div>
            </div>
            <div id="deptList" class="list-group"></div>
          </div>

          <div id="employeeView" class="nav-card panel-hidden">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div>
                <div class="fw-semibold">Employees</div>
                <div class="small text-muted" id="currentDeptTitle"></div>
              </div>
              <button class="btn btn-sm btn-outline-secondary" type="button" id="backToDepts">Back</button>
            </div>
            <div id="employeeList" class="list-group"></div>
          </div>

          <div id="attendanceView" class="nav-card panel-hidden">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div>
                <div class="fw-semibold">Attendance</div>
                <div class="small text-muted" id="currentEmployeeTitle"></div>
              </div>
              <button class="btn btn-sm btn-outline-secondary" type="button" id="backToEmployees">Back</button>
            </div>
            <div class="d-flex align-items-center gap-2 mb-3">
              <a class="btn btn-sm btn-outline-primary" id="downloadAllButton" href="#">Download All</a>
            </div>
            <div id="attendanceList" class="list-group"></div>
          </div>
        @endif
      </main>
    </div>

    <footer class="app-footer d-flex flex-wrap align-items-center justify-content-between">
      <div class="small">Attendance Management System</div>
      <div class="small">Offline mode ready - Data synced locally</div>
    </footer>
  </div>

  <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  @if (!empty($departmentGroups) && !$departmentGroups->isEmpty())
  <script>
    const attendanceData = @json($attendancePayload);
    const deptView = document.getElementById('deptView');
    const employeeView = document.getElementById('employeeView');
    const attendanceView = document.getElementById('attendanceView');
    const deptList = document.getElementById('deptList');
    const employeeList = document.getElementById('employeeList');
    const attendanceList = document.getElementById('attendanceList');
    const currentDeptTitle = document.getElementById('currentDeptTitle');
    const currentEmployeeTitle = document.getElementById('currentEmployeeTitle');
    const backToDepts = document.getElementById('backToDepts');
    const backToEmployees = document.getElementById('backToEmployees');
    const downloadAllButton = document.getElementById('downloadAllButton');
    const zipBaseUrl = "{{ route('attendance.employee.zip') }}";

    let selectedDept = null;
    let selectedEmployee = null;

    function showPanel(panel) {
      [deptView, employeeView, attendanceView].forEach((view) => {
        if (!view) {
          return;
        }
        view.classList.toggle('panel-hidden', view !== panel);
      });
    }

    function renderDepartments() {
      deptList.innerHTML = '';
      attendanceData.forEach((dept, index) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'list-group-item list-group-item-action d-flex align-items-center justify-content-between';
        button.innerHTML = `
          <span>${dept.department_label}</span>
          <span class="small text-muted">${dept.employees.length} employee(s)</span>
        `;
        button.addEventListener('click', () => {
          selectedDept = attendanceData[index];
          renderEmployees();
          showPanel(employeeView);
        });
        deptList.appendChild(button);
      });
    }

    function renderEmployees() {
      if (!selectedDept) {
        return;
      }
      currentDeptTitle.textContent = selectedDept.department_label;
      employeeList.innerHTML = '';
      selectedDept.employees.forEach((employee, index) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'list-group-item list-group-item-action d-flex align-items-center justify-content-between';
        const idText = employee.employee_id ? `ID: ${employee.employee_id}` : 'ID: N/A';
        button.innerHTML = `
          <div>
            <div class="fw-semibold">${employee.employee_name}</div>
            <div class="small text-muted">${idText}</div>
          </div>
          <span class="small text-muted">${employee.records.length} record(s)</span>
        `;
        button.addEventListener('click', () => {
          selectedEmployee = selectedDept.employees[index];
          renderAttendance();
          showPanel(attendanceView);
        });
        employeeList.appendChild(button);
      });
    }

    function renderAttendance() {
      if (!selectedDept || !selectedEmployee) {
        return;
      }
      currentEmployeeTitle.textContent = `${selectedEmployee.employee_name} - ${selectedDept.department_label}`;
      attendanceList.innerHTML = '';
      selectedEmployee.records.forEach((record) => {
        const item = document.createElement(record.document_url ? 'a' : 'div');
        item.className = 'list-group-item d-flex align-items-center justify-content-between';
        if (record.document_url) {
          item.href = record.document_url;
          item.target = '_blank';
          item.classList.add('list-group-item-action');
        }
        item.innerHTML = `
          <span>${record.period_label || 'Unknown period'}</span>
          <span class="small text-muted">${record.document_url ? 'DOCX' : 'Missing file'}</span>
        `;
        attendanceList.appendChild(item);
      });

      const params = new URLSearchParams({
        department: selectedDept.department_value !== '' ? selectedDept.department_value : '__none__',
        employee_id: selectedEmployee.employee_id || '',
        employee_name: selectedEmployee.employee_name || '',
      });
      downloadAllButton.href = `${zipBaseUrl}?${params.toString()}`;
    }

    if (backToDepts) {
      backToDepts.addEventListener('click', () => {
        selectedDept = null;
        selectedEmployee = null;
        showPanel(deptView);
      });
    }

    if (backToEmployees) {
      backToEmployees.addEventListener('click', () => {
        selectedEmployee = null;
        showPanel(employeeView);
      });
    }

    renderDepartments();
    showPanel(deptView);
  </script>
  @endif
</body>
</html>
