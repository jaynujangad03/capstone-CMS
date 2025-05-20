<?php
include '../includes/header.php';
try {
    $db = new PDO('mysql:host=localhost;dbname=clinic_management_system;charset=utf8', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Create prescriptions table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS prescriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT,
        patient_name VARCHAR(255),
        prescribed_by VARCHAR(255),
        prescription_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        medicines TEXT,
        notes TEXT
    )");
    // Create pending_prescriptions table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS pending_prescriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT,
        patient_name VARCHAR(255),
        prescribed_by VARCHAR(255),
        prescription_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        medicines TEXT,
        notes TEXT
    )");
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Fetch medicines from DB for dropdown
$medicines = [];
try {
    $medStmt = $db->query('SELECT name, quantity FROM medicines ORDER BY name ASC');
    $medicines = $medStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $medicines = [];
}
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<main class="flex-1 overflow-y-auto bg-gray-50 p-6 ml-16 md:ml-64 mt-[56px]">
    <div class="max-w-5xl mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Patient Records</h2>
        <!-- Search Bar -->
        <div class="mb-4 flex items-center gap-2">
            <input id="searchInput" type="text" class="w-full max-w-xs border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Search patients...">
        </div>
        <!-- Patient Table -->
        <div class="bg-white rounded shadow p-6">
            <div class="overflow-x-auto">
                <table id="importedPatientsTable" class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600">ID</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600">Student ID</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600">Name</th>
                            <th class="px-4 py-2 text-center font-semibold text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $db->query('SELECT id, student_id, name, dob, gender, address, civil_status, year_level, MAX(dob) as last_visit FROM imported_patients GROUP BY id, student_id, name ORDER BY id DESC');
                        foreach ($stmt as $row): ?>
                        <tr>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($row['id']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($row['student_id']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td class="px-4 py-2 text-center">
                                <button class="viewBtn px-3 py-1 text-xs bg-primary text-white rounded hover:bg-primary/90" data-name="<?php echo htmlspecialchars($row['name']); ?>" data-id="<?php echo htmlspecialchars($row['id']); ?>" data-student_id="<?php echo htmlspecialchars($row['student_id']); ?>" data-gender="<?php echo htmlspecialchars($row['gender']); ?>" data-year="<?php echo htmlspecialchars($row['year_level']); ?>" data-address="<?php echo htmlspecialchars($row['address']); ?>" data-civil="<?php echo htmlspecialchars($row['civil_status']); ?>">View</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Profile Modal -->
    <div id="profileModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
            <button id="closeProfileModal" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700">
                <i class="ri-close-line ri-2x"></i>
            </button>
            <h3 class="text-lg font-semibold text-gray-800 mb-4" id="modalPatientName">Patient Profile</h3>
            <div id="modalPatientDetails" class="text-sm text-gray-700 mb-4">
                <!-- Patient details will be shown here -->
            </div>
            <button id="prescribeMedBtn" class="w-full bg-green-500 text-white py-2 rounded hover:bg-green-600 flex items-center justify-center mb-2"><i class="ri-capsule-line mr-2"></i>Prescribe Medicine</button>
        </div>
    </div>
    <!-- Prescribe Medicine Modal -->
    <div id="prescribeMedModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6 relative prescribe-modal-scroll">
            <button id="closePrescribeMedModal" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700">
                <i class="ri-close-line ri-2x"></i>
            </button>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Prescribe Medicine</h3>
            <form id="prescribeMedForm">
                <div id="medsList">
                    <div class="medRow mb-4 border-b pb-4">
                        <div class="mb-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Medicine</label>
                            <select class="medicineSelect w-full border border-gray-300 rounded px-3 py-2 text-sm" required>
                                <option value="">Select medicine</option>
                                <?php foreach ($medicines as $med): ?>
                                    <option value="<?php echo htmlspecialchars($med['name']); ?>" data-stock="<?php echo htmlspecialchars($med['quantity']); ?>">
                                        <?php echo htmlspecialchars($med['name']); ?> (<?php echo htmlspecialchars($med['quantity']); ?> in stock)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex gap-2 mb-2">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Dosage</label>
                                <input type="text" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="e.g. 500mg" required />
                            </div>
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                                <input type="number" class="w-full border border-gray-300 rounded px-3 py-2 text-sm qtyInput" min="1" required />
                                <span class="text-xs text-gray-500 stockMsg"></span>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Frequency</label>
                            <input type="text" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="e.g. 3x a day" required />
                        </div>
                        <div class="mb-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Instructions</label>
                            <input type="text" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="e.g. After meals" />
                        </div>
                        <button type="button" class="removeMedBtn text-xs text-red-500 hover:underline mt-1">Remove</button>
                    </div>
                </div>
                <button type="button" id="addMedRowBtn" class="mb-4 px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">+ Add Another Medicine</button>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea class="w-full border border-gray-300 rounded px-3 py-2 text-sm" rows="2" placeholder="Additional info..."></textarea>
                </div>
                <button type="submit" class="w-full bg-primary text-white py-2 rounded hover:bg-primary/90">Submit Prescription</button>
            </form>
        </div>
    </div>
</main>

<script>
$(document).ready(function() {
    var table = $('#importedPatientsTable').DataTable({
        "pageLength": 10,
        "lengthChange": false,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "language": {
            "paginate": {
                "previous": "Prev",
                "next": "Next"
            }
        },
        "dom": 'lrtip'
    });
    // Connect the custom search bar to the table: filter by Name only (case-insensitive, trimmed)
    $('#searchInput').on('input', function() {
        var val = this.value ? this.value.trim() : '';
        // Remove any previous custom search
        $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(function(fn) {
            return !fn._isNameSearch;
        });
        if (val) {
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                // Name is column index 2 in the DataTable (ID, Student ID, Name, Actions)
                var name = (data[2] || '').toLowerCase();
                return name.indexOf(val.toLowerCase()) !== -1;
            });
            $.fn.dataTable.ext.search[$.fn.dataTable.ext.search.length-1]._isNameSearch = true;
        }
        table.draw();
    });
    // Remove default DataTables search effect (fix infinite loop)
    // table.on('search.dt', function() {
    //     table.search('').draw(false);
    // });
    // Custom filtering for Year Level and Gender
    $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(function(fn) {
        return !fn._isYearGenderFilter;
    });
    $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex) {
            var yearLevel = $('#yearLevelFilter').val();
            var gender = $('#genderFilter').val();
            // year_level is column 8, gender is column 4 in the DB, but in the table only a subset is shown
            // Table columns: 0:ID, 1:Student ID, 2:Name, 3:Actions
            // If you want to filter by year_level and gender, you must add those columns to the table or fetch them from data attributes
            return true;
        }
    );
    $('#yearLevelFilter, #genderFilter').on('change', function() {
        table.draw();
    });
    // View button logic
    $(document).on('click', '.viewBtn', function() {
        const name = $(this).data('name');
        const id = $(this).data('id');
        const gender = $(this).data('gender');
        const year = $(this).data('year');
        const address = $(this).data('address');
        const civil = $(this).data('civil');
        $('#modalPatientName').text(name + ' (' + id + ')');
        $('#modalPatientDetails').html(
            `<p>Year Level: ${year}</p><p>Gender: ${gender}</p><p>Address: ${address}</p><p>Civil Status: ${civil}</p>`
        );
        $('#profileModal').removeClass('hidden');
    });
    $('#closeProfileModal').on('click', function() {
        $('#profileModal').addClass('hidden');
    });
    $(window).on('click', function(e) {
        if (e.target === document.getElementById('profileModal')) $('#profileModal').addClass('hidden');
    });
    // Prescribe Medicine Modal logic
    let currentPatientName = '';
    $(document).on('click', '.viewBtn', function() {
        const name = $(this).data('name');
        const id = $(this).data('id');
        const gender = $(this).data('gender');
        const year = $(this).data('year');
        const address = $(this).data('address');
        const civil = $(this).data('civil');
        $('#modalPatientName').text(name + ' (' + id + ')');
        $('#modalPatientDetails').html(
            `<p>Year Level: ${year}</p><p>Gender: ${gender}</p><p>Address: ${address}</p><p>Civil Status: ${civil}</p>`
        );
        $('#profileModal').removeClass('hidden');
        currentPatientName = name;
    });
    $('#prescribeMedBtn').on('click', function() {
        $('#prescribeMedModal').removeClass('hidden');
        // Show patient name in the prescribe modal title
        $('#prescribeMedModal h3').text('Prescribe Medicine for ' + currentPatientName);
    });
    $('#closePrescribeMedModal').on('click', function() {
        $('#prescribeMedModal').addClass('hidden');
        // Reset modal title
        $('#prescribeMedModal h3').text('Prescribe Medicine');
    });
    $(window).on('click', function(e) {
        if (e.target === document.getElementById('prescribeMedModal')) $('#prescribeMedModal').addClass('hidden');
    });
    // Add Medicine Row
    $('#addMedRowBtn').on('click', function() {
        var newRow = `<div class="medRow mb-4 border-b pb-4">
                            <div class="mb-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Medicine</label>
                                <select class="medicineSelect w-full border border-gray-300 rounded px-3 py-2 text-sm" required>
                                    <option value="">Select medicine</option>
                                    <?php foreach ($medicines as $med): ?>
                                        <option value="<?php echo htmlspecialchars($med['name']); ?>" data-stock="<?php echo htmlspecialchars($med['quantity']); ?>">
                                            <?php echo htmlspecialchars($med['name']); ?> (<?php echo htmlspecialchars($med['quantity']); ?> in stock)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="flex gap-2 mb-2">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Dosage</label>
                                    <input type="text" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="e.g. 500mg" required />
                                </div>
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                                    <input type="number" class="w-full border border-gray-300 rounded px-3 py-2 text-sm qtyInput" min="1" required />
                                    <span class="text-xs text-gray-500 stockMsg"></span>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Frequency</label>
                                <input type="text" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="e.g. 3x a day" required />
                            </div>
                            <div class="mb-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Instructions</label>
                                <input type="text" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="e.g. After meals" />
                            </div>
                            <button type="button" class="removeMedBtn text-xs text-red-500 hover:underline mt-1">Remove</button>
                        </div>`;
        $('#medsList').append(newRow);
    });
    // Remove Medicine Row
    $(document).on('click', '.removeMedBtn', function() {
        $(this).closest('.medRow').remove();
    });
    // Submit Prescription Form
    $('#prescribeMedForm').on('submit', function(e) {
        e.preventDefault();
        var isValid = true;
        // Clear previous errors and success
        $('#prescribeMedForm .error-msg, #prescribeMedForm .success-msg').remove();
        // Validate all required fields in each medRow
        $('.medRow').each(function() {
            var row = $(this);
            row.find('select, input[required]').each(function() {
                if (!$(this).val()) {
                    isValid = false;
                    if ($(this).next('.error-msg').length === 0) {
                        $('<div class="error-msg text-xs text-red-500 mt-1 animate-fade-in">This field is required.</div>').insertAfter($(this));
                    }
                }
            });
        });
        if (!isValid) {
            return;
        }
        var medsData = [];
        $('.medRow').each(function() {
            var row = $(this);
            var med = {
                medicine: row.find('.medicineSelect').val(),
                dosage: row.find('input[placeholder="e.g. 500mg"]').val(),
                quantity: row.find('input.qtyInput').val(),
                frequency: row.find('input[placeholder="e.g. 3x a day"]').val(),
                instructions: row.find('input[placeholder="e.g. After meals"]').val()
            };
            medsData.push(med);
        });
        var notes = $('#prescribeMedForm textarea').val();
        var patientId = $('#profileModal').find('#modalPatientName').text().match(/\(([^)]+)\)$/);
        var patientName = $('#profileModal').find('#modalPatientName').text().replace(/\s*\([^)]*\)$/, '');
        $.ajax({
            url: 'submit_prescription.php',
            type: 'POST',
            data: {
                patient_id: patientId ? patientId[1] : '',
                patient_name: patientName,
                medicines: JSON.stringify(medsData),
                notes: notes
            },
            success: function(response) {
                // Show success message in the form
                $('#prescribeMedForm').prepend('<div class="success-msg bg-green-100 text-green-700 px-3 py-2 rounded text-center text-sm mb-4 animate-fade-in">Prescription added successfully!</div>');
                setTimeout(function() {
                    $('#prescribeMedModal').addClass('hidden');
                    $('#profileModal').addClass('hidden');
                    window.location.href = 'records.php';
                }, 1200);
            },
            error: function(xhr, status, error) {
                console.error(error);
                alert('An error occurred while submitting the prescription. Please try again.');
            }
        });
    });
});
</script>

<style>
@keyframes fade-in {
  from { opacity: 0; transform: translateY(-4px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
  animation: fade-in 0.3s ease;
}
.prescribe-modal-scroll {
  max-height: 80vh;
  overflow-y: scroll;
  border-radius: 0.75rem; /* Match modal's rounded-lg */
  /* Always reserve space for scrollbar, so content never shifts */
  scrollbar-gutter: stable both-edges;
}
.prescribe-modal-scroll:hover {
  /* No change needed, always scrollable */
}
.prescribe-modal-scroll::-webkit-scrollbar {
  width: 10px;
  border-radius: 0.75rem;
  background: transparent;
}
.prescribe-modal-scroll::-webkit-scrollbar-thumb {
  border-radius: 0.75rem;
  background: #c1c1c1; /* Use a neutral default, but let browser override */
  border: 2px solid transparent;
  background-clip: padding-box;
}
.prescribe-modal-scroll::-webkit-scrollbar-thumb:hover {
  background: #a0a0a0;
}
/* For Firefox */
.prescribe-modal-scroll {
  scrollbar-width: auto;
  scrollbar-color: auto;
}
</style>