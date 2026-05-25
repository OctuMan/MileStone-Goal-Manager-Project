const achievementForm = document.getElementById('achievementForm');
const achievementTitleInput = document.getElementById('achievement-title');
const achievementCategorySelect = document.getElementById('achievement-category');
const achievementDateInput = document.getElementById('achievement-date');
const achievementNoteInput = document.getElementById('achievement-note');
const saveAchievementBtn = document.getElementById('saveAchievementBtn');
const scoreboard = document.getElementById('achievement-scoreboard');
const achievedTasks = document.getElementById('achieved-tasks');
const achievedGoals = document.getElementById('achieved-goals');
const manualAchievements = document.getElementById('manual-achievements');
const periodButtons = document.querySelectorAll('[data-period]');

let achievementData = { tasks: [], goals: [], manual: [], stats: { total: 0, tasks: 0, goals: 0, manual: 0 } };
let currentPeriod = 'daily';

function escapeHTML(str = '') {
  return String(str).replace(/[&<>'"]/g, (c) =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[c])
  );
}

function localDate(dateValue) {
  if (!dateValue) return null;
  const date = new Date(dateValue.includes('T') ? dateValue : `${dateValue}T00:00:00`);
  return Number.isNaN(date.getTime()) ? null : date;
}

function formatDate(dateValue) {
  const date = localDate(dateValue);
  if (!date) return '';
  return date.toLocaleDateString(document.documentElement.lang || 'en', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
}

function weekKey(date) {
  const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
  d.setUTCDate(d.getUTCDate() + 4 - (d.getUTCDay() || 7));
  const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
  const weekNo = Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
  return `${d.getUTCFullYear()}-${String(weekNo).padStart(2, '0')}`;
}

function groupLabel(dateValue) {
  const date = localDate(dateValue);
  if (!date) return __('achievements.unknown_date');

  if (currentPeriod === 'weekly') {
    return __('achievements.week_label').replace('%week%', weekKey(date));
  }

  return formatDate(dateValue);
}

function groupItems(items, getDate) {
  return items.reduce((groups, item) => {
    const label = groupLabel(getDate(item));
    groups[label] = groups[label] || [];
    groups[label].push(item);
    return groups;
  }, {});
}

async function achievementRequest(data) {
  try {
    const response = await fetch('achievementProcess.php', {
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

function setInvalidInput(input, msg) {
  input.classList.add('is-invalid');
  const fb = input.parentNode.querySelector('.invalid-feedback');
  if (fb) fb.textContent = msg || '';
}

function clearInvalidInput(input) {
  input.classList.remove('is-invalid');
  const fb = input.parentNode.querySelector('.invalid-feedback');
  if (fb) fb.textContent = '';
}

function renderScoreboard() {
  const stats = achievementData.stats || {};
  scoreboard.innerHTML = `
    <div><strong>${stats.total || 0}</strong><span>${__('achievements.total')}</span></div>
    <div><strong>${stats.tasks || 0}</strong><span>${__('achievements.tasks_done')}</span></div>
    <div><strong>${stats.goals || 0}</strong><span>${__('achievements.goals_done')}</span></div>
    <div><strong>${stats.manual || 0}</strong><span>${__('achievements.manual_done')}</span></div>
  `;
}

function emptyState() {
  return `
    <div class="achievement-empty">
      <i class="bi bi-journal-check"></i>
      ${__('achievements.empty_state')}
    </div>
  `;
}

function renderGroupedFeed(container, items, getDate, renderItem) {
  if (!items.length) {
    container.innerHTML = emptyState();
    return;
  }

  const groups = groupItems(items, getDate);
  container.innerHTML = Object.entries(groups).map(([label, groupItemsForLabel]) => `
    <div class="achievement-group">
      <div class="achievement-group-label">${escapeHTML(label)}</div>
      <div class="achievement-group-items">
        ${groupItemsForLabel.map(renderItem).join('')}
      </div>
    </div>
  `).join('');
}

function renderAchievements() {
  renderScoreboard();

  renderGroupedFeed(
    achievedTasks,
    achievementData.tasks || [],
    (task) => task.done_at || task.created_at,
    (task) => `
      <article class="achievement-card achieved-task">
        <div class="achievement-icon"><i class="bi bi-check2"></i></div>
        <div>
          <div class="achievement-title">${escapeHTML(task.task_title)}</div>
          <div class="achievement-meta">
            ${task.goal_name ? `<span><i class="bi bi-bullseye"></i>${escapeHTML(task.goal_name)}</span>` : `<span><i class="bi bi-inbox"></i>${__('tasks.no_goal')}</span>`}
            <span><i class="bi bi-calendar3"></i>${formatDate(task.done_at || task.created_at)}</span>
          </div>
        </div>
      </article>
    `
  );

  renderGroupedFeed(
    achievedGoals,
    achievementData.goals || [],
    (goal) => goal.done_at || goal.created_at,
    (goal) => `
      <article class="achievement-card achieved-goal">
        <div class="achievement-icon"><i class="bi bi-trophy"></i></div>
        <div>
          <div class="achievement-title">${escapeHTML(goal.goal_name)}</div>
          <div class="achievement-meta">
            <span class="category category-${escapeHTML(String(goal.goal_category || 'other').toLowerCase())}">
              ${__('category.' + String(goal.goal_category || 'other').toLowerCase())}
            </span>
            <span><i class="bi bi-calendar3"></i>${formatDate(goal.done_at || goal.created_at)}</span>
          </div>
        </div>
      </article>
    `
  );

  renderGroupedFeed(
    manualAchievements,
    achievementData.manual || [],
    (achievement) => achievement.achieved_at,
    (achievement) => `
      <article class="achievement-card manual-achievement" data-id="${achievement.achievement_id}">
        <div class="achievement-icon"><i class="bi bi-stars"></i></div>
        <div>
          <div class="achievement-title">${escapeHTML(achievement.achievement_title)}</div>
          ${achievement.achievement_note ? `<p class="achievement-note">${escapeHTML(achievement.achievement_note)}</p>` : ''}
          <div class="achievement-meta">
            <span class="category category-${escapeHTML(achievement.achievement_category)}">
              ${__('achievements.category_' + achievement.achievement_category) || escapeHTML(achievement.achievement_category)}
            </span>
            <span><i class="bi bi-calendar3"></i>${formatDate(achievement.achieved_at)}</span>
          </div>
        </div>
        <button type="button" class="achievement-delete" title="${__('options.delete')}">
          <i class="bi bi-trash3"></i>
        </button>
      </article>
    `
  );
}

async function loadAchievements() {
  const loading = `
    <div class="text-center text-muted py-4">
      <div class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></div>
      ${__('achievements.loading')}
    </div>
  `;
  achievedTasks.innerHTML = loading;
  achievedGoals.innerHTML = loading;
  manualAchievements.innerHTML = loading;

  const response = await achievementRequest({ action: 'fetch_achievements' });

  if (response?.status === 'success') {
    achievementData = response;
    renderAchievements();
    return;
  }

  achievedTasks.innerHTML = `<div class="alert alert-danger">${__('achievements.error_load')}</div>`;
  achievedGoals.innerHTML = `<div class="alert alert-danger">${__('achievements.error_load')}</div>`;
  manualAchievements.innerHTML = `<div class="alert alert-danger">${__('achievements.error_load')}</div>`;
}

achievementForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  const title = achievementTitleInput.value.trim();

  if (!title) {
    setInvalidInput(achievementTitleInput, __('achievements.title_required'));
    return;
  }

  clearInvalidInput(achievementTitleInput);
  saveAchievementBtn.disabled = true;
  saveAchievementBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';

  const response = await achievementRequest({
    action: 'insert',
    achievement_title: title,
    achievement_category: achievementCategorySelect.value,
    achievement_note: achievementNoteInput.value.trim(),
    achieved_at: achievementDateInput.value,
  });

  saveAchievementBtn.disabled = false;
  saveAchievementBtn.innerHTML = `<i class="bi bi-stars"></i> ${__('achievements.save')}`;

  if (response?.status === 'inserted') {
    achievementForm.reset();
    achievementDateInput.valueAsDate = new Date();
    await loadAchievements();
    achievementTitleInput.focus();
    return;
  }

  setInvalidInput(achievementTitleInput, response?.message || __('achievements.error_save'));
});

achievementTitleInput.addEventListener('input', () => clearInvalidInput(achievementTitleInput));

manualAchievements.addEventListener('click', async (event) => {
  const deleteBtn = event.target.closest('.achievement-delete');
  if (!deleteBtn) return;

  const card = deleteBtn.closest('.manual-achievement');
  const response = await achievementRequest({
    action: 'delete-achievement',
    achievement_id: card.dataset.id,
  });

  if (response?.status === 'success') {
    await loadAchievements();
  }
});

periodButtons.forEach((button) => {
  button.addEventListener('click', () => {
    periodButtons.forEach((btn) => btn.classList.remove('active'));
    button.classList.add('active');
    currentPeriod = button.dataset.period;
    renderAchievements();
  });
});

document.addEventListener('DOMContentLoaded', () => {
  achievementDateInput.valueAsDate = new Date();
  loadAchievements();
});
