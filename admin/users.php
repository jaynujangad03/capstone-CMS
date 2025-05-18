<?php
session_start();
include '../includea/header.php';

// Database connection (MySQL)
try {
    $db = new PDO('mysql:host=localhost;dbname=clinic_management_system;charset=utf8mb4', 'root', ''); // Change username/password if needed
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Create table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        username VARCHAR(255) NOT NULL UNIQUE,
        email VARCHAR(255) NOT NULL UNIQUE,
        role VARCHAR(50) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'Active',
        password VARCHAR(255) NOT NULL
    )");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
// Add user if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $status = 'Active';
    try {
        $stmt = $db->prepare('INSERT INTO users (name, username, email, role, status) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$name, $username, $email, $role, $status]);
        $_SESSION['user_message'] = ['type' => 'success', 'text' => 'User added successfully!'];
    } catch (PDOException $e) {
        $_SESSION['user_message'] = ['type' => 'error', 'text' => 'Failed to add user: ' . $e->getMessage()];
    }
    // header('Location: users.php');
    // exit;
}
// Handle update user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $id = $_POST['edit_id'];
    $name = $_POST['edit_name'];
    $username = $_POST['edit_username'];
    $email = $_POST['edit_email'];
    $role = $_POST['edit_role'];
    $status = $_POST['edit_status'];
    try {
        $stmt = $db->prepare('UPDATE users SET name=?, username=?, email=?, role=?, status=? WHERE id=?');
        $stmt->execute([$name, $username, $email, $role, $status, $id]);
        $_SESSION['user_message'] = ['type' => 'success', 'text' => 'User updated successfully!'];
    } catch (PDOException $e) {
        $_SESSION['user_message'] = ['type' => 'error', 'text' => 'Failed to update user: ' . $e->getMessage()];
    }
    // header('Location: users.php');
    // exit;
}
// Fetch users
$users = $db->query('SELECT * FROM users')->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="flex-1 overflow-y-auto bg-gray-50 p-6 ml-16 md:ml-64 mt-[56px]">
    <div class="max-w-5xl mx-auto">
        <!-- Success/Error Message -->
        <?php if (isset($_SESSION['user_message'])): ?>
            <div class="mb-4 p-3 rounded <?php echo $_SESSION['user_message']['type'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                <?= htmlspecialchars($_SESSION['user_message']['text']) ?>
            </div>
            <?php unset($_SESSION['user_message']); ?>
        <?php endif; ?>
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Manage Users</h2>
            <button id="addUserBtn"
                class="px-4 py-2 bg-primary text-white font-medium text-sm rounded-button hover:bg-primary/90 flex items-center">
                <i class="ri-user-add-line ri-lg mr-1"></i> Add New User
            </button>
        </div>
        <!-- Role Filter Dropdown & Search Bar -->
        <div class="mb-4 flex items-center gap-4 flex-wrap">
            <div class="flex items-center">
                <label for="roleFilter" class="mr-2 text-sm font-medium text-gray-700">Filter by Role:</label>
                <select id="roleFilter"
                    class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="all">All</option>
                    <option value="admin">Administrator</option>
                    <option value="doctor/nurse">Doctor/Nurse</option>
                </select>
            </div>
            <div class="flex items-center flex-1 max-w-xs">
                <input type="text" id="userSearch" placeholder="Search by name or email..."
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
            </div>
        </div>
        <!-- User List Table -->
        <div class="bg-white rounded shadow p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm" id="userTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600">Name</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600">Username</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600">Email</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600">Role</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600">Status</th>
                            <th class="px-4 py-2 text-center font-semibold text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
<tr 
    data-id="<?= $user['id'] ?>"
    data-name="<?= htmlspecialchars($user['name']) ?>"
    data-username="<?= htmlspecialchars($user['username']) ?>"
    data-email="<?= htmlspecialchars($user['email']) ?>"
    data-role="<?= htmlspecialchars($user['role']) ?>"
    data-status="<?= htmlspecialchars($user['status']) ?>"
>
    <td class="px-4 py-2"><?= htmlspecialchars($user['name']) ?></td>
    <td class="px-4 py-2"><?= htmlspecialchars($user['username']) ?></td>
    <td class="px-4 py-2"><?= htmlspecialchars($user['email']) ?></td>
    <td class="px-4 py-2"><?= htmlspecialchars($user['role']) ?></td>
    <td class="px-4 py-2"><span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full"><?= htmlspecialchars($user['status']) ?></span></td>
    <td class="px-4 py-2 text-center space-x-2">
        <button 
            class="editBtn px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200"
            data-id="<?= $user['id'] ?>"
            data-name="<?= htmlspecialchars($user['name']) ?>"
            data-username="<?= htmlspecialchars($user['username']) ?>"
            data-email="<?= htmlspecialchars($user['email']) ?>"
            data-role="<?= htmlspecialchars($user['role']) ?>"
            data-status="<?= htmlspecialchars($user['status']) ?>"
        >Edit</button>
        <button class="disableBtn px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200">Disable</button>
    </td>
</tr>
<?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Add New User Modal -->
    <div id="addUserModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
            <button id="closeModalBtn" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700">
                <i class="ri-close-line ri-2x"></i>
            </button>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Add New User</h3>
            <form id="addUserForm" class="space-y-4" method="post" autocomplete="off">
                <input type="hidden" name="add_user" value="1">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input type="text" name="name"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="username"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        required />
                </div>
                <div class="relative">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="email" name="email" required class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="user@email.com">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select name="role"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        required>
                        <option value="">Select Role</option>
                        <option value="admin">Administrator</option>
                        <option value="doctor/nurse">Doctor/Nurse</option>
                    </select>
                </div>
                <!-- Add password and confirm password fields to Add User form -->
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" id="add_password"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary pr-10"
                        required />
                    <span class="absolute right-3 top-9 cursor-pointer" onclick="togglePassword('add_password', this)">
                        <i class="ri-eye-off-line" id="add_password_icon"></i>
                    </span>
                </div>
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <input type="password" name="confirm_password" id="add_confirm_password"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary pr-10"
                        required />
                    <span class="absolute right-3 top-9 cursor-pointer" onclick="togglePassword('add_confirm_password', this)">
                        <i class="ri-eye-off-line" id="add_confirm_password_icon"></i>
                    </span>
                </div>
                <div class="flex justify-end">
                    <button type="submit"
                        class="px-4 py-2 bg-primary text-white font-medium text-sm rounded-button hover:bg-primary/90">Add
                        User</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Edit User Modal -->
    <div id="editUserModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
            <button id="closeEditModalBtn" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700">
                <i class="ri-close-line ri-2x"></i>
            </button>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Edit User</h3>
            <form id="editUserForm" class="space-y-4" method="post" autocomplete="off">
                <input type="hidden" name="edit_user" value="1">
                <input type="hidden" name="edit_id" id="edit_id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input type="text" name="edit_name" id="edit_name"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="edit_username" id="edit_username"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        required />
                </div>
                <div class="relative">
                    <label for="edit_email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="edit_email" name="edit_email" required class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="user@email.com">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select name="edit_role" id="edit_role"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        required>
                        <option value="">Select Role</option>
                        <option value="admin">Administrator</option>
                        <option value="doctor/nurse">Doctor/Nurse</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="edit_status" id="edit_status"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        required>
                        <option value="Active">Active</option>
                        <option value="Disabled">Disabled</option>
                    </select>
                </div>
                <!-- Add password and confirm password fields to Edit User form (optional) -->
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password (leave blank to keep unchanged)</label>
                    <input type="password" name="edit_password" id="edit_password"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary pr-10"
                    />
                    <span class="absolute right-3 top-9 cursor-pointer" onclick="togglePassword('edit_password', this)">
                        <i class="ri-eye-off-line" id="edit_password_icon"></i>
                    </span>
                </div>
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <input type="password" name="edit_confirm_password" id="edit_confirm_password"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary pr-10"
                    />
                    <span class="absolute right-3 top-9 cursor-pointer" onclick="togglePassword('edit_confirm_password', this)">
                        <i class="ri-eye-off-line" id="edit_confirm_password_icon"></i>
                    </span>
                </div>
                <div class="flex justify-end">
                    <button type="submit"
                        class="px-4 py-2 bg-primary text-white font-medium text-sm rounded-button hover:bg-primary/90">Update
                        User</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    // Modal logic
    const addUserBtn = document.getElementById('addUserBtn');
    const addUserModal = document.getElementById('addUserModal');
    const closeModalBtn = document.getElementById('closeModalBtn');
    addUserBtn.addEventListener('click', () => addUserModal.classList.remove('hidden'));
    closeModalBtn.addEventListener('click', () => addUserModal.classList.add('hidden'));
    window.addEventListener('click', (e) => {
        if (e.target === addUserModal) addUserModal.classList.add('hidden');
    });
    // Edit User Modal logic
    const editUserModal = document.getElementById('editUserModal');
    const closeEditModalBtn = document.getElementById('closeEditModalBtn');
    const editUserForm = document.getElementById('editUserForm');

    document.querySelectorAll('.editBtn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('edit_id').value = this.getAttribute('data-id');
            document.getElementById('edit_name').value = this.getAttribute('data-name');
            document.getElementById('edit_username').value = this.getAttribute('data-username');
            document.getElementById('edit_email').value = this.getAttribute('data-email');
            document.getElementById('edit_role').value = this.getAttribute('data-role');
            document.getElementById('edit_status').value = this.getAttribute('data-status');
            editUserModal.classList.remove('hidden');
        });
    });
    closeEditModalBtn.addEventListener('click', () => editUserModal.classList.add('hidden'));
    window.addEventListener('click', (e) => {
        if (e.target === editUserModal) editUserModal.classList.add('hidden');
    });
    // Filter logic for role dropdown
    document.getElementById('roleFilter').addEventListener('change', filterUsers);
    // Search bar logic
    document.getElementById('userSearch').addEventListener('input', filterUsers);

    function filterUsers() {
        const selectedRole = document.getElementById('roleFilter').value;
        const search = document.getElementById('userSearch').value.trim().toLowerCase();
        const rows = document.querySelectorAll('#userTable tbody tr');
        rows.forEach(row => {
            const userRole = row.getAttribute('data-role');
            const name = row.getAttribute('data-name').toLowerCase();
            const username = row.getAttribute('data-username').toLowerCase();
            const matchesRole = (selectedRole === 'all' || userRole === selectedRole);
            const matchesSearch = (!search || name.includes(search) || username.includes(search));
            if (matchesRole && matchesSearch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    // Action buttons (demo only)
    document.querySelector('#userTable tbody').addEventListener('click', async function(e) {
        if (e.target.classList.contains('editBtn')) {
            document.getElementById('edit_id').value = e.target.getAttribute('data-id');
            document.getElementById('edit_name').value = e.target.getAttribute('data-name');
            document.getElementById('edit_username').value = e.target.getAttribute('data-username');
            document.getElementById('edit_email').value = e.target.getAttribute('data-email');
            document.getElementById('edit_role').value = e.target.getAttribute('data-role');
            document.getElementById('edit_status').value = e.target.getAttribute('data-status');
            editUserModal.classList.remove('hidden');
        }
        if (e.target.classList.contains('disableBtn')) {
            const tr = e.target.closest('tr');
            const userId = tr.getAttribute('data-id');
            // Send AJAX to disable user
            const formData = new FormData();
            formData.append('disable_user', '1');
            formData.append('user_id', userId);
            const res = await fetch('user_actions.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                tr.setAttribute('data-status', 'Disabled');
                tr.children[4].innerHTML = '<span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">Disabled</span>';
                showUserMessage('success', 'User disabled successfully!');
            } else {
                showUserMessage('error', data.message || 'Failed to disable user.');
            }
        }
    });

    // Password show/hide toggle function
    function togglePassword(inputId, iconSpan) {
        const input = document.getElementById(inputId);
        const icon = iconSpan.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('ri-eye-off-line');
            icon.classList.add('ri-eye-line');
        } else {
            input.type = 'password';
            icon.classList.remove('ri-eye-line');
            icon.classList.add('ri-eye-off-line');
        }
    }

    // Add User AJAX
    document.getElementById('addUserForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const form = e.target;
        let valid = true;
        // Remove previous error messages
        form.querySelectorAll('.form-error').forEach(el => el.remove());
        // Name validation
        if (!form.name.value.trim()) {
            showFieldError(form.name, 'Name is required');
            valid = false;
        }
        // Username validation
        if (!form.username.value.trim()) {
            showFieldError(form.username, 'Username is required');
            valid = false;
        }
        // Email validation
        if (!form.email.value.trim()) {
            showFieldError(form.email, 'Email is required');
            valid = false;
        } else if (!validateEmail(form.email.value.trim())) {
            showFieldError(form.email, 'Invalid email format');
            valid = false;
        }
        // Role validation
        if (!form.role.value) {
            showFieldError(form.role, 'Role is required');
            valid = false;
        }
        // Password validation
        if (!form.password.value) {
            showFieldError(form.password, 'Password is required');
            valid = false;
        }
        if (!form.confirm_password.value) {
            showFieldError(form.confirm_password, 'Confirm password is required');
            valid = false;
        }
        if (form.password.value && form.confirm_password.value && form.password.value !== form.confirm_password.value) {
            showFieldError(form.confirm_password, 'Passwords do not match!');
            valid = false;
        }
        if (!valid) return;
        const formData = new FormData(form);
        const res = await fetch('user_actions.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            // Add new row to table
            const tbody = document.querySelector('#userTable tbody');
            const tr = document.createElement('tr');
            tr.setAttribute('data-id', data.user.id);
            tr.setAttribute('data-name', data.user.name);
            tr.setAttribute('data-username', data.user.username);
            tr.setAttribute('data-email', data.user.email);
            tr.setAttribute('data-role', data.user.role);
            tr.setAttribute('data-status', data.user.status);
            tr.innerHTML = `
                <td class="px-4 py-2">${data.user.name}</td>
                <td class="px-4 py-2">${data.user.username}</td>
                <td class="px-4 py-2">${data.user.email}</td>
                <td class="px-4 py-2">${data.user.role}</td>
                <td class="px-4 py-2"><span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">${data.user.status}</span></td>
                <td class="px-4 py-2 text-center space-x-2">
                    <button class="editBtn px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200"
                        data-id="${data.user.id}"
                        data-name="${data.user.name}"
                        data-username="${data.user.username}"
                        data-email="${data.user.email}"
                        data-role="${data.user.role}"
                        data-status="${data.user.status}"
                    >Edit</button>
                    <button class="disableBtn px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200">Disable</button>
                </td>
            `;
            tbody.appendChild(tr);
            addUserModal.classList.add('hidden');
            form.reset();
            setTimeout(() => { showUserMessage('success', data.message); }, 200);
        } else {
            alert(data.message);
        }
    });

    // Edit User AJAX
    document.getElementById('editUserForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const form = e.target;
        let valid = true;
        // Remove previous error messages
        form.querySelectorAll('.form-error').forEach(el => el.remove());
        // Name validation
        if (!form.edit_name.value.trim()) {
            showFieldError(form.edit_name, 'Name is required');
            valid = false;
        }
        // Username validation
        if (!form.edit_username.value.trim()) {
            showFieldError(form.edit_username, 'Username is required');
            valid = false;
        }
        // Email validation
        if (!form.edit_email.value.trim()) {
            showFieldError(form.edit_email, 'Email is required');
            valid = false;
        } else if (!validateEmail(form.edit_email.value.trim())) {
            showFieldError(form.edit_email, 'Invalid email format');
            valid = false;
        }
        // Role validation
        if (!form.edit_role.value) {
            showFieldError(form.edit_role, 'Role is required');
            valid = false;
        }
        // Password validation (optional)
        if (form.edit_password.value || form.edit_confirm_password.value) {
            if (!form.edit_password.value) {
                showFieldError(form.edit_password, 'Password is required');
                valid = false;
            }
            if (!form.edit_confirm_password.value) {
                showFieldError(form.edit_confirm_password, 'Confirm password is required');
                valid = false;
            }
            if (form.edit_password.value !== form.edit_confirm_password.value) {
                showFieldError(form.edit_confirm_password, 'Passwords do not match!');
                valid = false;
            }
        }
        if (!valid) return;
        const formData = new FormData(form);
        const res = await fetch('user_actions.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            // Update row in table
            const tr = document.querySelector(`#userTable tr[data-id="${data.user.id}"]`);
            if (tr) {
                tr.setAttribute('data-name', data.user.name);
                tr.setAttribute('data-username', data.user.username);
                tr.setAttribute('data-email', data.user.email);
                tr.setAttribute('data-role', data.user.role);
                tr.setAttribute('data-status', data.user.status);
                tr.children[0].textContent = data.user.name;
                tr.children[1].textContent = data.user.username;
                tr.children[2].textContent = data.user.email;
                tr.children[3].textContent = data.user.role;
                tr.children[4].innerHTML = `<span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">${data.user.status}</span>`;
                // Update button data attributes
                const editBtn = tr.querySelector('.editBtn');
                editBtn.setAttribute('data-name', data.user.name);
                editBtn.setAttribute('data-username', data.user.username);
                editBtn.setAttribute('data-email', data.user.email);
                editBtn.setAttribute('data-role', data.user.role);
                editBtn.setAttribute('data-status', data.user.status);
            }
            editUserModal.classList.add('hidden');
            setTimeout(() => { showUserMessage('success', data.message); }, 200);
        } else {
            alert(data.message);
        }
    });

    // Helper to show error message below a field
    function showFieldError(input, message) {
        const error = document.createElement('div');
        error.className = 'form-error text-xs text-red-600 mt-1';
        error.textContent = message;
        input.parentNode.appendChild(error);
    }

    // Show user message function
    function showUserMessage(type, text) {
        let msgDiv = document.querySelector('#userMessageDiv');
        if (!msgDiv) {
            msgDiv = document.createElement('div');
            msgDiv.id = 'userMessageDiv';
            msgDiv.className = 'mb-4 p-3 rounded fixed top-6 left-1/2 transform -translate-x-1/2 z-50 shadow-lg';
            document.body.appendChild(msgDiv);
        }
        msgDiv.textContent = text;
        if (type === 'success') {
            msgDiv.className = 'mb-4 p-3 rounded fixed top-6 left-1/2 transform -translate-x-1/2 z-50 shadow-lg bg-green-100 text-green-800';
        } else {
            msgDiv.className = 'mb-4 p-3 rounded fixed top-6 left-1/2 transform -translate-x-1/2 z-50 shadow-lg bg-red-100 text-red-800';
        }
        msgDiv.style.minWidth = '250px';
        msgDiv.style.textAlign = 'center';
        msgDiv.style.transition = 'opacity 0.3s';
        msgDiv.style.opacity = '1';
        setTimeout(() => {
            msgDiv.style.opacity = '0';
            setTimeout(() => { msgDiv.remove(); }, 400);
        }, 2000);
    }

    // Email validation function
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(String(email).toLowerCase());
    }
</script>

<?php
include '../includea/footer.php';
?>