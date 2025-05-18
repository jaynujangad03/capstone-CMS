<?php
include '../includep/header.php';
?>

<main class="flex-1 overflow-y-auto bg-gray-50 p-6 ml-16 md:ml-64 mt-[56px]">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Notifications</h2>
    <div class="bg-white rounded shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Notification Feed</h3>
        <ul class="divide-y divide-gray-200">
            <li class="flex items-start gap-4 py-4">
                <div class="flex-shrink-0 mt-1"><i class="ri-calendar-check-line text-primary text-2xl"></i></div>
                <div class="flex-1">
                    <div class="font-medium text-gray-800">Appointment Confirmed</div>
                    <div class="text-gray-600 text-sm">Your appointment for <b>2025-05-18 09:00 AM</b> has been <span class="text-green-600 font-semibold">confirmed</span>.</div>
                    <div class="text-xs text-gray-400 mt-1">Today, 08:15 AM</div>
                </div>
                <div class="flex flex-col gap-1">
                    <button class="markReadBtn text-xs text-primary hover:underline">Mark as read</button>
                    <button class="deleteBtn text-xs text-red-500 hover:underline">Delete</button>
                </div>
            </li>
            <li class="flex items-start gap-4 py-4 bg-yellow-50">
                <div class="flex-shrink-0 mt-1"><i class="ri-capsule-line text-yellow-500 text-2xl"></i></div>
                <div class="flex-1">
                    <div class="font-medium text-gray-800">Medication Issued</div>
                    <div class="text-gray-600 text-sm">Ibuprofen was issued to you on <b>2025-05-15</b>.</div>
                    <div class="text-xs text-gray-400 mt-1">Yesterday, 03:20 PM</div>
                </div>
                <div class="flex flex-col gap-1">
                    <button class="markReadBtn text-xs text-primary hover:underline">Mark as read</button>
                    <button class="deleteBtn text-xs text-red-500 hover:underline">Delete</button>
                </div>
            </li>
            <li class="flex items-start gap-4 py-4">
                <div class="flex-shrink-0 mt-1"><i class="ri-file-list-2-line text-blue-500 text-2xl"></i></div>
                <div class="flex-1">
                    <div class="font-medium text-gray-800">Record Added</div>
                    <div class="text-gray-600 text-sm">A new medical record was added to your history for <b>2025-05-10</b>.</div>
                    <div class="text-xs text-gray-400 mt-1">2025-05-10, 11:00 AM</div>
                </div>
                <div class="flex flex-col gap-1">
                    <button class="markReadBtn text-xs text-primary hover:underline">Mark as read</button>
                    <button class="deleteBtn text-xs text-red-500 hover:underline">Delete</button>
                </div>
            </li>
        </ul>
    </div>
</main>
<script>
// Demo mark as read/delete logic
const markReadBtns = document.querySelectorAll('.markReadBtn');
const deleteBtns = document.querySelectorAll('.deleteBtn');
markReadBtns.forEach(btn => btn.addEventListener('click', function() {
    this.closest('li').classList.add('opacity-50');
}));
deleteBtns.forEach(btn => btn.addEventListener('click', function() {
    this.closest('li').remove();
}));
</script>

<?php
include '../includep/footer.php';
?>