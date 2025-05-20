<?php
include '../includep/header.php';
$student_id = $_SESSION['student_row_id']; // This is the imported_patients.id

// Use a separate connection for this file to avoid using a closed $conn from header.php
$conn2 = new mysqli('localhost', 'root', '', 'clinic_management_system');
if ($conn2->connect_errno) {
    die('Database connection failed: ' . $conn2->connect_error);
}

// Handle appointment booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $time = $_POST['time'];
    $reason = $_POST['reason'];
    $email = $_POST['email'];
    $insert = $conn2->prepare('INSERT INTO appointments (student_id, date, time, reason, status, email) VALUES (?, ?, ?, ?, ?, ?)');
    $status = 'pending';
    $insert->bind_param('isssss', $student_id, $date, $time, $reason, $status, $email);
    $insert->execute();
    $insert->close();
}

// Fetch appointments for this student
$appointments = [];
$stmt = $conn2->prepare('SELECT date, time, reason, status, email FROM appointments WHERE student_id = ? ORDER BY date DESC, time DESC');
if ($stmt) {
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $stmt->bind_result($date, $time, $reason, $status, $email);
    while ($stmt->fetch()) {
        $appointments[] = [
            'date' => $date,
            'time' => $time,
            'reason' => $reason,
            'status' => $status,
            'email' => $email
        ];
    }
    $stmt->close();
}
$conn2->close();
?>

<main class="flex-1 overflow-y-auto bg-gray-50 p-6 ml-16 md:ml-64 mt-[56px]">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">My Appointments</h2>
    <!-- Notification -->
    <div class="mb-6">
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 p-4 rounded flex items-center gap-2">
            <i class="ri-information-line text-xl"></i>
            <span>You have 1 pending appointment. Please wait for confirmation.</span>
        </div>
    </div>
    <!-- Book Appointment Form -->
    <div class="bg-white rounded shadow p-6 mb-8 max-w-xl">
        <h3 class="text-lg font-semibold mb-4">Book an Appointment</h3>
        <form id="bookApptForm" method="POST" autocomplete="off">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input type="date" name="date" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" min="<?php echo date('Y-m-d'); ?>" required />
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Time</label>
                <input type="time" name="time" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" required />
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                <input type="text" name="reason" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Enter reason" required />
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <input type="email" name="email" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Enter your email address" required />
            </div>
            <button type="submit" id="bookApptBtn" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition-colors">Book Appointment</button>
        </form>
    </div>
    <!-- My Appointments Table: Pending -->
    <div class="bg-white rounded shadow p-6 mb-8">
        <h3 class="text-lg font-semibold mb-4">My Pending Appointments</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Date</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Time</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Reason</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Email</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Status</th>
                        <th class="px-4 py-2 text-center font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($appointments)): ?>
                    <?php foreach ($appointments as $appt): ?>
                        <?php if ($appt['status'] === 'pending'): ?>
                        <tr>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($appt['date']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($appt['time']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($appt['reason']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($appt['email']); ?></td>
                            <td class="px-4 py-2">
                                <span class="inline-block px-2 py-1 rounded bg-yellow-100 text-yellow-800 text-xs">Pending</span>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <button class="cancelBtn px-2 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600 mr-1">Cancel</button>
                                <button class="reschedBtn px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600">Reschedule</button>
                            </td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="px-4 py-2 text-center text-gray-400">No pending appointments found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- My Appointments Table: Done (Approved/Declined/Rescheduled) -->
    <div class="bg-white rounded shadow p-6">
        <h3 class="text-lg font-semibold mb-4">My Approved Appointments</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
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
                        <?php if ($appt['status'] === 'approved' || $appt['status'] === 'confirmed' || $appt['status'] === 'declined' || $appt['status'] === 'rescheduled'): ?>
                        <tr>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($appt['date']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($appt['time']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($appt['reason']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($appt['email']); ?></td>
                            <td class="px-4 py-2">
                                <?php if ($appt['status'] === 'approved' || $appt['status'] === 'confirmed'): ?>
                                    <span class="inline-block px-2 py-1 rounded bg-green-100 text-green-800 text-xs">Approved</span>
                                    <span class="block text-xs text-green-700 mt-1">Please wait for this day and go to the clinic.</span>
                                <?php elseif ($appt['status'] === 'declined'): ?>
                                    <span class="inline-block px-2 py-1 rounded bg-red-100 text-red-800 text-xs">Declined</span>
                                <?php elseif ($appt['status'] === 'rescheduled'): ?>
                                    <span class="inline-block px-2 py-1 rounded bg-blue-100 text-blue-800 text-xs">Rescheduled</span>
                                    <span class="block text-xs text-blue-700 mt-1">Please wait for this day and go to the clinic.</span>
                                <?php else: ?>
                                    <span class="inline-block px-2 py-1 rounded bg-gray-100 text-gray-800 text-xs"><?php echo htmlspecialchars(ucfirst($appt['status'])); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="px-4 py-2 text-center text-gray-400">No done appointments found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php
include '../includep/footer.php';
?>

<?php
// DROP TABLE IF EXISTS appointments;
// CREATE TABLE appointments (
//     id INT AUTO_INCREMENT PRIMARY KEY,
//     student_id INT NOT NULL,
//     date DATE NOT NULL,
//     time TIME NOT NULL,
//     reason VARCHAR(255) NOT NULL,
//     status ENUM('pending', 'confirmed', 'canceled') DEFAULT 'pending',
//     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
//     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
//     FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
// );
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.cancelBtn').forEach(function(btn, idx) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            if (btn.disabled) return;
            if (confirm('Are you sure you want to cancel this appointment?')) {
                // Find the appointment row and get date/time/reason as unique keys
                const row = btn.closest('tr');
                const date = row.children[0].textContent.trim();
                const time = row.children[1].textContent.trim();
                const reason = row.children[2].textContent.trim();
                // Send AJAX request to cancel
                fetch('profile_cancel_appointment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ date, time, reason })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Update status cell in the table
                        const statusCell = row.querySelector('td:nth-child(4) span');
                        statusCell.textContent = 'Cancelled';
                        statusCell.className = 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800';
                        btn.disabled = true;
                        btn.classList.add('opacity-50', 'cursor-not-allowed');
                    } else {
                        alert('Failed to cancel appointment.');
                    }
                });
            }
        });
    });
});
</script>
