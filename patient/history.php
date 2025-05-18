<?php
include '../includep/header.php';
?>

<main class="flex-1 overflow-y-auto bg-gray-50 p-6 ml-16 md:ml-64 mt-[56px]">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Medical History</h2>
    <!-- Filters -->
    <div class="flex flex-wrap gap-4 items-end mb-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
            <input type="date" class="border border-gray-300 rounded px-3 py-2 text-sm" />
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Illness</label>
            <input type="text" class="border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Search illness..." />
        </div>
    </div>
    <!-- Visit Log Table -->
    <div class="bg-white rounded shadow p-6 mb-8">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Visit Log</h3>
            <button class="px-4 py-2 bg-primary text-white rounded hover:bg-primary/90 flex items-center"><i class="ri-download-2-line mr-1"></i> Download as PDF</button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Date</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Symptoms</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Diagnosis</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Treatment</th>
                        <th class="px-4 py-2 text-center font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="px-4 py-2">2025-05-10</td>
                        <td class="px-4 py-2">Fever, cough</td>
                        <td class="px-4 py-2">Flu</td>
                        <td class="px-4 py-2">Paracetamol, rest</td>
                        <td class="px-4 py-2 text-center">
                            <button class="viewRecordBtn px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600" data-date="2025-05-10">View Full Record</button>
                        </td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2">2025-04-22</td>
                        <td class="px-4 py-2">Headache</td>
                        <td class="px-4 py-2">Migraine</td>
                        <td class="px-4 py-2">Ibuprofen</td>
                        <td class="px-4 py-2 text-center">
                            <button class="viewRecordBtn px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600" data-date="2025-04-22">View Full Record</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Full Record Modal -->
    <div id="fullRecordModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
            <button id="closeFullRecordModal" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700">
                <i class="ri-close-line ri-2x"></i>
            </button>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Full Visit Record</h3>
            <div id="modalRecordDetails" class="text-sm text-gray-700">
                <!-- Full record details will be shown here -->
            </div>
        </div>
    </div>
</main>
<script>
// Demo modal logic
const viewBtns = document.querySelectorAll('.viewRecordBtn');
const fullRecordModal = document.getElementById('fullRecordModal');
const closeFullRecordModal = document.getElementById('closeFullRecordModal');
const modalRecordDetails = document.getElementById('modalRecordDetails');
viewBtns.forEach(btn => {
    btn.addEventListener('click', function() {
        // Demo details by date
        if(this.dataset.date === '2025-05-10') {
            modalRecordDetails.innerHTML = '<p><b>Date:</b> 2025-05-10</p><p><b>Symptoms:</b> Fever, cough</p><p><b>Diagnosis:</b> Flu</p><p><b>Treatment:</b> Paracetamol, rest</p><p><b>Notes:</b> Advised to rest for 3 days. No complications observed.</p>';
        } else {
            modalRecordDetails.innerHTML = '<p><b>Date:</b> 2025-04-22</p><p><b>Symptoms:</b> Headache</p><p><b>Diagnosis:</b> Migraine</p><p><b>Treatment:</b> Ibuprofen</p><p><b>Notes:</b> Patient responded well to medication. Follow-up if symptoms persist.</p>';
        }
        fullRecordModal.classList.remove('hidden');
    });
});
closeFullRecordModal.addEventListener('click', () => fullRecordModal.classList.add('hidden'));
window.addEventListener('click', (e) => {
    if (e.target === fullRecordModal) fullRecordModal.classList.add('hidden');
});
</script>

<?php
include '../includep/footer.php';
?>