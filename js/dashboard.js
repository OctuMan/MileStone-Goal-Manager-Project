/* ============================================================
   NAFSSI DASHBOARD — dashboard.js
   Cleaned up, ID-based selectors, staggered card animation
   ============================================================ */

// ── Element refs ──────────────────────────────────────────────
const addGoalBtn    = document.getElementById('addGoalBtn');
const addGoalForm   = document.getElementById('addGoalForm');
const closeFormBtn  = document.getElementById('closeGoalForm');
const cancelBtn     = document.getElementById('cancelGoalBtn');
const saveBtn       = document.querySelector('.save-btn');
const goalTitleInput = document.getElementById('goal-title');
const goalCatSelect  = document.getElementById('category-select');
const goalSearchInput = document.getElementById('goal-search');
const goalCategoryFilter = document.getElementById('goal-category-filter');
const goalStatusFilter = document.getElementById('goal-status-filter');
const goalTaskFilter = document.getElementById('goal-task-filter');
const resetGoalFiltersBtn = document.getElementById('resetGoalFilters');
let allGoals = [];

// ── Form visibility ───────────────────────────────────────────
function showForm() {
  makeVisible(addGoalForm);
  makeInvisible(addGoalBtn);
  goalTitleInput.focus();
}

function hideForm() {
  makeInvisible(addGoalForm);
  makeVisible(addGoalBtn);
  clearValidation(goalTitleInput);
}

addGoalBtn.addEventListener('click', showForm);
closeFormBtn.addEventListener('click', hideForm);
cancelBtn.addEventListener('click', hideForm);

// ── Visibility helpers ────────────────────────────────────────
function makeVisible(el) {
  el.classList.replace('d-none', 'd-block') || el.classList.add('d-block');
}

function makeInvisible(el) {
  el.classList.replace('d-block', 'd-none') || el.classList.add('d-none');
}

// ── Validation helpers ────────────────────────────────────────
function setValidInput(input) {
  input.classList.add('is-valid');
  input.classList.remove('is-invalid');
  const fb = input.parentNode.querySelector('.invalid-feedback');
  if (fb) fb.textContent = '';
}

function setInvalidInput(input, msg) {
  input.classList.add('is-invalid');
  input.classList.remove('is-valid');
  const fb = input.parentNode.querySelector('.invalid-feedback');
  if (fb) fb.textContent = msg;
}

function clearValidation(input) {
  input.classList.remove('is-valid', 'is-invalid');
  const fb = input.parentNode.querySelector('.invalid-feedback');
  if (fb) fb.textContent = '';
  input.value = '';
}

// ── Save goal ─────────────────────────────────────────────────
saveBtn.addEventListener('click', async (e) => {
  e.preventDefault();
  const goalTitle = goalTitleInput.value.trim();
  const goalCat   = goalCatSelect.value;

  if (!goalTitle) {
    setInvalidInput(goalTitleInput, 'Goal title cannot be empty.');
    return;
  }
  setValidInput(goalTitleInput);

  // Disable button while saving
  saveBtn.disabled = true;
  saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving…';

  const response = await serverResponse({
    goal_name:     goalTitle,
    goal_status:   'pending',
    goal_category: goalCat,
    action:        'insert',
  });

  saveBtn.disabled = false;
  saveBtn.innerHTML = '<i class="bi bi-check2"></i> Save Goal';

  if (response?.status === 'inserted') {
    clearValidation(goalTitleInput);
    hideForm();
    loadUserGoals();
  } else {
    // Show a gentle error without full page disruption
    setInvalidInput(goalTitleInput, 'Something went wrong. Please try again.');
  }
});
// ── Progress bar ──────────────────────────────────────
function renderProgress(goals) {
    const progressBarContainer = document.getElementById('goals-progress');
    if (!progressBarContainer) return;

    const total = goals.length;
    const completed = goals.filter(g => g.goal_status === 'completed').length;
    const percentage = total > 0 ? Math.round((completed / total) * 100) : 0;

    // We use .replace() to inject numbers into the translated strings
    const descMsg = __('goals.progress_msg').replace('%percentage%', percentage);
    const countMsg = __('goals.done_count').replace('%completed%', completed).replace('%total%', total);

    progressBarContainer.innerHTML = `
        <div class="progress-desc mb-2 d-flex justify-content-between">
            <span>${descMsg}</span>
            <span class="text-muted small">${countMsg}</span>
        </div>
        <div class="progress" style="height: 10px;">
            <div class="progress-bar bg-success progress-bar-animated" 
                 style="width: ${percentage}%" aria-valuenow="${percentage}">
            </div>
        </div>`;
}




// ── Server communication ──────────────────────────────────────
async function serverResponse(data) {
  try {
    const res  = await fetch('goalProcess.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(data),
    });
    const text = await res.text();
    try {
      return JSON.parse(text);
    } catch {
      console.error('Server returned non-JSON:', text);
      return { status: 'error' };
    }
  } catch (err) {
    console.error('Fetch error:', err);
    return { status: 'error' };
  }
}

// ── Load & render goals ───────────────────────────────────────
async function loadUserGoals() {
    const container = document.getElementById('goals-container');

    // 1. Translated Loading State
    container.innerHTML = `
        <div class="text-center text-muted py-4" style="grid-column:1/-1;">
          <div class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></div>
          <span style="font-size:var(--text-sm);">${__('goals.loading')}</span>
        </div>`;

    const response = await serverResponse({ action: 'fetch_goals' });

    if (response?.status === 'success') {
        allGoals = response.goals || [];
        renderFilteredGoals();
    } else {
        // 5. Translated Error State
        container.innerHTML = `
            <div class="alert alert-danger" style="grid-column:1/-1;">
              <i class="bi bi-exclamation-circle me-2"></i>
              ${__('goals.error_load')}
            </div>`;
    }
}

function getGoalFilters() {
    return {
        search: (goalSearchInput?.value || '').trim().toLowerCase(),
        category: goalCategoryFilter?.value || 'all',
        status: goalStatusFilter?.value || 'all',
        taskState: goalTaskFilter?.value || 'all',
    };
}

function goalMatchesFilters(goal, filters) {
    const goalName = String(goal.goal_name || '').toLowerCase();
    const goalCategory = ['health', 'work', 'personal', 'religion', 'other'].includes(String(goal.goal_category).toLowerCase())
        ? String(goal.goal_category).toLowerCase()
        : 'other';
    const goalStatus = ['pending', 'completed', 'active'].includes(goal.goal_status) ? goal.goal_status : 'pending';
    const tasksTotal = Number(goal.tasks_total || 0);
    const tasksCompleted = Number(goal.tasks_completed || 0);

    if (filters.search && !goalName.includes(filters.search)) return false;
    if (filters.category !== 'all' && goalCategory !== filters.category) return false;
    if (filters.status !== 'all' && goalStatus !== filters.status) return false;

    if (filters.taskState === 'no-tasks' && tasksTotal !== 0) return false;
    if (filters.taskState === 'in-progress' && !(tasksTotal > 0 && tasksCompleted < tasksTotal)) return false;
    if (filters.taskState === 'tasks-done' && !(tasksTotal > 0 && tasksCompleted === tasksTotal)) return false;

    return true;
}

function renderFilteredGoals() {
    const container = document.getElementById('goals-container');
    const filters = getGoalFilters();
    const goals = allGoals.filter((goal) => goalMatchesFilters(goal, filters));
    renderProgress(goals);
    container.innerHTML = '';

    if (allGoals.length === 0) {
        container.innerHTML = `
            <div class="alert alert-info" style="grid-column:1/-1;">
              <i class="bi bi-flag me-2"></i>
              ${__('goals.empty_state')}
            </div>`;
        return;
    }

    if (goals.length === 0) {
        container.innerHTML = `
            <div class="alert alert-info" style="grid-column:1/-1;">
              <i class="bi bi-funnel me-2"></i>
              ${__('goals.no_filtered_goals')}
            </div>`;
        return;
    }

    const lang = document.documentElement.lang || 'en';
    goals.forEach((goal, index) => {
            const goalStatus = ['pending', 'completed', 'active'].includes(goal.goal_status) ? goal.goal_status : 'pending';
            const goalCategory = ['health', 'work', 'personal', 'religion', 'other'].includes(String(goal.goal_category).toLowerCase())
                ? String(goal.goal_category).toLowerCase()
                : 'other';
            const tasksTotal = Number(goal.tasks_total || 0);
            const tasksCompleted = Number(goal.tasks_completed || 0);
            const taskPercentage = tasksTotal > 0 ? Math.round((tasksCompleted / tasksTotal) * 100) : 0;
            const taskProgressLabel = tasksTotal > 0
                ? __('goals.task_progress')
                    .replace('%completed%', tasksCompleted)
                    .replace('%total%', tasksTotal)
                : __('goals.no_assigned_tasks');
            // 3. Dynamic Date Formatting (Automatically handles Arabic/English names)
            const dateOptions = { month: 'short', day: 'numeric', year: 'numeric' };
            const dateStr = new Date(goal.created_at).toLocaleDateString(lang, dateOptions);
            
            let dateDoneStr = "";
            if (goalStatus === 'completed' && goal.done_at) {
                const doneDate = new Date(goal.done_at).toLocaleDateString(lang, dateOptions);
                dateDoneStr = `<span class="date-done text-success">
                    <i class="bi bi-check2-all"></i> ${doneDate}</span>`;
            }

            // 4. Logic vs. Display
            // We use the raw DB value for the CSS class, but the translated value for the text.
            const statusClass = {
                pending: 'status-pending',
                completed: 'status-done',
                active: 'status-active',
            }[goalStatus] || '';
const translatedStatus = __('goals.status_' + goalStatus);
const translatedCategory = __('category.' + goalCategory);
            const isCompleted = goalStatus === 'completed';
            const checkIcon = isCompleted ? 'bi-check-circle-fill text-success' : 'bi-circle';

            const card = document.createElement('div');
            card.className = 'goal-card';
            card.setAttribute('data-id', goal.goal_id);
            card.style.animationDelay = `${index * 60}ms`;

            card.innerHTML = `
                <div class="mark-as-done">
                  <i class="done-btn ${checkIcon}" role="button"></i>
                </div>
                <div class="title ${isCompleted ? 'text-decoration-line-through text-muted' : ''}">
                    ${escapeHTML(goal.goal_name)}
                </div>
                <div class="meta-data">
                  <span class="date-created">
                    <i class="bi bi-calendar3"></i> ${dateStr}
                  </span>
                  ${dateDoneStr}
                  <span class="category category-${goalCategory}">
                    <i class="bi bi-tag"></i>
                    ${translatedCategory}
                  </span>
                  <span class="status">
        <span class="badge ${statusClass}" data-status="${goalStatus}">
            ${translatedStatus}
        </span>
    </span>
                </div>

                <div class="goal-task-progress" aria-label="${escapeHTML(taskProgressLabel)}">
                  <div class="goal-task-progress-head">
                    <span><i class="bi bi-list-check"></i> ${escapeHTML(taskProgressLabel)}</span>
                    <span>${taskPercentage}%</span>
                  </div>
                  <div class="goal-task-progress-track">
                    <div class="goal-task-progress-bar" style="width: ${taskPercentage}%"></div>
                  </div>
                </div>


                            
    <div class="options" 
         role="button" 
         data-bs-toggle="dropdown" 
        
         aria-expanded="false" 
         title="${__('goals.options_tooltip')}">
        <i class="bi bi-three-dots-vertical"></i>
    </div>

    <ul class="dropdown-menu options-menu">
        <li><a class="dropdown-item" href="tasks.php?goal_id=${encodeURIComponent(goal.goal_id)}">${__('options.assign_task')}</a></li>
        <li><a class="dropdown-item" href="#">${__('options.archive')}</a></li>
        <li><a class="dropdown-item" href="#">${__('options.edit')}</a></li>
        <li><a class="dropdown-item" href="#" id="delete-goal">${__('options.delete')}</a></li>
    </ul>
</div>
                        

 
                
                `;

            container.appendChild(card);
        });
}

// ── XSS prevention ────────────────────────────────────────────
function escapeHTML(str = '') {
  return str.replace(/[&<>'"]/g, (c) =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[c])
  );
}

// ── Init ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded',()=> {
  loadUserGoals();


});

[goalSearchInput, goalCategoryFilter, goalStatusFilter, goalTaskFilter].forEach((control) => {
  if (!control) return;
  control.addEventListener('input', renderFilteredGoals);
  control.addEventListener('change', renderFilteredGoals);
});

if (resetGoalFiltersBtn) {
  resetGoalFiltersBtn.addEventListener('click', () => {
    if (goalSearchInput) goalSearchInput.value = '';
    if (goalCategoryFilter) goalCategoryFilter.value = 'all';
    if (goalStatusFilter) goalStatusFilter.value = 'all';
    if (goalTaskFilter) goalTaskFilter.value = 'all';
    renderFilteredGoals();
  });
}

const container = document.getElementById('goals-container');

container.addEventListener('click', async (e) => {
    const markAsDoneBtn = e.target.closest('.done-btn');
    if (!markAsDoneBtn) return;

    const goalCard = markAsDoneBtn.closest('.goal-card');
    const goalId = goalCard.getAttribute('data-id');
    
    // Find the current status text inside the badge
    const statusBadge = goalCard.querySelector('.badge');
    const currentStatus = statusBadge.getAttribute('data-status');

    const response = await serverResponse({
        goal_id: goalId,
        current_status: currentStatus,
        action: 'toggle-status'
    });

    if (response.status === "success") {
    
        loadUserGoals();
        
        
    }
});

container.addEventListener('click', async(e)=>{
  const deleteBtn = e.target.closest('#delete-goal');
  if(!deleteBtn) return;
  const goalId = deleteBtn.closest('.goal-card').getAttribute('data-id');
  
  const response = await serverResponse({
        goal_id: goalId,
        action: 'delete-goal'
    });

    if (response.status === "success") {
    
        loadUserGoals();
        
        
    }
})

