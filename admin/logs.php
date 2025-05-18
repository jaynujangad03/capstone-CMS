<?php
include '../includea/header.php';
// Fetch logs from database
try {
    $db = new PDO('mysql:host=localhost;dbname=clinic_management_system;charset=utf8', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $logs = $db->query('SELECT * FROM logs ORDER BY timestamp DESC')->fetchAll(PDO::FETCH_ASSOC);
    // For user filter dropdown
    $userEmails = $db->query('SELECT DISTINCT user_email FROM logs')->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $logs = [];
    $userEmails = [];
}
?>
<main class="flex-1 overflow-y-auto bg-gray-50 p-6 ml-16 md:ml-64 mt-[56px]">
    <div class="max-w-5xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800">System Logs</h2>
            <button id="exportLogsBtn" class="px-4 py-2 bg-primary text-white font-medium text-sm rounded-button hover:bg-primary/90 flex items-center"><i class="ri-download-2-line mr-1"></i> Export Logs</button>
        </div>
        <!-- Filters and Search -->
        <div class="flex flex-wrap gap-4 items-end mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select class="border border-gray-300 rounded px-3 py-2 text-sm" id="roleFilter">
                    <option value="all">All Roles</option>
                    <option value="admin">Admin</option>
                    <option value="staff">Staff</option>
                    <option value="patient">Patient</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">User</label>
                <select class="border border-gray-300 rounded px-3 py-2 text-sm" id="userFilter">
                    <option value="all">All Users</option>
                    <?php foreach ($userEmails as $email): ?>
                        <option value="<?php echo htmlspecialchars($email); ?>"><?php echo htmlspecialchars($email); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input type="date" class="border border-gray-300 rounded px-3 py-2 text-sm" id="dateFilter" />
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" placeholder="Search logs..." class="w-full border border-gray-300 rounded px-3 py-2 text-sm" id="searchFilter" />
            </div>
        </div>
        <!-- Activity Log Table -->
        <div class="bg-white rounded shadow p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm" id="logsTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600">User</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600">Role</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600">Action</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="px-4 py-2 user-cell"><?php echo htmlspecialchars($log['user_email']); ?></td>
                            <td class="px-4 py-2 role-cell"><?php 
                                // Infer role for display
                                $u = strtolower($log['user_email']);
                                if ((strpos($u, 'admin') !== false && strpos($u, 'staff') === false && strpos($u, 'doctor') === false && strpos($u, 'nurse') === false) || $u === 'admin') {
                                    echo 'Admin';
                                } elseif (strpos($u, 'staff') !== false || strpos($u, 'doctor') !== false || strpos($u, 'nurse') !== false) {
                                    echo 'Staff';
                                } else {
                                    echo 'Patient';
                                }
                            ?></td>
                            <td class="px-4 py-2 action-cell"><?php echo htmlspecialchars($log['action']); ?></td>
                            <td class="px-4 py-2 timestamp-cell"><?php echo htmlspecialchars($log['timestamp']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const roleFilter = document.getElementById('roleFilter');
    const userFilter = document.getElementById('userFilter');
    const dateFilter = document.getElementById('dateFilter');
    const searchFilter = document.getElementById('searchFilter');
    const table = document.getElementById('logsTable');
    const rows = Array.from(table.querySelectorAll('tbody tr'));

    // Helper to infer role from user/email (adjust as needed)
    function getRoleFromUser(user) {
        const u = user.toLowerCase();
        if ((u.includes('admin') && !u.includes('staff') && !u.includes('doctor') && !u.includes('nurse')) || u === 'admin') return 'admin';
        if (u.includes('staff') || u.includes('doctor') || u.includes('nurse')) return 'staff';
        return 'patient';
    }

    function filterLogs() {
        const roleVal = roleFilter.value;
        const userVal = userFilter.value;
        const dateVal = dateFilter.value;
        const searchVal = searchFilter.value.toLowerCase();
        rows.forEach(row => {
            const user = row.querySelector('.user-cell').textContent;
            const role = row.querySelector('.role-cell').textContent.toLowerCase();
            const action = row.querySelector('.action-cell').textContent;
            const timestamp = row.querySelector('.timestamp-cell').textContent;
            let show = true;
            // Role filter: If a role is selected, only show logs for users with that role
            if (roleVal !== 'all' && role !== roleVal) {
                show = false;
            }
            // When a role is selected, reset user filter to 'all' (force only role filter)
            if (roleVal !== 'all') {
                userFilter.value = 'all';
            }
            // User filter (only if role is 'all')
            if (roleVal === 'all' && userVal !== 'all' && user !== userVal) {
                show = false;
            }
            // Date filter (YYYY-MM-DD)
            if (dateVal && !timestamp.startsWith(dateVal)) show = false;
            // Search filter
            if (searchVal && !(user.toLowerCase().includes(searchVal) || action.toLowerCase().includes(searchVal) || timestamp.toLowerCase().includes(searchVal))) show = false;
            row.style.display = show ? '' : 'none';
        });
    }
    roleFilter.addEventListener('change', filterLogs);
    userFilter.addEventListener('change', filterLogs);
    dateFilter.addEventListener('change', filterLogs);
    searchFilter.addEventListener('input', filterLogs);
    // Initial filter to apply all filters on load
    filterLogs();

    // Export logs as CSV
    document.getElementById('exportLogsBtn').addEventListener('click', function() {
        let csv = 'User,Action,Timestamp\n';
        rows.forEach(row => {
            if (row.style.display !== 'none') {
                const user = row.querySelector('.user-cell').textContent;
                const action = row.querySelector('.action-cell').textContent;
                const timestamp = row.querySelector('.timestamp-cell').textContent;
                csv += `"${user}","${action}","${timestamp}"\n`;
            }
        });
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'system_logs_<?php echo date('Ymd_His'); ?>.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });
});
</script>
<?php
include '../includea/footer.php';
?>