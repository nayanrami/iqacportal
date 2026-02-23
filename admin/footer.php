</main>

<!-- Background Task Monitor -->
<div id="task-monitor" class="fixed bottom-6 right-6 z-[60] flex flex-col gap-3 pointer-events-none"></div>

<script>
function updateTaskMonitor() {
    fetch('<?= APP_URL ?>/admin/api/tasks.php?action=poll')
        .then(r => r.json())
        .then(tasks => {
            const container = document.getElementById('task-monitor');
            if (!container) return;
            
            // Get current IDs in container to avoid flickering if nothing changed
            const existingIds = Array.from(container.children).map(c => c.dataset.id);
            const newIds = tasks.map(t => t.id.toString());
            
            // If tasks are same and status is not 'running', maybe skip update? 
            // Better to just refresh for now as it's simple.
            
            container.innerHTML = '';
            tasks.forEach(task => {
                const card = document.createElement('div');
                card.dataset.id = task.id;
                card.className = "w-72 glass-card p-4 shadow-xl border-t-4 pointer-events-auto " + 
                    (task.status === 'running' ? 'border-indigo-500' : 
                     task.status === 'completed' ? 'border-emerald-500' : 
                     task.status === 'failed' ? 'border-red-500' : 'border-gray-300');
                
                const icon = task.task_type === 'sync' ? 'fa-sync' : 'fa-magic';
                const statusText = task.status === 'running' ? `<i class="fas fa-spinner fa-spin mr-2"></i>Running (${task.progress}%)` : 
                                 task.status === 'completed' ? '<i class="fas fa-check-circle mr-2 text-emerald-500"></i>Completed' : 
                                 task.status === 'failed' ? '<i class="fas fa-exclamation-triangle mr-2 text-red-500"></i>Failed' : 'Pending...';

                card.innerHTML = `
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-black uppercase text-gray-400 tracking-widest">
                            <i class="fas ${icon} mr-1"></i> ${task.task_type.toUpperCase()} Task
                        </span>
                        <span class="text-[10px] font-bold ${task.status === 'running' ? 'text-indigo-500' : 'text-gray-500'}">${statusText}</span>
                    </div>
                    <div class="text-xs font-bold text-gray-700 truncate mb-2">${task.message || 'Processing...'}</div>
                    <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r ${task.status === 'failed' ? 'from-red-400 to-red-500' : 'from-indigo-400 to-indigo-500'} transition-all duration-500" style="width: ${task.progress}%"></div>
                    </div>
                    ${(task.status === 'completed' || task.status === 'failed') ? 
                      `<button onclick="this.parentElement.remove()" class="mt-2 text-[10px] font-bold text-gray-400 hover:text-gray-600">Dismiss</button>` : ''}
                `;
                container.appendChild(card);
            });
        });
}

function startBackgroundTask(type, confirmMsg = null) {
    if (confirmMsg && !confirm(confirmMsg)) return;
    
    fetch('<?= APP_URL ?>/admin/api/tasks.php?action=start&type=' + type)
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                updateTaskMonitor();
            } else {
                alert('Error starting task: ' + (res.error || 'Unknown error'));
            }
        });
}

// Start polling
setInterval(updateTaskMonitor, 4000);
updateTaskMonitor();
</script>

</body>
</html>
