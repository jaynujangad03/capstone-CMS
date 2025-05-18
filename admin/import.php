<?php
include '../includea/header.php';

// Database connection (using MySQL for clinic_management_system)
$db = new PDO('mysql:host=localhost;dbname=clinic_management_system;charset=utf8', 'root', '');
// Create imported_patients table if not exists
$db->exec('CREATE TABLE IF NOT EXISTS imported_patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(255),
    name VARCHAR(255),
    dob VARCHAR(255),
    gender VARCHAR(255),
    address VARCHAR(255),
    civil_status VARCHAR(255),
    password VARCHAR(255),
    year_level VARCHAR(255)
)');

// Handle CSV upload and import
$uploadStatus = '';
$previewRows = [];
$duplicateCount = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csvFile'])) {
    $file = $_FILES['csvFile']['tmp_name'];
    if (($handle = fopen($file, 'r')) !== false) {
        $header = fgetcsv($handle); // Assume first row is header
        $existingIds = [];
        $stmt = $db->query('SELECT id FROM imported_patients');
        foreach ($stmt as $row) {
            $existingIds[] = $row['id'];
        }
        $inserted = 0;
        while (($data = fgetcsv($handle)) !== false) {
            // Map columns: [student_id, name, dob, gender, address, civil_status, password, year_level]
            $student_id = $data[0];
            $name = $data[1];
            $dob = $data[2];
            $gender = $data[3];
            $address = $data[4];
            $civil_status = $data[5];
            $password = $data[6];
            $year_level = $data[7];
            $isDuplicate = false;
            // Check for duplicate student_id
            $stmtCheck = $db->prepare('SELECT COUNT(*) FROM imported_patients WHERE student_id = ?');
            $stmtCheck->execute([$student_id]);
            if ($stmtCheck->fetchColumn() > 0) {
                $isDuplicate = true;
            }
            $previewRows[] = [
                'student_id' => $student_id,
                'name' => $name,
                'dob' => $dob,
                'gender' => $gender,
                'address' => $address,
                'civil_status' => $civil_status,
                'password' => $password,
                'year_level' => $year_level,
                'duplicate' => $isDuplicate
            ];
            if (!$isDuplicate) {
                $stmt2 = $db->prepare('INSERT INTO imported_patients (student_id, name, dob, gender, address, civil_status, password, year_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt2->execute([$student_id, $name, $dob, $gender, $address, $civil_status, $password, $year_level]);
                $inserted++;
            } else {
                $duplicateCount++;
            }
        }
        fclose($handle);
        $uploadStatus = "<span class='text-green-700'>Upload and import successful! $inserted new record(s) added.</span>";
    } else {
        $uploadStatus = "<span class='text-red-700'>Failed to open uploaded file.</span>";
    }
}
?>

<main class="flex-1 overflow-y-auto bg-gray-50 p-6 ml-16 md:ml-64 mt-[56px]">
    <div class="max-w-5xl mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Import CSV</h2>
        <!-- File Upload Form -->
        <div class="bg-white rounded shadow p-6 mb-8">
            <form id="csvUploadForm" enctype="multipart/form-data" method="post">
                <label class="block text-sm font-medium text-gray-700 mb-2">Select CSV File</label>
                <div class="flex items-center space-x-4 mb-4">
                    <input type="file" name="csvFile" id="csvFile" accept=".csv"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-primary/90"
                        required />
                    <button type="submit"
                        class="px-4 py-2 bg-primary text-white font-medium text-sm rounded-button hover:bg-primary/90">Upload</button>
                </div>
            </form>
            <!-- Upload Status Notification -->
            <?php if ($uploadStatus): ?>
                <div id="uploadStatus" class="mt-2 text-sm "><?php echo $uploadStatus; ?></div>
            <?php else: ?>
                <div id="uploadStatus" class="hidden mt-2 text-sm"></div>
            <?php endif; ?>
        </div>
        <!-- Duplicate Detection Summary -->
        <?php if ($duplicateCount > 0): ?>
        <div id="duplicateSummary" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 rounded">
            <p class="text-sm text-yellow-800 font-medium"><?php echo $duplicateCount; ?> duplicate(s) detected in the uploaded file.</p>
        </div>
        <?php endif; ?>
        <!-- Imported Patients Table -->
        <div class="bg-white rounded shadow p-6 mt-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Imported Patients</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm" id="importedPatientsTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600">Student ID</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600">Name</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600">DOB</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600">Gender</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600">Address</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600">Civil Status</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600">Year Level</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $stmt = $db->query('SELECT id, student_id, name, dob, gender, address, civil_status, year_level FROM imported_patients ORDER BY id DESC');
                    foreach ($stmt as $row): ?>
                        <tr>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($row['student_id']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($row['dob']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($row['gender']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($row['address']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($row['civil_status']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($row['year_level']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php
include '../includea/footer.php';
?>