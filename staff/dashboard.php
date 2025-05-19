<?php
include '../includes/header.php';
// Database connection
$db = new PDO('mysql:host=localhost;dbname=clinic_management_system;charset=utf8', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fetch total patients today
$patientsToday = 0;
try {
    $today = date('Y-m-d');
    $stmt = $db->prepare('SELECT COUNT(*) FROM imported_patients WHERE DATE(last_visit) = ?');
    $stmt->execute([$today]);
    $patientsToday = $stmt->fetchColumn();
} catch (Exception $e) {}

// Fetch appointments today and tomorrow
$appointmentsToday = 0;
$appointmentsTomorrow = 0;
try {
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $stmt = $db->prepare('SELECT appointment_date FROM appointments WHERE DATE(appointment_date) IN (?, ?)');
    $stmt->execute([$today, $tomorrow]);
    $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($dates as $date) {
        if (strpos($date, $today) === 0) $appointmentsToday++;
        if (strpos($date, $tomorrow) === 0) $appointmentsTomorrow++;
    }
} catch (Exception $e) {}

// Fetch total visits this week
$totalVisitsWeek = 0;
try {
    $startOfWeek = date('Y-m-d', strtotime('monday this week'));
    $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
    $stmt = $db->prepare('SELECT COUNT(*) FROM imported_patients WHERE last_visit BETWEEN ? AND ?');
    $stmt->execute([$startOfWeek, $endOfWeek]);
    $totalVisitsWeek = $stmt->fetchColumn();
} catch (Exception $e) {}

// Fetch meds issued this week (sum of all medicines dispensed in prescriptions this week)
$medsIssuedWeek = 0;
try {
    $startOfWeek = date('Y-m-d', strtotime('monday this week'));
    $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
    $stmt = $db->prepare('SELECT medicines FROM prescriptions WHERE prescription_date BETWEEN ? AND ?');
    $stmt->execute([$startOfWeek, $endOfWeek]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $medList = json_decode($row['medicines'], true);
        if (is_array($medList)) {
            $medsIssuedWeek += count($medList);
        }
    }
} catch (Exception $e) {}

// Fetch low stock medicines
$lowStockMeds = [];
try {
    $stmt = $db->query('SELECT name FROM medicines WHERE quantity <= 20');
    $lowStockMeds = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {}

// Fetch frequent illnesses (top 5 diagnoses in prescriptions this week)
$illnessLabels = [];
$illnessCounts = [];
try {
    $startOfWeek = date('Y-m-d', strtotime('monday this week'));
    $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
    $stmt = $db->prepare('SELECT diagnosis, COUNT(*) as cnt FROM prescriptions WHERE prescription_date BETWEEN ? AND ? GROUP BY diagnosis ORDER BY cnt DESC LIMIT 5');
    $stmt->execute([$startOfWeek, $endOfWeek]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $illnessLabels[] = $row['diagnosis'];
        $illnessCounts[] = (int)$row['cnt'];
    }
} catch (Exception $e) {}
?>

<main class="flex-1 overflow-y-auto bg-gray-50 p-6 ml-16 md:ml-64 mt-[56px]">
    <div class="max-w-6xl mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Dashboard</h2>
        <!-- Quick Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded shadow p-6 flex flex-col items-center justify-center">
                <div
                    class="w-12 h-12 flex items-center justify-center bg-primary bg-opacity-10 rounded-full text-primary mb-2">
                    <i class="ri-user-heart-line ri-xl"></i>
                </div>
                <span class="text-3xl font-bold text-gray-800 mb-1"><?php echo $patientsToday; ?></span>
                <span class="text-sm font-medium text-gray-500 mb-2">Total Patients Today</span>
            </div>
            <div class="bg-white rounded shadow p-6 flex flex-col items-center justify-center">
                <div class="w-12 h-12 flex items-center justify-center bg-blue-100 rounded-full text-blue-600 mb-2">
                    <i class="ri-calendar-check-line ri-xl"></i>
                </div>
                <span class="text-3xl font-bold text-gray-800 mb-1"><?php echo $appointmentsToday . ' / ' . $appointmentsTomorrow; ?></span>
                <span class="text-sm font-medium text-gray-500 mb-2">Appointments Today / Tomorrow</span>
            </div>
            <div class="bg-white rounded shadow p-6 flex flex-col items-center justify-center">
                <div class="w-12 h-12 flex items-center justify-center bg-green-100 rounded-full text-green-600 mb-2">
                    <i class="ri-bar-chart-2-line ri-xl"></i>
                </div>
                <span class="text-3xl font-bold text-gray-800 mb-1"><?php echo $totalVisitsWeek; ?></span>
                <span class="text-sm font-medium text-gray-500 mb-2">Total Visits This Week</span>
            </div>
        </div>
        <!-- Meds Issued Quick Stat -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded shadow p-6 flex flex-col items-center justify-center">
                <div class="w-12 h-12 flex items-center justify-center bg-orange-100 rounded-full text-orange-600 mb-2">
                    <i class="ri-capsule-line ri-xl"></i>
                </div>
                <span class="text-3xl font-bold text-gray-800 mb-1"><?php echo $medsIssuedWeek; ?></span>
                <span class="text-sm font-medium text-gray-500 mb-2">Meds Issued This Week</span>
            </div>
            <!-- Low Stock Alert Box -->
            <div class="bg-red-50 border-l-4 border-red-400 p-6 rounded flex items-center">
                <div class="w-10 h-10 flex items-center justify-center bg-red-100 rounded-full text-red-600 mr-4">
                    <i class="ri-alert-line ri-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-red-800">Medicine Stock Alert</p>
                    <p class="text-xs text-red-600">
                        <?php
                        if (!empty($lowStockMeds)) {
                            // Fetch medicine quantities for low stock
                            $lowStockDetails = [];
                            $stmt = $db->query('SELECT name, quantity FROM medicines WHERE quantity <= 20');
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $lowStockDetails[] = $row;
                            }
                            $emptyMeds = array_filter($lowStockDetails, function($med) { return $med['quantity'] == 0; });
                            $lowMeds = array_filter($lowStockDetails, function($med) { return $med['quantity'] > 0; });
                            if (!empty($emptyMeds)) {
                                $names = array_map(function($med) { return $med['name']; }, $emptyMeds);
                                echo implode(', ', $names) . ' ' . (count($names) === 1 ? 'is' : 'are') . ' empty.';
                            }
                            if (!empty($lowMeds)) {
                                if (!empty($emptyMeds)) echo ' '; // space between messages
                                $names = array_map(function($med) { return $med['name']; }, $lowMeds);
                                echo implode(', ', $names) . ' ' . (count($names) === 1 ? 'is' : 'are') . ' running low.';
                            }
                        } else {
                            echo 'No medicines are running low.';
                        }
                        ?>
                    </p>
                </div>
            </div>
        </div>
        <!-- Bar Chart: Frequent Illnesses -->
        <div class="bg-white rounded shadow p-6 mb-8">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-800">Frequent Illnesses</h3>
            </div>
            <div id="illnessBarChart" class="w-full h-[300px]"></div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Bar Chart: Frequent Illnesses (live data)
        const illnessBarChart = echarts.init(document.getElementById('illnessBarChart'));
        const illnessBarOption = {
            tooltip: { trigger: 'axis' },
            grid: { left: '3%', right: '4%', bottom: '3%', top: '3%', containLabel: true },
            xAxis: {
                type: 'category',
                data: <?= json_encode($illnessLabels) ?>,
                axisLine: { lineStyle: { color: '#e5e7eb' } },
                axisLabel: { color: '#6b7280' }
            },
            yAxis: {
                type: 'value',
                axisLine: { show: false },
                axisLabel: { color: '#6b7280' },
                splitLine: { lineStyle: { color: '#f3f4f6' } }
            },
            series: [{
                name: 'Cases',
                type: 'bar',
                barWidth: '40%',
                itemStyle: { color: 'rgba(87, 181, 231, 1)', borderRadius: [6, 6, 0, 0] },
                data: <?= json_encode($illnessCounts) ?>
            }]
        };
        illnessBarChart.setOption(illnessBarOption);
        window.addEventListener('resize', function () {
            illnessBarChart.resize();
        });
    });
</script>

<?php
include '../includes/footer.php';
?>