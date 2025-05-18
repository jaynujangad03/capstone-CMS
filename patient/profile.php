<?php
include '../includep/header.php';
?>

<main class="flex-1 overflow-y-auto bg-gray-50 p-6 ml-16 md:ml-64 mt-[56px]">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">My Profile</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Personal Info -->
        <div class="bg-white rounded shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Personal Information</h3>
            <div class="mb-2"><span class="font-medium">Student ID:</span> 202312345</div>
            <div class="mb-2"><span class="font-medium">Name:</span> Emily Johnson</div>
            <div class="mb-2"><span class="font-medium">Course:</span> BSCS</div>
            <div class="mb-2"><span class="font-medium">Email:</span> emily.johnson@email.com</div>
            <div class="mb-2"><span class="font-medium">Contact:</span> 0917-123-4567</div>
            <button class="mt-4 px-4 py-2 bg-primary text-white rounded hover:bg-primary/90 flex items-center"><i class="ri-download-2-line mr-1"></i> Download Profile (PDF)</button>
        </div>
        <!-- Edit Form -->
        <div class="bg-white rounded shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Edit Profile</h3>
            <form>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" value="emily.johnson@email.com" required />
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                    <input type="text" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" value="0917-123-4567" required />
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Change Password</label>
                    <input type="password" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="New password" />
                </div>
                <button type="submit" class="w-full bg-primary text-white py-2 rounded hover:bg-primary/90">Save Changes</button>
            </form>
        </div>
    </div>

    
</main>
<script>
document.querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault();
    alert('Profile updated!');
});
</script>

<?php
include '../includep/footer.php';
?>