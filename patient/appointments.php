<?php
include '../includep/header.php';
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
        <form id="bookApptForm">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input type="date" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" min="2025-05-16" required />
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Time</label>
                <input type="time" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" required />
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                <select class="w-full border border-gray-300 rounded px-3 py-2 text-sm" required>
                    <option value="">Select reason</option>
                    <option value="consultation">Consultation</option>
                    <option value="followup">Follow-up</option>
                    <option value="medicine">Request Medicine</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <button type="submit" class="w-full bg-primary text-white py-2 rounded hover:bg-primary/90">Book Appointment</button>
        </form>
    </div>
    <!-- My Appointments Table -->
    <div class="bg-white rounded shadow p-6">
        <h3 class="text-lg font-semibold mb-4">My Appointments</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Date</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Time</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Reason</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Status</th>
                        <th class="px-4 py-2 text-center font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="px-4 py-2">2025-05-18</td>
                        <td class="px-4 py-2">09:00 AM</td>
                        <td class="px-4 py-2">Consultation</td>
                        <td class="px-4 py-2"><span class="inline-block px-2 py-1 rounded bg-yellow-100 text-yellow-800 text-xs">Pending</span></td>
                        <td class="px-4 py-2 text-center">
                            <button class="cancelBtn px-2 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600 mr-1">Cancel</button>
                            <button class="reschedBtn px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600">Reschedule</button>
                        </td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2">2025-05-10</td>
                        <td class="px-4 py-2">10:30 AM</td>
                        <td class="px-4 py-2">Request Medicine</td>
                        <td class="px-4 py-2"><span class="inline-block px-2 py-1 rounded bg-green-100 text-green-800 text-xs">Confirmed</span></td>
                        <td class="px-4 py-2 text-center">
                            <button class="cancelBtn px-2 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600 mr-1">Cancel</button>
                            <button class="reschedBtn px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600">Reschedule</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</main>
<script>
document.getElementById('bookApptForm').addEventListener('submit', function(e) {
    e.preventDefault();
    alert('Appointment booked!');
});
const cancelBtns = document.querySelectorAll('.cancelBtn');
const reschedBtns = document.querySelectorAll('.reschedBtn');
cancelBtns.forEach(btn => btn.addEventListener('click', () => alert('Appointment cancelled!')));
reschedBtns.forEach(btn => btn.addEventListener('click', () => alert('Reschedule dialog would open.')));
</script>

<?php
include '../includep/footer.php';
?>