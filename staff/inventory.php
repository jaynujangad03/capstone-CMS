<?php
include '../includes/header.php';
// Create medicines table if not exists
try {
    $db = new PDO('mysql:host=localhost;dbname=clinic_management_system;charset=utf8', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("CREATE TABLE IF NOT EXISTS medicines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        dosage VARCHAR(255) NOT NULL,
        quantity INT NOT NULL,
        expiry DATE NOT NULL
    )");
    // Fetch all medicines for the table
    $medicines = $db->query('SELECT * FROM medicines ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Fetch prescription history for Issue Medication History
$prescriptionHistory = [];
try {
    $prescStmt = $db->query('SELECT prescription_date, patient_name, medicines FROM prescriptions ORDER BY prescription_date DESC');
    $prescriptionHistory = $prescStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $prescriptionHistory = [];
}

// Fetch pending prescriptions for Patients with Pending Prescriptions
$pendingPrescriptions = [];
try {
    $pendingStmt = $db->query('SELECT * FROM pending_prescriptions ORDER BY prescription_date ASC');
    $pendingPrescriptions = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $pendingPrescriptions = [];
}

// Pagination for Issue Medication History
// Flatten prescription history so each medicine entry is a separate row (even for same patient)
$flatPrescriptionHistory = [];
foreach ($prescriptionHistory as $presc) {
    $date = $presc['prescription_date'];
    $patient = $presc['patient_name'];
    $meds = json_decode($presc['medicines'], true);
    if (is_array($meds)) {
        foreach ($meds as $med) {
            $flatPrescriptionHistory[] = [
                'prescription_date' => $date,
                'patient_name' => $patient,
                'medicine' => $med['medicine'] ?? '',
                'quantity' => $med['quantity'] ?? ''
            ];
        }
    }
}
$historyPage = isset($_GET['history_page']) ? max(1, intval($_GET['history_page'])) : 1;
$historyPerPage = 10;
$historyTotal = count($flatPrescriptionHistory);
$historyTotalPages = ceil($historyTotal / $historyPerPage);
$historyStart = ($historyPage - 1) * $historyPerPage;
$historyPageData = array_slice($flatPrescriptionHistory, $historyStart, $historyPerPage);
?>
<!-- Dashboard Content -->
<main class="flex-1 overflow-y-auto bg-gray-50 p-6 ml-16 md:ml-64 mt-[56px]">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Inventory</h2>
    <!-- Filters -->
    <div class="flex flex-wrap gap-4 items-end mb-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Filter</label>
            <select id="medicineFilter" class="border border-gray-300 rounded px-3 py-2 text-sm">
                <option value="all">All</option>
                <?php foreach ($medicines as $med): ?>
                    <option value="<?php echo htmlspecialchars($med['name']); ?>"><?php echo htmlspecialchars($med['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <input id="medicineSearch" type="text" placeholder="Search medicine..." class="border border-gray-300 rounded px-3 py-2 text-sm" />
        <button id="addMedBtn"
            class="ml-auto px-4 py-2 bg-primary text-white rounded hover:bg-primary/90 flex items-center"><i
                class="ri-add-line mr-1"></i> Add Medicine</button>
    </div>
    <!-- Medicine List Table -->
    <div class="bg-white rounded shadow p-6 mb-8">
        <h3 class="text-lg font-semibold mb-4">Medicine List</h3>
        <div class="overflow-x-auto">
            <table id="medicineTable" class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Name</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Dosage</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Quantity</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Date Added</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Expiry</th>
                        <th class="px-4 py-2 text-center font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($medicines as $med): ?>
                    <tr data-id="<?php echo $med['id']; ?>" data-name="<?php echo htmlspecialchars(strtolower($med['name'])); ?>">
                        <td class="px-4 py-2"><?php echo htmlspecialchars($med['name']); ?></td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($med['dosage']); ?></td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($med['quantity']); ?></td>
                        <td class="px-4 py-2"><?php echo isset($med['created_at']) ? htmlspecialchars($med['created_at']) : '-'; ?></td>
                        <td class="px-4 py-2<?php echo (strtotime($med['expiry']) < strtotime('+30 days')) ? ' text-red-600 font-semibold' : ''; ?>"><?php echo htmlspecialchars($med['expiry']); ?></td>
                        <td class="px-4 py-2 text-center">
                            <button class="editMedBtn px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600 mr-1">Edit</button>
                            <button class="deleteMedBtn px-2 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Issue Medication History -->
    <div class="bg-white rounded shadow p-6 mb-8">
        <h3 class="text-lg font-semibold mb-4">Issue Medication History</h3>
        <div class="overflow-x-auto">
            <table id="issueHistoryTable" class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Date</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Patient</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Medicine</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($historyPageData)) {
                        foreach ($historyPageData as $idx => $row) {
                            $date = htmlspecialchars($row['prescription_date']);
                            $patient = htmlspecialchars($row['patient_name']);
                            $medName = htmlspecialchars($row['medicine']);
                            $qty = htmlspecialchars($row['quantity']);
                            echo "<tr>";
                            echo "<td class='px-4 py-2 flex items-center gap-2'>";
                            echo "<button class='viewHistoryBtn text-primary hover:text-blue-700' data-idx='{$idx}' title='View Details'><i class='ri-eye-line text-lg'></i></button>";
                            echo $date;
                            echo "</td>";
                            echo "<td class='px-4 py-2'>" . $patient . "</td>";
                            echo "<td class='px-4 py-2'>" . $medName . "</td>";
                            echo "<td class='px-4 py-2'>" . $qty . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' class='px-4 py-2 text-center text-gray-500'>No prescription history found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <div class="flex flex-col md:flex-row md:justify-between md:items-center mt-4 gap-2">
            <div class="text-sm text-gray-600">
                Showing
                <span class="font-semibold">
                    <?php echo $historyTotal == 0 ? 0 : ($historyStart + 1); ?>
                </span>
                to
                <span class="font-semibold">
                    <?php echo min($historyStart + $historyPerPage, $historyTotal); ?>
                </span>
                of
                <span class="font-semibold">
                    <?php echo $historyTotal; ?>
                </span>
                entries
            </div>
            <div class="flex flex-wrap gap-1">
                <?php if ($historyTotalPages > 1): ?>
                    <?php if ($historyPage > 1): ?>
                        <a href="?history_page=<?php echo $historyPage-1; ?>" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Prev</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $historyTotalPages; $i++): ?>
                        <a href="?history_page=<?php echo $i; ?>" class="px-3 py-1 rounded <?php echo $i == $historyPage ? 'bg-primary text-white' : 'bg-gray-200 hover:bg-gray-300'; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <?php if ($historyPage < $historyTotalPages): ?>
                        <a href="?history_page=<?php echo $historyPage+1; ?>" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Next</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Patients with Pending Prescriptions List -->
    <div class="bg-white rounded shadow p-6 mb-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Patients with Pending Prescriptions</h3>
            <button id="issuePendingBtn" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">Issue Selected</button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-2 py-2"><input type="checkbox" id="selectAllPending"></th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Patient ID</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Name</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Prescribed Medicine(s)</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Dosage</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Quantity</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Status</th>
                        <th class="px-4 py-2 text-center font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($pendingPrescriptions)) {
                        foreach ($pendingPrescriptions as $pending) {
                            $meds = json_decode($pending['medicines'], true);
                            echo '<tr data-id="' . htmlspecialchars($pending['id']) . '">';
                            echo '<td class="px-2 py-2"><input type="checkbox" class="pendingCheckbox"></td>';
                            echo '<td class="px-4 py-2">' . htmlspecialchars($pending['patient_id']) . '</td>';
                            echo '<td class="px-4 py-2">' . htmlspecialchars($pending['patient_name']) . '</td>';
                            // Prescribed Medicine(s) column: show all medicines, pipe separated
                            if (is_array($meds) && count($meds) > 0) {
                                $medNames = array_map(function($m) { return htmlspecialchars($m['medicine'] ?? '-'); }, $meds);
                                echo '<td class="px-4 py-2">' . implode(' | ', $medNames) . '</td>';
                                // Dosage column: show all dosages, pipe separated
                                $dosages = array_map(function($m) { return htmlspecialchars($m['dosage'] ?? '-'); }, $meds);
                                echo '<td class="px-4 py-2">' . implode(' | ', $dosages) . '</td>';
                                // Quantity column: show all quantities, pipe separated
                                $quantities = array_map(function($m) { return htmlspecialchars($m['quantity'] ?? '-'); }, $meds);
                                echo '<td class="px-4 py-2">' . implode(' | ', $quantities) . '</td>';
                            } else {
                                echo '<td class="px-4 py-2">-</td>';
                                echo '<td class="px-4 py-2">-</td>';
                                echo '<td class="px-4 py-2">-</td>';
                            }
                            echo '<td class="px-4 py-2"><span class="inline-block px-2 py-1 rounded bg-yellow-100 text-yellow-800 text-xs">Pending</span></td>';
                            echo '<td class="px-4 py-2 text-center">';
                            echo '<button class="issuePendingSingleBtn px-2 py-1 text-xs bg-green-500 text-white rounded hover:bg-green-600" onclick="issuePrescription([\'' . htmlspecialchars($pending['id']) . '\'])">Issue</button>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="8" class="px-4 py-2 text-center text-gray-500">No pending prescriptions found.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Add Medicine Modal -->
    <div id="addMedModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
            <button id="closeAddMedModal" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700">
                <i class="ri-close-line ri-2x"></i>
            </button>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Add Medicine</h3>
            <form id="addMedForm">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Medicine Name</label>
                    <input type="text" name="name" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" required />
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dosage</label>
                    <input type="text" name="dosage" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" required />
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                    <input type="number" name="quantity" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" min="1" required />
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label>
                    <input type="date" name="expiry" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" required />
                </div>
                <button type="submit" class="w-full bg-primary text-white py-2 rounded hover:bg-primary/90">Add
                    Medicine</button>
            </form>
        </div>
    </div>
    <!-- Add Edit Medicine Modal (hidden by default) -->
    <div id="editMedModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
            <button id="closeEditMedModal" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700">
                <i class="ri-close-line ri-2x"></i>
            </button>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Edit Medicine</h3>
            <form id="editMedForm">
                <input type="hidden" name="id" id="editMedId">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Medicine Name</label>
                    <input type="text" name="name" id="editMedName" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" required />
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dosage</label>
                    <input type="text" name="dosage" id="editMedDosage" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" required />
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                    <input type="number" name="quantity" id="editMedQuantity" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" min="1" required />
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label>
                    <input type="date" name="expiry" id="editMedExpiry" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" required />
                </div>
                <button type="submit" class="w-full bg-primary text-white py-2 rounded hover:bg-primary/90">Save Changes</button>
            </form>
        </div>
    </div>
    <!-- Modal for viewing prescription details -->
    <div id="historyViewModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
            <button id="closeHistoryViewModal" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700">
                <i class="ri-close-line ri-2x"></i>
            </button>
            <h3 id="historyViewModalTitle" class="text-lg font-semibold text-gray-800 mb-4"></h3>
            <div id="historyViewModalBody" class="text-sm text-gray-700 space-y-2"></div>
        </div>
    </div>
</main>
<style>
.dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_processing, .dataTables_wrapper .dataTables_paginate {
    color: inherit;
}
.dataTables_wrapper .dataTables_paginate {
    float: right;
    text-align: right;
    padding-top: .25em;
}
</style>
<script>
    // Modal logic
    const addMedBtn = document.getElementById('addMedBtn');
    const addMedModal = document.getElementById('addMedModal');
    const closeAddMedModal = document.getElementById('closeAddMedModal');
    addMedBtn.addEventListener('click', () => addMedModal.classList.remove('hidden'));
    closeAddMedModal.addEventListener('click', () => addMedModal.classList.add('hidden'));
    window.addEventListener('click', (e) => {
        if (e.target === addMedModal) addMedModal.classList.add('hidden');
    });
    // Prevent form submit (demo)
    document.querySelector('#addMedModal form').addEventListener('submit', function (e) {
        e.preventDefault();
        alert('Medicine added!');
        addMedModal.classList.add('hidden');
    });

    // Patients with Pending Prescriptions List logic
    const selectAllPending = document.getElementById('selectAllPending');
    const issuePendingBtn = document.getElementById('issuePendingBtn');

    if(selectAllPending) {
        selectAllPending.addEventListener('change', function() {
            document.querySelectorAll('.pendingCheckbox').forEach(cb => cb.checked = this.checked);
        });
    }

    // Issue Pending Prescription logic
    function issuePrescription(ids) {
        fetch('issue_prescription.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'ids=' + encodeURIComponent(JSON.stringify(ids))
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                alert('Prescribed meds issued and deducted from inventory!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(() => alert('Error issuing prescription.'));
    }

    if(issuePendingBtn) {
        issuePendingBtn.addEventListener('click', function() {
            const selected = Array.from(document.querySelectorAll('.pendingCheckbox'))
                .filter(cb => cb.checked)
                .map(cb => cb.closest('tr').getAttribute('data-id'));
            if(selected.length === 0) {
                alert('No patients selected.');
                return;
            }
            issuePrescription(selected);
        });
    }
    // Single Issue button: always issues only its row, does not depend on checkbox
    // (No need to disable checkbox, just issue the row's ID)
    document.querySelectorAll('.issuePendingSingleBtn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const id = this.closest('tr').getAttribute('data-id');
            issuePrescription([id]);
        });
    });

    // Add Medicine Modal logic with backend integration
    document.querySelector('#addMedModal form').addEventListener('submit', function (e) {
        e.preventDefault();
        const name = this.querySelector('input[name="name"]').value;
        const dosage = this.querySelector('input[name="dosage"]').value;
        const quantity = this.querySelector('input[name="quantity"]').value;
        const expiry = this.querySelector('input[name="expiry"]').value;
        fetch('add_medicine.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `name=${encodeURIComponent(name)}&dosage=${encodeURIComponent(dosage)}&quantity=${encodeURIComponent(quantity)}&expiry=${encodeURIComponent(expiry)}`
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                alert('Medicine added!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(() => alert('Error adding medicine.'));
        addMedModal.classList.add('hidden');
    });

    // Edit Medicine Modal logic
    const editMedModal = document.getElementById('editMedModal');
    const closeEditMedModal = document.getElementById('closeEditMedModal');
    const editMedForm = document.getElementById('editMedForm');
    let currentEditRow = null;
    document.querySelectorAll('.editMedBtn').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            currentEditRow = row;
            const id = row.getAttribute('data-id');
            const name = row.children[0].textContent.trim();
            const dosage = row.children[1].textContent.trim();
            const quantity = row.children[2].textContent.trim();
            const expiry = row.children[4].textContent.trim();
            document.getElementById('editMedId').value = id;
            document.getElementById('editMedName').value = name;
            document.getElementById('editMedDosage').value = dosage;
            document.getElementById('editMedQuantity').value = quantity;
            document.getElementById('editMedExpiry').value = expiry;
            editMedModal.classList.remove('hidden');
        });
    });
    closeEditMedModal.addEventListener('click', () => editMedModal.classList.add('hidden'));
    window.addEventListener('click', (e) => {
        if (e.target === editMedModal) editMedModal.classList.add('hidden');
    });
    editMedForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const id = document.getElementById('editMedId').value;
        const name = document.getElementById('editMedName').value;
        const dosage = document.getElementById('editMedDosage').value;
        const quantity = document.getElementById('editMedQuantity').value;
        const expiry = document.getElementById('editMedExpiry').value;
        fetch('edit_medicine.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${encodeURIComponent(id)}&name=${encodeURIComponent(name)}&dosage=${encodeURIComponent(dosage)}&quantity=${encodeURIComponent(quantity)}&expiry=${encodeURIComponent(expiry)}`
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                alert('Medicine updated!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(() => alert('Error updating medicine.'));
        editMedModal.classList.add('hidden');
    });
    // Delete Medicine logic
    const deleteMedBtns = document.querySelectorAll('.deleteMedBtn');
    deleteMedBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            const id = row.getAttribute('data-id');
            if(confirm('Are you sure you want to delete this medicine?')) {
                fetch('delete_medicine.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${encodeURIComponent(id)}`
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        alert('Medicine deleted!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(() => alert('Error deleting medicine.'));
            }
        });
    });

    // Medicine filter and search logic
    const medicineFilter = document.getElementById('medicineFilter');
    const medicineSearch = document.getElementById('medicineSearch');
    const medicineTable = document.getElementById('medicineTable');
    medicineFilter.addEventListener('change', filterMedicines);
    medicineSearch.addEventListener('input', filterMedicines);
    function filterMedicines() {
        const filter = medicineFilter.value.toLowerCase();
        const search = medicineSearch.value.toLowerCase();
        Array.from(medicineTable.querySelectorAll('tbody tr')).forEach(row => {
            const name = row.getAttribute('data-name');
            const matchesFilter = (filter === 'all' || name === filter);
            const matchesSearch = name.includes(search);
            row.style.display = (matchesFilter && matchesSearch) ? '' : 'none';
        });
    }

    // Issue Medication History View Modal logic
    const historyData = <?php echo json_encode(array_values($historyPageData)); ?>;
    const viewBtns = document.querySelectorAll('.viewHistoryBtn');
    const viewModal = document.getElementById('historyViewModal');
    const closeViewModal = document.getElementById('closeHistoryViewModal');
    const viewModalTitle = document.getElementById('historyViewModalTitle');
    const viewModalBody = document.getElementById('historyViewModalBody');
    viewBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const idx = this.getAttribute('data-idx');
            const row = historyData[idx];
            viewModalTitle.textContent = row.patient_name;
            viewModalBody.innerHTML = `
                <div><span class='font-semibold'>Date:</span> ${row.prescription_date}</div>
                <div><span class='font-semibold'>Patient:</span> ${row.patient_name}</div>
                <div><span class='font-semibold'>Medicine:</span> ${row.medicine}</div>
                <div><span class='font-semibold'>Quantity:</span> ${row.quantity}</div>
            `;
            viewModal.classList.remove('hidden');
        });
    });
    closeViewModal.addEventListener('click', () => viewModal.classList.add('hidden'));
    window.addEventListener('click', (e) => {
        if (e.target === viewModal) viewModal.classList.add('hidden');
    });
</script>
<?php
include '../includes/footer.php';
?>