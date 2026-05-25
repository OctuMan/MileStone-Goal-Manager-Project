const taskForm = document.getElementById('taskForm');
const taskTitleInput = document.getElementById('task-title');
const taskGoalSelect = document.getElementById('task-goal');
const taskDueInput = document.getElementById('task-due');
const taskNotesInput = document.getElementById('task-notes');
const saveTaskBtn = document.getElementById('saveTaskBtn');
const taskList = document.getElementById('task-list');
const taskStats = document.getElementById('task-stats');
const filterButtons = document.querySelectorAll('.task-filter');

let allTasks = [];
let currentFilter = 'all';

function setInvalidInput(input, msg) {
  input.classList.add('is-invalid');
  input.classList.remove('is-valid');
  const fb = input.parentNode.querySelector('.invalid-feedback');
  if (fb) fb.textContent = msg || '';
}

function clearInvalidInput(input) {
  input.classList.remove('is-invalid');
  const fb = input.parentNode.querySelector('.invalid-feedback');
  if (fb) fb.textContent = '';
}

async function taskRequest(data) {
  try {
    const response = await fetch('taskProcess.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data),
    });
    const text = await response.text();

    try {
      return JSON.parse(text);
    } catch {
      console.error('Server returned non-JSON:', text);
      return { status: 'error' };
    }
  } catch (error) {
    console.error(error);
    return { status: 'error' };
  }
}

function escapeHTML(str = '') {
  return String(str).replace(/[&<>'"]/g, (c) =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[c])
  );
}

function formatDate(dateValue) {
  if (!dateValue) return '';
  const lang = document.documentElement.lang || 'en';
  return new Date(`${dateValue}T00:00:00`).toLocaleDateString(lang, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
}

function getVisibleTasks() {
  if (currentFilter === 'standalone') {
    return allTasks.filter((task) => !task.goal_id);
  }
  if (currentFilter === 'linked') {
    return allTasks.filter((task) => task.goal_id);
  }
  return allTasks;
}

function renderStats() {
  const total = allTasks.length;
  const completed = allTasks.filter((task) => task.task_status === 'completed').length;
  const linked = allTasks.filter((task) => task.goal_id).length;
  const standalone = total - linked;

  taskStats.innerHTML = `
    <span><strong>${completed}</strong> ${__('tasks.stat_done')}</span>
    <span><strong>${standalone}</strong> ${__('tasks.stat_standalone')}</span>
    <span><strong>${linked}</strong> ${__('tasks.stat_linked')}</span>
  `;
}

function renderTasks() {
  renderStats();
  const tasks = getVisibleTasks();
  taskList.innerHTML = '';

  if (tasks.length === 0) {
    taskList.innerHTML = `
      <div class="alert alert-info">
        <i class="bi bi-list-check"></i>
        ${__('tasks.empty_state')}
      </div>
    `;
    return;
  }

  tasks.forEach((task, index) => {
    const status = ['pending', 'completed'].includes(task.task_status) ? task.task_status : 'pending';
    const isCompleted = status === 'completed';
    const goalName = task.goal_name ? escapeHTML(task.goal_name) : '';
    const dueDate = formatDate(task.due_date);
    const doneDate = task.done_at ? formatDate(task.done_at.slice(0, 10)) : '';
    const card = document.createElement('article');

    card.className = `task-card ${isCompleted ? 'task-card-completed' : ''}`;
    card.dataset.id = task.task_id;
    card.style.animationDelay = `${index * 45}ms`;
    card.innerHTML = `
      <button type="button" class="task-check" data-status="${status}" title="${__('tasks.toggle_status')}">
        <i class="bi ${isCompleted ? 'bi-check-circle-fill' : 'bi-circle'}"></i>
      </button>
      <div class="task-card-body">
        <div class="task-title">${escapeHTML(task.task_title)}</div>
        ${task.task_notes ? `<p class="task-notes-preview">${escapeHTML(task.task_notes)}</p>` : ''}
        <div class="task-meta">
          ${goalName ? `<span class="task-goal-pill"><i class="bi bi-bullseye"></i>${goalName}</span>` : `<span><i class="bi bi-inbox"></i>${__('tasks.no_goal')}</span>`}
          ${dueDate ? `<span><i class="bi bi-calendar3"></i>${dueDate}</span>` : ''}
          ${doneDate ? `<span class="text-success"><i class="bi bi-check2-all"></i>${doneDate}</span>` : ''}
        </div>
      </div>
      <button type="button" class="task-delete" title="${__('options.delete')}">
        <i class="bi bi-trash3"></i>
      </button>
    `;

    taskList.appendChild(card);
  });
}

async function loadTasks() {
  taskList.innerHTML = `
    <div class="text-center text-muted py-4">
      <div class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></div>
      ${__('tasks.loading')}
    </div>
  `;

  const response = await taskRequest({ action: 'fetch_tasks' });

  if (response?.status === 'success') {
    allTasks = response.tasks || [];
    renderTasks();
    return;
  }

  taskList.innerHTML = `
    <div class="alert alert-danger">
      <i class="bi bi-exclamation-circle"></i>
      ${__('tasks.error_load')}
    </div>
  `;
}

taskForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  const title = taskTitleInput.value.trim();

  if (!title) {
    setInvalidInput(taskTitleInput, __('tasks.title_required'));
    return;
  }

  clearInvalidInput(taskTitleInput);
  saveTaskBtn.disabled = true;
  saveTaskBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';

  const response = await taskRequest({
    action: 'insert',
    task_title: title,
    goal_id: taskGoalSelect.value,
    due_date: taskDueInput.value,
    task_notes: taskNotesInput.value.trim(),
  });

  saveTaskBtn.disabled = false;
  saveTaskBtn.innerHTML = `<i class="bi bi-check2"></i> ${__('tasks.save_task')}`;

  if (response?.status === 'inserted') {
    const selectedGoal = taskGoalSelect.value;
    taskForm.reset();
    taskGoalSelect.value = selectedGoal;
    await loadTasks();
    taskTitleInput.focus();
    return;
  }

  setInvalidInput(taskTitleInput, response?.message || __('tasks.error_save'));
});

taskTitleInput.addEventListener('input', () => clearInvalidInput(taskTitleInput));

taskList.addEventListener('click', async (event) => {
  const checkBtn = event.target.closest('.task-check');
  const deleteBtn = event.target.closest('.task-delete');

  if (checkBtn) {
    const card = checkBtn.closest('.task-card');
    const response = await taskRequest({
      action: 'toggle-status',
      task_id: card.dataset.id,
      current_status: checkBtn.dataset.status,
    });

    if (response?.status === 'success') {
      await loadTasks();
    }
  }

  if (deleteBtn) {
    const card = deleteBtn.closest('.task-card');
    const response = await taskRequest({
      action: 'delete-task',
      task_id: card.dataset.id,
    });

    if (response?.status === 'success') {
      await loadTasks();
    }
  }
});

filterButtons.forEach((button) => {
  button.addEventListener('click', () => {
    filterButtons.forEach((btn) => btn.classList.remove('active'));
    button.classList.add('active');
    currentFilter = button.dataset.filter;
    renderTasks();
  });
});

document.addEventListener('DOMContentLoaded', loadTasks);
