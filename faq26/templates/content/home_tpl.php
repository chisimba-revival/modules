<?php if (empty($GLOBALS['kewl_entry_point_run'])) { die('Direct access denied'); } ?>

<div class="max-w-5xl mx-auto p-4 bg-white rounded-lg shadow-sm border border-slate-200">
    <div class="flex justify-between items-center mb-6 border-b pb-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">FAQ Management</h2>
            <p class="text-xs text-slate-500">Active Scope: <span class="font-mono bg-slate-100 px-1 py-0.5 rounded text-slate-700"><?php echo htmlentities($scope_type . ' : ' . $scope_id); ?></span></p>
        </div>
        <a href="<?php echo $addUrl; ?>" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow-sm transition">+ Add New Question</a>
    </div>

    <?php if (empty($faqs)): ?>
        <div class="text-center py-8 text-slate-500 text-sm italic">
            No FAQ entries found for this scope. Click "+ Add New Question" to create one.
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold text-xs uppercase tracking-wider">
                        <th class="py-3 px-4">Order</th>
                        <th class="py-3 px-4">Question</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($faqs as $faq): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3 px-4 font-mono text-xs text-slate-500"><?php echo (int)$faq['display_order']; ?></td>
                            <td class="py-3 px-4 font-medium text-slate-800"><?php echo htmlentities($faq['question']); ?></td>
                            <td class="py-3 px-4">
                                <?php if ($faq['is_published']): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Published</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600">Draft</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4 text-right space-x-2">
                                <form action="<?php echo $deleteUrl; ?>" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this FAQ?');">
                                    <input type="hidden" name="id" value="<?php echo htmlentities($faq['id']); ?>" />
                                    <input type="hidden" name="scope_type" value="<?php echo htmlentities($scope_type); ?>" />
                                    <input type="hidden" name="scope_id" value="<?php echo htmlentities($scope_id); ?>" />
                                    <button type="submit" class="text-xs text-red-600 hover:text-red-800 font-medium">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
