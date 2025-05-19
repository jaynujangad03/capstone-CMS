<?php
include '../includes/header.php';

$conn = new mysqli('localhost', 'root', '', 'clinic_management_system');
if ($conn->connect_errno) {
    die('Database connection failed: ' . $conn->connect_error);
}

$appointments = [];
// Use only columns that exist in the appointments table
$sql = 'SELECT a.date, a.time, a.reason, a.status, ip.name FROM appointments a JOIN imported_patients ip ON a.student_id = ip.id ORDER BY a.date DESC, a.time DESC';
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
    <!-- Appointment Table -->
    <div class="bg-white rounded shadow p-6">
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
                            <?php if ($appt['status'] !== 'cancelled'): ?>
                            <tr>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($appt['name']); ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($appt['date']); ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($appt['time']); ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($appt['reason']); ?></td>
                                <td class="px-4 py-2">
                                    <?php if ($appt['status'] === 'pending'): ?>
                                        <span class="inline-block px-2 py-1 rounded bg-yellow-100 text-yellow-800 text-xs">Pending</span>
                                    <?php elseif ($appt['status'] === 'approved' || $appt['status'] === 'confirmed'): ?>
                                        <span class="inline-block px-2 py-1 rounded bg-green-100 text-green-800 text-xs">Approved</span>
                                    <?php elseif ($appt['status'] === 'declined'): ?>
                                        <span class="inline-block px-2 py-1 rounded bg-red-100 text-red-800 text-xs">Declined</span>
                                    <?php else: ?>
                                        <span class="inline-block px-2 py-1 rounded bg-gray-100 text-gray-800 text-xs"><?php echo htmlspecialchars(ucfirst($appt['status'])); ?></span>
                                    <?php endif; ?>
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
                        <tr><td colspan="6" class="px-4 py-2 text-center text-gray-400">No appointments found.</td></tr>
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
    reschedBtns.forEach(btn => btn.addEventListener('click', () => alert('Reschedule dialog would open.')));
</script>

<?php
include '../includes/footer.php';
?>