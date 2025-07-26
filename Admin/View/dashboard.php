<!DOCTYPE html>
<html>

<head>
    <title>Team CMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="p-6 bg-gray-100">
    <h1 class="text-2xl font-bold mb-4">Team Management</h1>
    <button onclick="openModal()" class="bg-blue-600 text-white px-4 py-2 rounded mb-4">Add Team Member</button>

    <!-- Modal -->
    <div id="modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-6 rounded w-full max-w-md">
            <h2 class="text-xl font-bold mb-4" id="modalTitle">Add Team Member</h2>
            <form id="teamForm">
                <input type="hidden" name="id" id="memberId">
                <div class="mb-2">
                    <label>Name</label>
                    <input name="name" id="name" class="w-full border px-2 py-1" required>
                </div>
                <div class="mb-2">
                    <label>Position</label>
                    <input name="text" id="text" class="w-full border px-2 py-1" required>
                </div>
                <div class="mb-2">
                    <label>Image</label>
                    <input type="file" name="image" id="image">
                </div>
                <div class="mb-2">
                    <label>Facebook</label>
                    <input name="facebook" id="facebook" class="w-full border px-2 py-1" placeholder="Facebook URL">
                </div>
                <div class="mb-2">
                    <label>Twitter</label>
                    <input name="twitter" id="twitter" class="w-full border px-2 py-1" placeholder="Twitter URL">
                </div>
                <div class="mb-2">
                    <label>Instagram</label>
                    <input name="instagram" id="instagram" class="w-full border px-2 py-1" placeholder="Instagram URL">
                </div>
                <div class="mb-2">
                    <label>WhatsApp</label>
                    <input name="whatsapp" id="whatsapp" class="w-full border px-2 py-1" placeholder="WhatsApp URL">
                </div>
                <div class="mb-2">
                    <label>Phone</label>
                    <input name="phone" id="phone" class="w-full border px-2 py-1" placeholder="Phone Number">
                </div>
                <div class="flex justify-end space-x-2 mt-4">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Save</button>
                </div>
            </form>
        </div>
    </div>


    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
        <!-- Font Awesome CDN (only include once) -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
        <?php foreach ($team as $i => $member): ?>
            <div class="bg-white shadow-lg rounded-xl p-6 flex flex-col items-center transition-transform hover:scale-105 hover:shadow-2xl relative">
                <div class="absolute top-2 right-2 flex gap-2">
                </div>
                <div class="w-24 h-24 mb-4 rounded-full overflow-hidden border-4 border-blue-500 shadow">
                    <img src="/AtoZe/<?= htmlspecialchars($member['image']) ?>" alt="<?= $member['name'] ?>" class="w-full h-full object-cover">
                </div>
                <h2 class="text-xl font-bold text-gray-800 mb-1"><?= htmlspecialchars($member['name']) ?></h2>
                <p class="text-gray-500 text-center mb-3"><?= htmlspecialchars($member['text']) ?></p>
                <div class="flex gap-3 mt-2">
                    <?php
                    $icons = [
                        'facebook'  => ['fab fa-facebook fa-lg', 'text-blue-600 hover:text-blue-800', '', ''],
                        'twitter'   => ['fab fa-twitter fa-lg', 'text-blue-400 hover:text-blue-600', '', ''],
                        'instagram' => ['fab fa-instagram fa-lg', 'text-pink-500 hover:text-pink-700', '', ''],
                        'whatsapp'  => ['fab fa-whatsapp fa-lg', 'text-green-500 hover:text-green-700', '', ''],
                        'phone'     => ['fa fa-phone fa-lg', 'text-gray-600 hover:text-gray-800', 'tel:', ''],
                    ];
                    foreach ($member['socials'] as $key => $value) {
                        if (!empty($value) && isset($icons[$key])) {
                            $icon = $icons[$key][0];
                            $class = $icons[$key][1];
                            $prefix = $icons[$key][2];
                            $suffix = $icons[$key][3];
                            $href = $key === 'phone' ? $prefix . htmlspecialchars($value) : htmlspecialchars($value);
                            $target = $key === 'phone' ? '' : 'target="_blank"';
                            echo "<a href='$href' class='$class' $target><i class='$icon'></i></a>";
                        }
                    }
                    ?>
                </div>
                <div class="flex justify-end mt-2 space-x-2">
                    <button onclick='editMember(<?= json_encode($member, JSON_UNESCAPED_UNICODE) ?>)' class="text-blue-600 text-sm">Edit</button>
                    <button onclick='deleteMember(<?= json_encode($member['name'], JSON_UNESCAPED_UNICODE) ?>)' class="text-red-600 text-sm">Delete</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Edit Modal (hidden by default) -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-lg p-8 w-full max-w-lg relative">
            <button onclick="closeEditModal()" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700"><i class="fa fa-times fa-lg"></i></button>
            <form id="editForm" method="POST" action="?action=edit">
                <input type="hidden" name="id" id="editId">
                <h2 class="text-lg font-semibold mb-4">Edit Member</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="text" name="name" id="editName" placeholder="Name" class="border rounded p-2" required>
                    <input type="text" name="image" id="editImage" placeholder="Image Path" class="border rounded p-2" required>
                    <input type="text" name="text" id="editText" placeholder="Role/Title" class="border rounded p-2" required>
                    <input type="text" name="facebook" id="editFacebook" placeholder="Facebook URL" class="border rounded p-2">
                    <input type="text" name="twitter" id="editTwitter" placeholder="Twitter URL" class="border rounded p-2">
                    <input type="text" name="instagram" id="editInstagram" placeholder="Instagram URL" class="border rounded p-2">
                    <input type="text" name="whatsapp" id="editWhatsapp" placeholder="WhatsApp URL" class="border rounded p-2">
                    <input type="text" name="phone" id="editPhone" placeholder="Phone" class="border rounded p-2">
                </div>
                <button type="submit" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('modal').classList.remove('hidden');
            document.getElementById('teamForm').reset();
            document.getElementById('memberId').value = '';
            document.getElementById('modalTitle').innerText = 'Add Team Member';
        }

        function closeModal() {
            document.getElementById('modal').classList.add('hidden');
        }

        function editMember(member) {
            openModal();
            document.getElementById('memberId').value = member.name;
            document.getElementById('name').value = member.name;
            document.getElementById('text').value = member.text;
        }

        function deleteMember(name) {
            if (!confirm("Are you sure you want to delete this member?")) return;
            fetch('Controller/CMSController.php?action=delete&type=team', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    name
                })
            }).then(res => res.json()).then(data => {
                alert(data.message);
                location.reload();
            });
        }

        document.getElementById('teamForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('type', 'team');

            // Ensure social fields are sent as '#' if empty
            ['facebook', 'twitter', 'instagram', 'whatsapp', 'phone'].forEach(field => {
                if (!formData.get(field) || formData.get(field).trim() === '') {
                    formData.set(field, '#');
                }
            });

            fetch('./Controller/CMSController.php?action=save', {
                method: 'POST',
                body: formData
            }).then(res => res.json()).then(data => {
                alert(data.message);
                location.reload();
            });
        });
    </script>

</body>

</html>