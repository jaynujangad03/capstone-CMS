<?php
include '../includes/header.php';
// Database connection
$db = new PDO('mysql:host=localhost;dbname=clinic_management_system;charset=utf8', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Handle report generation (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'], $_POST['from'], $_POST['to'])) {
    $type = $_POST['type'];
    $from = $_POST['from'];
    $to = $_POST['to'];
    if ($type === 'visits') {
        // Patient Visits report
        $stmt = $db->prepare('SELECT prescription_date, patient_name, prescribed_by FROM prescriptions WHERE DATE(prescription_date) BETWEEN ? AND ? ORDER BY prescription_date ASC');
        $stmt->execute([$from, $to]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $rows]);
        exit;
    } elseif ($type === 'meds') {
        // Medicine Usage report
        $stmt = $db->prepare('SELECT prescription_date, patient_name, medicines FROM prescriptions WHERE DATE(prescription_date) BETWEEN ? AND ? ORDER BY prescription_date ASC');
        $stmt->execute([$from, $to]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $medUsage = [];
        foreach ($rows as $row) {
            $meds = json_decode($row['medicines'], true);
            if (is_array($meds)) {
                foreach ($meds as $med) {
                    $name = $med['medicine'] ?? '';
                    $qty = (int)($med['quantity'] ?? 0);
                    if ($name) {
                        if (!isset($medUsage[$name])) $medUsage[$name] = 0;
                        $medUsage[$name] += $qty;
                    }
                }
            }
        }
        arsort($medUsage);
        echo json_encode(['success' => true, 'data' => $medUsage]);
        exit;
    }
}
// Top 5 Medicines Used (for chart)
$topMedsLabels = [];
$topMedsData = [];
$stmt = $db->query('SELECT medicines FROM prescriptions WHERE YEAR(prescription_date) = YEAR(CURDATE())');
$medCount = [];
foreach ($stmt as $row) {
    $meds = json_decode($row['medicines'], true);
    if (is_array($meds)) {
        foreach ($meds as $med) {
            $name = $med['medicine'] ?? '';
            $qty = (int)($med['quantity'] ?? 0);
            if ($name) {
                if (!isset($medCount[$name])) $medCount[$name] = 0;
                $medCount[$name] += $qty;
            }
        }
    }
}
arsort($medCount);
$topMedsLabels = array_slice(array_keys($medCount), 0, 5);
$topMedsData = array_slice(array_values($medCount), 0, 5);
// Visits per Staff (for chart)
$staffLabels = [];
$staffData = [];
$stmt = $db->query('SELECT prescribed_by, COUNT(*) as cnt FROM prescriptions WHERE YEAR(prescription_date) = YEAR(CURDATE()) GROUP BY prescribed_by ORDER BY cnt DESC');
foreach ($stmt as $row) {
    $staffLabels[] = $row['prescribed_by'];
    $staffData[] = (int)$row['cnt'];
}
?>
<!-- Dashboard Content -->
<main class="flex-1 overflow-y-auto bg-gray-50 p-6 ml-16 md:ml-64 mt-[56px]">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Reports</h2>
    <!-- Generate Report -->
    <div class="bg-white rounded shadow p-6 mb-8">
        <h3 class="text-lg font-semibold mb-4">Generate Report</h3>
        <form id="reportForm" class="flex flex-wrap gap-4 items-end mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select name="type" class="border border-gray-300 rounded px-3 py-2 text-sm">
                    <option value="visits">Patient Visits</option>
                    <option value="meds">Medicine Usage</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From</label>
                <input name="from" type="date" class="border border-gray-300 rounded px-3 py-2 text-sm" value="<?= date('Y-m-01') ?>" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
                <input name="to" type="date" class="border border-gray-300 rounded px-3 py-2 text-sm" value="<?= date('Y-m-d') ?>" />
            </div>
            <button type="submit" class="px-4 py-2 bg-primary text-white rounded hover:bg-primary/90">Generate</button>
        </form>
        <div id="reportResult" class="mb-4"></div>
        <div class="flex gap-2 mb-2">
            <button class="px-3 py-2 bg-green-500 text-white rounded hover:bg-green-600"><i
                    class="ri-file-excel-2-line mr-1"></i>Export Excel</button>
            <button class="px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"><i
                    class="ri-file-pdf-line mr-1"></i>Export PDF</button>
        </div>
    </div>
    <!-- Graphs -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded shadow p-6">
            <h4 class="font-semibold mb-2">Top 5 Medicines Used</h4>
            <canvas id="topMedsChart" height="180"></canvas>
        </div>
        <div class="bg-white rounded shadow p-6">
            <h4 class="font-semibold mb-2">Visits per Staff</h4>
            <canvas id="visitsStaffChart" height="180"></canvas>
        </div>
    </div>
    <!-- Past Reports Archive -->
    <div class="bg-white rounded shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Past Reports Archive</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Date</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Type</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Generated By</th>
                        <th class="px-4 py-2 text-center font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="px-4 py-2">2025-05-15</td>
                        <td class="px-4 py-2">Patient Visits</td>
                        <td class="px-4 py-2">Staff A</td>
                        <td class="px-4 py-2 text-center">
                            <button
                                class="px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600">View</button>
                            <button
                                class="px-2 py-1 text-xs bg-green-500 text-white rounded hover:bg-green-600">Download</button>
                        </td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2">2025-05-10</td>
                        <td class="px-4 py-2">Medicine Usage</td>
                        <td class="px-4 py-2">Staff B</td>
                        <td class="px-4 py-2 text-center">
                            <button
                                class="px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600">View</button>
                            <button
                                class="px-2 py-1 text-xs bg-green-500 text-white rounded hover:bg-green-600">Download</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Top 5 Medicines Used Chart
const topMedsChart = document.getElementById('topMedsChart').getContext('2d');
new Chart(topMedsChart, {
    type: 'bar',
    data: {
        labels: <?= json_encode($topMedsLabels) ?>,
        datasets: [{
            label: 'Usage',
            data: <?= json_encode($topMedsData) ?>,
            backgroundColor: '#3b82f6',
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});
// Visits per Staff Chart
const visitsStaffChart = document.getElementById('visitsStaffChart').getContext('2d');
new Chart(visitsStaffChart, {
    type: 'bar',
    data: {
        labels: <?= json_encode($staffLabels) ?>,
        datasets: [{
            label: 'Visits',
            data: <?= json_encode($staffData) ?>,
            backgroundColor: '#10b981',
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});
// Generate Report AJAX
const reportForm = document.getElementById('reportForm');
const reportResult = document.getElementById('reportResult');
reportForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(reportForm);
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            if(formData.get('type') === 'visits') {
                let html = `<div class='overflow-x-auto'><table class='min-w-full divide-y divide-gray-200 text-sm'><thead><tr><th class='px-4 py-2'>Date</th><th class='px-4 py-2'>Patient</th><th class='px-4 py-2'>Staff</th></tr></thead><tbody>`;
                data.data.forEach(row => {
                    html += `<tr><td class='px-4 py-2'>${row.prescription_date}</td><td class='px-4 py-2'>${row.patient_name}</td><td class='px-4 py-2'>${row.prescribed_by}</td></tr>`;
                });
                html += '</tbody></table></div>';
                reportResult.innerHTML = html;
            } else if(formData.get('type') === 'meds') {
                let html = `<div class='overflow-x-auto'><table class='min-w-full divide-y divide-gray-200 text-sm'><thead><tr><th class='px-4 py-2'>Medicine</th><th class='px-4 py-2'>Total Used</th></tr></thead><tbody>`;
                for(const [med, qty] of Object.entries(data.data)) {
                    html += `<tr><td class='px-4 py-2'>${med}</td><td class='px-4 py-2'>${qty}</td></tr>`;
                }
                html += '</tbody></table></div>';
                reportResult.innerHTML = html;
            }
        } else {
            reportResult.innerHTML = `<div class='text-red-600'>Error: ${data.message}</div>`;
        }
    })
    .catch(() => reportResult.innerHTML = `<div class='text-red-600'>Error generating report.</div>`);
});
</script>
<?php
include '../includes/footer.php';
?>