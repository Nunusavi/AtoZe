<div class="bg-white shadow rounded p-4">
  <img src="<?= $member['image'] ?>" class="w-24 h-24 rounded-full object-cover mb-3">
  <h2 class="text-lg font-semibold"><?= htmlspecialchars($member['name']) ?></h2>
  <p class="text-sm text-gray-600"><?= htmlspecialchars($member['text']) ?></p>
</div>
