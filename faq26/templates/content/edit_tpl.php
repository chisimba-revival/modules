<?php if (empty($GLOBALS['kewl_entry_point_run'])) { die('Direct access denied'); } ?>
<div class="max-w-2xl mx-auto p-4 bg-white rounded-md border border-slate-200">
    <h2 class="text-xl font-bold mb-4">Add FAQ Question</h2>
    <form action="<?php echo $saveUrl; ?>" method="POST" class="space-y-4">
        <input type="hidden" name="scope_type" value="<?php echo htmlentities($scope_type); ?>" />
        <input type="hidden" name="scope_id" value="<?php echo htmlentities($scope_id); ?>" />

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Question</label>
            <input type="text" name="question" required class="w-full border border-slate-300 rounded p-2 text-sm" />
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Answer</label>
            <textarea name="answer" rows="5" required class="w-full border border-slate-300 rounded p-2 text-sm"></textarea>
        </div>
        <div class="flex justify-end gap-2">
            <a href="<?php echo $cancelUrl; ?>" class="px-4 py-2 text-sm border rounded">Cancel</a>
            <button type="submit" class="px-4 py-2 text-sm bg-blue-600 text-white rounded">Save FAQ</button>
        </div>
    </form>
</div>
