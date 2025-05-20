<?php
include '../includep/header.php';
$student_id = $_SESSION['student_row_id'];
// Fetch prescription history for this patient only
$prescriptionHistory = [];
try {
    $db = new PDO('mysql:host=localhost;dbname=clinic_management_system;charset=utf8', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $db->prepare('SELECT prescription_date, medicines FROM prescriptions WHERE patient_id = ? ORDER BY prescription_date DESC');
    $stmt->execute([$student_id]);
    $prescriptionHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $prescriptionHistory = [];
}
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
    <!-- Visit Log Table: Medication History -->
    <div class="bg-white rounded shadow p-6 mb-8">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Medication History</h3>
            <button class="px-4 py-2 bg-primary text-white rounded hover:bg-primary/90 flex items-center"><i class="ri-download-2-line mr-1"></i> Download as PDF</button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Date</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Medicine</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($prescriptionHistory)) {
                        foreach ($prescriptionHistory as $presc) {
                            $date = htmlspecialchars($presc['prescription_date']);
                            $meds = json_decode($presc['medicines'], true);
                            if (is_array($meds)) {
                                foreach ($meds as $med) {
                                    $medName = htmlspecialchars($med['medicine'] ?? '');
                                    $qty = htmlspecialchars($med['quantity'] ?? '');
                                    echo "<tr>";
                                    echo "<td class='px-4 py-2'>" . $date . "</td>";
                                    echo "<td class='px-4 py-2'>" . $medName . "</td>";
                                    echo "<td class='px-4 py-2'>" . $qty . "</td>";
                                    echo "</tr>";
                                }
                            }
                        }
                    } else {
                        echo "<tr><td colspan='3' class='px-4 py-2 text-center text-gray-500'>No medication history found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<?php
include '../includep/footer.php';
?>