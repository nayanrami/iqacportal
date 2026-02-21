/**
 * IQAC Cell ADIT - App JavaScript (Light Theme)
 */

// Sidebar toggle for mobile
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) sidebar.classList.toggle('open');
}

document.addEventListener('click', function (e) {
    const sidebar = document.getElementById('sidebar');
    if (sidebar && sidebar.classList.contains('open') && !sidebar.contains(e.target) && !e.target.closest('[onclick*="sidebar"]')) {
        sidebar.classList.remove('open');
    }
});

// Flash message auto-dismiss
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        }, 4000);
    });

    // Animate elements on scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, i) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, i * 80);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.stat-card, .animate-on-scroll').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
        observer.observe(card);
    });

    // Count-up animation for stat values
    document.querySelectorAll('.stat-value[data-count]').forEach(el => {
        const target = parseFloat(el.getAttribute('data-count'));
        const isDecimal = target % 1 !== 0;
        let current = 0;
        const increment = target / 40;
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) { current = target; clearInterval(timer); }
            el.textContent = isDecimal ? current.toFixed(2) : Math.round(current);
        }, 30);
    });
});

// Question Builder (Light Theme)
let questionCounter = 0;

function addQuestion() {
    questionCounter++;
    const container = document.getElementById('questions-container');
    if (!container) return;

    const num = container.children.length + 1;
    const div = document.createElement('div');
    div.className = 'question-item-anim bg-gray-50 border border-gray-200 rounded-xl p-5 flex gap-3 items-start';
    div.innerHTML = `
        <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0 mt-1 q-number shadow">${num}</div>
        <div class="flex-1 space-y-2">
            <input type="text" name="questions[]" placeholder="Enter your question..." required
                   class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-gray-800 placeholder-gray-400 outline-none focus:border-indigo-500 transition text-sm">
            <input type="number" name="max_scores[]" value="5" min="1" max="10" required
                   class="w-32 px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-gray-800 outline-none focus:border-indigo-500 transition text-sm" placeholder="Max Score">
        </div>
        <button type="button" onclick="removeQuestion(this)" class="text-gray-300 hover:text-red-500 transition p-2 mt-1"><i class="fas fa-trash-alt"></i></button>
    `;
    container.appendChild(div);
    div.querySelector('input[type="text"]').focus();
}

function removeQuestion(btn) {
    const item = btn.closest('.question-item-anim') || btn.closest('[class*="question-item"]');
    if (!item) return;
    item.style.opacity = '0';
    item.style.transform = 'translateX(-20px)';
    item.style.transition = 'all 0.3s ease';
    setTimeout(() => {
        item.remove();
        renumberQuestions();
    }, 300);
}

function renumberQuestions() {
    document.querySelectorAll('#questions-container > div').forEach((item, index) => {
        const num = item.querySelector('.q-number');
        if (num) num.textContent = index + 1;
    });
}

// Modal controls
function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) { modal.classList.add('active'); document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) { modal.classList.remove('active'); document.body.style.overflow = ''; }
}

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(m => {
            m.classList.remove('active'); document.body.style.overflow = '';
        });
    }
});

// Delete confirmation
function confirmDelete(url, name) {
    if (confirm('Are you sure you want to delete "' + name + '"? This action cannot be undone.')) {
        window.location.href = url;
    }
}

// Form validation
function validateFeedbackForm(form) {
    const questions = form.querySelectorAll('.rating-group');
    let isValid = true;
    questions.forEach(group => {
        const checked = group.querySelector('input[type="radio"]:checked');
        if (!checked) {
            group.style.borderColor = '#ef4444';
            group.style.boxShadow = '0 0 0 2px rgba(239, 68, 68, 0.1)';
            isValid = false;
        } else {
            group.style.borderColor = '';
            group.style.boxShadow = '';
        }
    });
    if (!isValid) alert('Please rate all questions before submitting.');
    return isValid;
}
