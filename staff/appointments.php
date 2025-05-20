<?php
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    (
        ($_POST['action'] === 'reschedule' && isset($_POST['name'], $_POST['oldDate'], $_POST['oldTime'], $_POST['reason'], $_POST['newDate'], $_POST['newTime'])) ||
        (in_array($_POST['action'], ['approve', 'decline']) && isset($_POST['date'], $_POST['time'], $_POST['reason'], $_POST['name']))
    )
) {
    $conn = new mysqli('localhost', 'root', '', 'clinic_management_system');
    if ($conn->connect_errno) {
        echo json_encode(['success' => false, 'error' => 'DB error']);
        exit;
    }
    $action = $_POST['action'];
    if ($action === 'reschedule') {
        $name = $_POST['name'];
        $oldDate = $_POST['oldDate'];
        $oldTime = $_POST['oldTime'];
        $reason = $_POST['reason'];
        $newDate = $_POST['newDate'];
        $newTime = $_POST['newTime'];
        $stmt = $conn->prepare('SELECT a.email, ip.id FROM appointments a JOIN imported_patients ip ON a.student_id = ip.id WHERE ip.name = ? AND a.date = ? AND a.time = ? AND a.reason = ? LIMIT 1');
        $stmt->bind_param('ssss', $name, $oldDate, $oldTime, $reason);
        $stmt->execute();
        $stmt->bind_result($email, $student_id);
        $stmt->fetch();
        $stmt->close();
        if ($student_id && $email) {
            $stmt2 = $conn->prepare('UPDATE appointments SET date = ?, time = ?, status = ? WHERE student_id = ? AND date = ? AND time = ? AND reason = ?');
            $newStatus = 'rescheduled';
            $stmt2->bind_param('sssisss', $newDate, $newTime, $newStatus, $student_id, $oldDate, $oldTime, $reason);
            $success = $stmt2->execute();
            $stmt2->close();
            // Send email notification
            require_once __DIR__ . '/../mail.php';
            $subject = 'Your Appointment Has Been Rescheduled';
            $msg = "Dear $name,<br>Your appointment for '$reason' has been rescheduled to <b>$newDate</b> at <b>$newTime</b>.<br>If you have questions, please contact the clinic.";
            sendMail($email, $name, $subject, $msg);
            // Insert notification for the patient
            $notif_msg = "Your appointment for $reason has been <span class='text-blue-600 font-semibold'>rescheduled</span> to <b>$newDate</b> at <b>$newTime</b>.";
            $notif_type = 'appointment';
            $stmt3 = $conn->prepare('INSERT INTO notifications (student_id, message, type, created_at) VALUES (?, ?, ?, NOW())');
            $stmt3->bind_param('iss', $student_id, $notif_msg, $notif_type);
            $stmt3->execute();
            $stmt3->close();
            echo json_encode(['success' => $success]);
            exit;
        } else {
            echo json_encode(['success' => false, 'error' => 'Patient not found']);
            exit;
        }
    }
    // Approve/Decline logic
    $date = $_POST['date'];
    $time = $_POST['time'];
    $reason = $_POST['reason'];
    $name = $_POST['name'];

    // Get student_id from imported_patients
    $stmt = $conn->prepare('SELECT id FROM imported_patients WHERE name = ? LIMIT 1');
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $stmt->bind_result($student_id);
    $stmt->fetch();
    $stmt->close();

    if ($student_id) {
        if ($action === 'approve' || $action === 'decline') {
            $status = $action === 'approve' ? 'approved' : 'declined';
            $stmt = $conn->prepare('UPDATE appointments SET status = ? WHERE student_id = ? AND date = ? AND time = ? AND reason = ?');
            $stmt->bind_param('sisss', $status, $student_id, $date, $time, $reason);
            $stmt->execute();
            $stmt->close();

            // Insert notification
            $notif_msg = $status === 'approved'
                ? "Your appointment for $date $time has been <span class='text-green-600 font-semibold'>approved</span>."
                : "Your appointment for $date $time has been <span class='text-red-600 font-semibold'>declined</span>.";
            $notif_type = 'appointment';
            $conn->query("INSERT INTO notifications (student_id, message, type, created_at) VALUES ($student_id, '" . $conn->real_escape_string($notif_msg) . "', '$notif_type', NOW())");

            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Student not found']);
    }
    $conn->close();
    exit;
}
?>
<?php
include '../includes/header.php';

$conn = new mysqli('localhost', 'root', '', 'clinic_management_system');
if ($conn->connect_errno) {
    die('Database connection failed: ' . $conn->connect_error);
}

$appointments = [];
// Use only columns that exist in the appointments table
$sql = 'SELECT a.date, a.time, a.reason, a.status, a.email, ip.name FROM appointments a JOIN imported_patients ip ON a.student_id = ip.id ORDER BY a.date DESC, a.time DESC';
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
    $result->free();
}
$conn->close();
?>
<!-- Dashboard Content -->

<!-- Appointments Content -->
<main class="flex-1 overflow-y-auto bg-gray-50 p-6 ml-16 md:ml-64 mt-[56px]">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Appointments</h2>
    <!-- Filters -->
    <div class="flex flex-wrap gap-4 items-end mb-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
            <input type="date" class="border border-gray-300 rounded px-3 py-2 text-sm" />
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select class="border border-gray-300 rounded px-3 py-2 text-sm">
                <option value="all">All</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="declined">Declined</option>
                <option value="rescheduled">Rescheduled</option>
            </select>
        </div>
    </div>
    <!-- Calendar View -->
    <div class="bg-white rounded shadow p-4 mb-8">
        <div class="flex items-center justify-between mb-4">
            <button class="text-gray-500 hover:text-primary"><i class="ri-arrow-left-s-line ri-lg"></i></button>
            <span class="font-semibold text-lg">May 2025</span>
            <button class="text-gray-500 hover:text-primary"><i class="ri-arrow-right-s-line ri-lg"></i></button>
        </div>
        <div class="grid grid-cols-7 gap-2 text-center text-sm">
            <div class="font-semibold text-gray-600">Sun</div>
            <div class="font-semibold text-gray-600">Mon</div>
            <div class="font-semibold text-gray-600">Tue</div>
            <div class="font-semibold text-gray-600">Wed</div>
            <div class="font-semibold text-gray-600">Thu</div>
            <div class="font-semibold text-gray-600">Fri</div>
            <div class="font-semibold text-gray-600">Sat</div>
            <!-- Demo calendar days -->
            <div class="text-gray-400">27</div>
            <div class="text-gray-400">28</div>
            <div class="text-gray-400">29</div>
            <div class="text-gray-400">30</div>
            <div>1</div>
            <div>2</div>
            <div>3</div>
            <div>4</div>
            <div>5</div>
            <div>6</div>
            <div>7</div>
            <div>8</div>
            <div>9</div>
            <div>10</div>
            <div>11</div>
            <div>12</div>
            <div>13</div>
            <div>14</div>
            <div class="bg-primary text-white rounded">15</div>
            <div>16</div>
            <div>17</div>
            <div>18</div>
            <div>19</div>
            <div>20</div>
            <div>21</div>
            <div>22</div>
            <div>23</div>
            <div>24</div>
            <div>25</div>
            <div>26</div>
            <div>27</div>
            <div>28</div>
            <div>29</div>
            <div>30</div>
            <div class="text-gray-400">1</div>
        </div>
    </div>
    <!-- Appointment Table: Pending -->
    <div class="bg-white rounded shadow p-6 mb-8">
        <h3 class="text-lg font-semibold mb-4">Pending Appointments</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Name</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Date</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Time</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Reason</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Status</th>
                        <th class="px-4 py-2 text-center font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($appointments)): ?>
                        <?php foreach ($appointments as $appt): ?>
                            <?php if ($appt['status'] === 'pending'): ?>
                            <tr data-email="<?php echo htmlspecialchars($appt['email']); ?>">
                                <td class="px-4 py-2"><?php echo htmlspecialchars($appt['name']); ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($appt['date']); ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($appt['time']); ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($appt['reason']); ?></td>
                                <td class="px-4 py-2">
                                    <span class="inline-block px-2 py-1 rounded bg-yellow-100 text-yellow-800 text-xs">Pending</span>
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <button class="approveBtn px-2 py-1 text-xs bg-green-500 text-white rounded hover:bg-green-600 mr-1">Approve</button>
                                    <button class="declineBtn px-2 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600 mr-1">Decline</button>
                                    <button class="reschedBtn px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600">Reschedule</button>
                                </td>
                            </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="px-4 py-2 text-center text-gray-400">No pending appointments found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Appointment Table: Done (Approved/Declined) -->
    <div class="bg-white rounded shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Done Appointments</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Name</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Date</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Time</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Reason</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Email</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($appointments)): ?>
                        <?php foreach ($appointments as $appt): ?>
                            <?php if ($appt['status'] === 'approved' || $appt['status'] === 'confirmed' || $appt['status'] === 'declined'): ?>
                            <tr>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($appt['name']); ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($appt['date']); ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($appt['time']); ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($appt['reason']); ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($appt['email']); ?></td>
                                <td class="px-4 py-2">
                                    <?php if ($appt['status'] === 'approved' || $appt['status'] === 'confirmed'): ?>
                                        <span class="inline-block px-2 py-1 rounded bg-green-100 text-green-800 text-xs">Approved</span>
                                    <?php elseif ($appt['status'] === 'declined'): ?>
                                        <span class="inline-block px-2 py-1 rounded bg-red-100 text-red-800 text-xs">Declined</span>
                                    <?php else: ?>
                                        <span class="inline-block px-2 py-1 rounded bg-gray-100 text-gray-800 text-xs"><?php echo htmlspecialchars(ucfirst($appt['status'])); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="px-4 py-2 text-center text-gray-400">No done appointments found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<script>
    // Demo action button logic
    const approveBtns = document.querySelectorAll('.approveBtn');
    const declineBtns = document.querySelectorAll('.declineBtn');
    const reschedBtns = document.querySelectorAll('.reschedBtn');
    approveBtns.forEach(btn => btn.addEventListener('click', () => alert('Appointment approved!')));
    declineBtns.forEach(btn => btn.addEventListener('click', () => alert('Appointment declined!')));
    // Reschedule logic
    reschedBtns.forEach(btn => btn.addEventListener('click', function() {
        const row = btn.closest('tr');
        const name = row.children[0].textContent.trim();
        const oldDate = row.children[1].textContent.trim();
        const oldTime = row.children[2].textContent.trim();
        const reason = row.children[3].textContent.trim();
        const email = row.getAttribute('data-email');
        // Show a prompt for new date and time
        const newDate = prompt('Enter new date for this appointment (YYYY-MM-DD):', oldDate);
        if (!newDate) return;
        const newTime = prompt('Enter new time for this appointment (HH:MM):', oldTime);
        if (!newTime) return;
        // Show the patient's email for notification
        alert('Patient email for notification: ' + email);
        if (confirm(`Are you sure you want to move this appointment to ${newDate} ${newTime}?`)) {
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'reschedule', name, oldDate, oldTime, reason, newDate, newTime })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    row.children[1].textContent = newDate;
                    row.children[2].textContent = newTime;
                    const statusCell = row.querySelector('td:nth-child(5) span');
                    statusCell.textContent = 'Rescheduled';
                    statusCell.className = 'inline-block px-2 py-1 rounded bg-blue-100 text-blue-800 text-xs';
                    alert('Appointment rescheduled and patient notified.');
                } else {
                    alert('Failed to reschedule appointment: ' + (data.error || 'Unknown error'));
                }
            });
        }
    }));

document.querySelectorAll('.approveBtn, .declineBtn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const row = btn.closest('tr');
        const action = btn.classList.contains('approveBtn') ? 'approve' : 'decline';
        const date = row.children[1].textContent.trim();
        const time = row.children[2].textContent.trim();
        const reason = row.children[3].textContent.trim();
        const name = row.children[0].textContent.trim();

        if (confirm(`Are you sure you want to ${action} this appointment?`)) {
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action, date, time, reason, name })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Update status cell
                    const statusCell = row.querySelector('td:nth-child(5) span');
                    if (action === 'approve') {
                        statusCell.textContent = 'Approved';
                        statusCell.className = 'inline-block px-2 py-1 rounded bg-green-100 text-green-800 text-xs';
                    } else {
                        statusCell.textContent = 'Declined';
                        statusCell.className = 'inline-block px-2 py-1 rounded bg-red-100 text-red-800 text-xs';
                    }
                } else {
                    alert('Failed to update appointment: ' + (data.error || 'Unknown error'));
                }
            });
        }
    });
});
</script>

<?php
include '../includes/footer.php';
?>