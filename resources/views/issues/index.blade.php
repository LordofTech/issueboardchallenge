<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issues Board</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 24px; margin-bottom: 24px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 4px; font-weight: 500; color: #333; }
        .form-control { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .form-control:focus { outline: none; border-color: #007bff; box-shadow: 0 0 0 2px rgba(0,123,255,0.25); }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 500; }
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0056b3; }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .table th { background: #f8f9fa; font-weight: 600; }
        .status { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-open { background: #e3f2fd; color: #1976d2; }
        .status-in_progress { background: #fff3e0; color: #f57c00; }
        .status-resolved { background: #e8f5e8; color: #2e7d32; }
        .status-closed { background: #f5f5f5; color: #616161; }
        .priority { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .priority-low { background: #e8f5e8; color: #2e7d32; }
        .priority-medium { background: #fff3e0; color: #f57c00; }
        .priority-high { background: #ffebee; color: #c62828; }
        .priority-critical { background: #ffcdd2; color: #d32f2f; }
        .error { color: #dc3545; font-size: 12px; margin-top: 4px; }
        .toast { position: fixed; top: 20px; right: 20px; padding: 12px 20px; border-radius: 4px; color: white; z-index: 1000; }
        .toast-success { background: #28a745; }
        .toast-error { background: #dc3545; }
        .loading { text-align: center; padding: 40px; color: #666; }
        .filters { display: flex; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
        .filters > * { flex: 1; min-width: 200px; }
        .pagination { display: flex; justify-content: space-between; align-items: center; margin-top: 20px; }
        .pagination button { background: white; border: 1px solid #ddd; padding: 8px 16px; border-radius: 4px; cursor: pointer; }
        .pagination button:hover { background: #f8f9fa; }
        .pagination button:disabled { opacity: 0.5; cursor: not-allowed; }
        .new-item { background: #fffbf0 !important; border-left: 4px solid #ffc107; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.3s ease-out; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Issues Board</h1>
            
            <!-- Create Issue Form -->
            <div class="card" style="margin-top: 24px;">
                <h2>Create New Issue</h2>
                <form id="issueForm">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label for="title">Title *</label>
                            <input type="text" id="title" name="title" class="form-control" required>
                            <div class="error" id="title-error"></div>
                        </div>
                        <div class="form-group">
                            <label for="priority">Priority *</label>
                            <select id="priority" name="priority" class="form-control" required>
                                <option value="">Select Priority</option>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                            <div class="error" id="priority-error"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" class="form-control" rows="3" required></textarea>
                        <div class="error" id="description-error"></div>
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="open">Open</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Create Issue</button>
                </form>
            </div>

            <!-- Search and Filter -->
            <div class="filters">
                <div class="form-group">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search issues...">
                </div>
                <div class="form-group">
                    <select id="statusFilter" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
            </div>

            <!-- Issues Table -->
            <div id="loadingDiv" class="loading">Loading issues...</div>
            <div id="issuesContainer" style="display: none;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody id="issuesTableBody">
                        <!-- Issues will be populated here -->
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <div class="pagination" id="pagination" style="display: none;">
                    <div id="paginationInfo"></div>
                    <div>
                        <button id="prevBtn" onclick="changePage(-1)">Previous</button>
                        <button id="nextBtn" onclick="changePage(1)">Next</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Global state
        let currentPage = 1;
        let totalPages = 1;
        let searchTimeout;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadIssues();
            setupEventListeners();
        });

        function setupEventListeners() {
            // Form submission
            document.getElementById('issueForm').addEventListener('submit', createIssue);
            
            // Search with debounce
            document.getElementById('searchInput').addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentPage = 1;
                    loadIssues();
                }, 500);
            });
            
            // Status filter
            document.getElementById('statusFilter').addEventListener('change', function() {
                currentPage = 1;
                loadIssues();
            });
        }

        async function loadIssues() {
            showLoading(true);
            
            const search = document.getElementById('searchInput').value;
            const status = document.getElementById('statusFilter').value;
            
            const params = new URLSearchParams({
                page: currentPage,
                per_page: 10,
                ...(search && { search }),
                ...(status && { status })
            });

            try {
                const response = await fetch(`/api/issues?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                if (!response.ok) throw new Error('Failed to load issues');

                const data = await response.json();
                displayIssues(data.data || []);
                updatePagination(data);
                
            } catch (error) {
                console.error('Error loading issues:', error);
                showToast('Error loading issues', 'error');
            } finally {
                showLoading(false);
            }
        }

        async function createIssue(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Creating...';
            
            clearErrors();
            
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('/api/issues', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (response.ok) {
                    e.target.reset();
                    showToast('Issue created successfully!', 'success');
                    loadIssues(); // Reload the list
                } else {
                    if (result.errors) {
                        displayErrors(result.errors);
                    } else {
                        showToast(result.message || 'Error creating issue', 'error');
                    }
                }
            } catch (error) {
                console.error('Error creating issue:', error);
                showToast('Network error', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Create Issue';
            }
        }

        function displayIssues(issues) {
            const tbody = document.getElementById('issuesTableBody');
            tbody.innerHTML = '';

            if (issues.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: #666;">No issues found</td></tr>';
                return;
            }

            issues.forEach(issue => {
                const row = document.createElement('tr');
                row.className = 'fade-in';
                row.innerHTML = `
                    <td>
                        <div style="font-weight: 500;">${escapeHtml(issue.title)}</div>
                        <div style="color: #666; font-size: 12px;">${escapeHtml(issue.description.substring(0, 100))}...</div>
                    </td>
                    <td><span class="status status-${issue.status}">${issue.status.replace('_', ' ').toUpperCase()}</span></td>
                    <td><span class="priority priority-${issue.priority}">${issue.priority.toUpperCase()}</span></td>
                    <td>${formatDate(issue.updated_at)}</td>
                `;
                tbody.appendChild(row);
            });
        }

        function updatePagination(data) {
            const pagination = document.getElementById('pagination');
            const info = document.getElementById('paginationInfo');
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');

            if (data.total > data.per_page) {
                pagination.style.display = 'flex';
                info.textContent = `Showing ${data.from} to ${data.to} of ${data.total} results`;
                
                prevBtn.disabled = data.current_page <= 1;
                nextBtn.disabled = data.current_page >= data.last_page;
                
                totalPages = data.last_page;
            } else {
                pagination.style.display = 'none';
            }
        }

        function changePage(direction) {
            const newPage = currentPage + direction;
            if (newPage >= 1 && newPage <= totalPages) {
                currentPage = newPage;
                loadIssues();
            }
        }

        function showLoading(show) {
            document.getElementById('loadingDiv').style.display = show ? 'block' : 'none';
            document.getElementById('issuesContainer').style.display = show ? 'none' : 'block';
        }

        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                document.body.removeChild(toast);
            }, 3000);
        }

        function displayErrors(errors) {
            Object.keys(errors).forEach(field => {
                const errorDiv = document.getElementById(`${field}-error`);
                if (errorDiv) {
                    errorDiv.textContent = errors[field][0];
                }
            });
        }

        function clearErrors() {
            document.querySelectorAll('.error').forEach(div => {
                div.textContent = '';
            });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Auto-refresh every 30 seconds to simulate real-time updates
        setInterval(loadIssues, 30000);
    </script>
</body>
</html>