<?php
include '../includea/header.php';
?>

<main class="flex-1 overflow-y-auto bg-gray-50 p-6 ml-16 md:ml-64 mt-[56px]">
    <div class="max-w-5xl mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Reports</h2>
        <!-- Report Generator -->
        <div class="bg-white rounded shadow p-6 mb-8">
            <form class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                    <input type="date" class="border border-gray-300 rounded px-3 py-2 text-sm mr-2" value="2025-05-01" />
                    <span class="mx-1 text-gray-400">to</span>
                    <input type="date" class="border border-gray-300 rounded px-3 py-2 text-sm" value="2025-05-15" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Report Type</label>
                    <select class="border border-gray-300 rounded px-3 py-2 text-sm">
                        <option value="all">All</option>
                        <option value="visits">Visits</option>
                        <option value="medications">Medications</option>
                        <option value="appointments">Appointments</option>
                        <option value="inventory">Inventory</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="px-4 py-2 bg-primary text-white font-medium text-sm rounded-button hover:bg-primary/90">Generate</button>
                </div>
            </form>
        </div>
        <!-- Export Buttons -->
        <div class="flex justify-end mb-4 gap-2">
            <button class="px-4 py-2 bg-primary text-white font-medium text-sm rounded-button hover:bg-primary/90 flex items-center"><i class="ri-file-pdf-line mr-1"></i> Export PDF</button>
            <button class="px-4 py-2 bg-green-600 text-white font-medium text-sm rounded-button hover:bg-green-700 flex items-center"><i class="ri-file-excel-2-line mr-1"></i> Export CSV</button>
        </div>
        <!-- Sample Reports Table -->
        <div class="bg-white rounded shadow p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Sample Reports</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600">Date</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600">Type</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600">Summary</th>
                            <th class="px-4 py-2 text-center font-semibold text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch dynamic report data from prescriptions and pending_prescriptions
                        try {
                            $db = new PDO('mysql:host=localhost;dbname=clinic_management_system;charset=utf8', 'root', '');
                            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                            // Calculate total visits (each prescription = 1 visit)
                            $visitCount = $db->query('SELECT COUNT(*) FROM prescriptions')->fetchColumn();
                            // Example: fetch low stock medicine (for Medications row)
                            $lowStockMed = $db->query("SELECT name FROM medicines WHERE quantity < 10 ORDER BY quantity ASC LIMIT 1")->fetchColumn();
                            // Example: fetch pending appointments (for Appointments row)
                            $pendingAppointments = $db->query("SELECT COUNT(*) FROM pending_prescriptions")->fetchColumn();
                            // Fetch soon-to-expire medicine (expiry within 30 days)
                            $soonExpireMed = $db->query("SELECT name, expiry FROM medicines WHERE expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY expiry ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) {
                            $visitCount = 0;
                            $lowStockMed = 'Ibuprofen';
                            $pendingAppointments = 0;
                            $soonExpireMed = null;
                        }
                        ?>
                        <tr>
                            <td class="px-4 py-2"><?= date('Y-m-d') ?></td>
                            <td class="px-4 py-2">Visits</td>
                            <td class="px-4 py-2">Total: <?= $visitCount ?> visits</td>
                            <td class="px-4 py-2 text-center space-x-2">
                                <button class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200">View</button>
                                <button class="px-2 py-1 text-xs bg-primary text-white rounded hover:bg-primary/90">Download</button>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2"><?= date('Y-m-d', strtotime('-1 day')) ?></td>
                            <td class="px-4 py-2">Medications</td>
                            <td class="px-4 py-2">
                                <?php
                                if ($lowStockMed) {
                                    echo htmlspecialchars($lowStockMed) . ' low stock';
                                } else {
                                    echo 'No low stock';
                                }
                                ?>
                            </td>
                            <td class="px-4 py-2 text-center space-x-2">
                                <button class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200">View</button>
                                <button class="px-2 py-1 text-xs bg-primary text-white rounded hover:bg-primary/90">Download</button>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2"><?= date('Y-m-d', strtotime('-1 day')) ?></td>
                            <td class="px-4 py-2">Medications</td>
                            <td class="px-4 py-2">
                                <?php
                                if ($soonExpireMed) {
                                    echo htmlspecialchars($soonExpireMed['name']) . ' expiring soon (' . htmlspecialchars($soonExpireMed['expiry']) . ')';
                                } else {
                                    echo 'No soon-to-expire';
                                }
                                ?>
                            </td>
                            <td class="px-4 py-2 text-center space-x-2">
                                <button class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200">View</button>
                                <button class="px-2 py-1 text-xs bg-primary text-white rounded hover:bg-primary/90">Download</button>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2"><?= date('Y-m-d', strtotime('-2 days')) ?></td>
                            <td class="px-4 py-2">Appointments</td>
                            <td class="px-4 py-2"><?= $pendingAppointments ?> pending appointments</td>
                            <td class="px-4 py-2 text-center space-x-2">
                                <button class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200">View</button>
                                <button class="px-2 py-1 text-xs bg-primary text-white rounded hover:bg-primary/90">Download</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Auto-Scheduling Report Toggle -->
        <div class="bg-white rounded shadow p-6 flex items-center justify-between">
            <div>
                <h4 class="text-md font-semibold text-gray-800 mb-1">Auto-Scheduling Reports</h4>
                <p class="text-xs text-gray-500">Enable to receive scheduled reports automatically via email.</p>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" checked>
                <span class="toggle-slider"></span>
            </label>
        </div>
    </div>
</main>

<?php
include '../includea/footer.php';
?>