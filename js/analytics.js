const nextActionCard = document.getElementById('next-action-card');
const analyticsSummary = document.getElementById('analytics-summary');
const weeklyBars = document.getElementById('weekly-bars');
const categoryAnalytics = document.getElementById('category-analytics');
const attentionList = document.getElementById('attention-list');
const stuckGoals = document.getElementById('stuck-goals');

function escapeHTML(str = '') {
  return String(str).replace(/[&<>'"]/g, (c) =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[c])
  );
}

function formatDate(dateValue) {
  if (!dateValue) return '';
  const date = new Date(`${dateValue}T00:00:00`);
  return date.toLocaleDateString(document.documentElement.lang || 'en', {
    month: 'short',
    day: 'numeric',
  });
}

async function analyticsRequest() {
  try {
    const response = await fetch('analyticsProcess.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'fetch_analytics' }),
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

function actionCopy(action) {
  const title = action?.title ? escapeHTML(action.title) : '';
  const detail = action?.detail ? escapeHTML(action.detail) : '';

  const map = {
    overdue: {
      icon: 'bi-alarm',
      title: __('analytics.action_overdue_title'),
      body: __('analytics.action_overdue_body').replace('%item%', title),
      meta: detail,
    },
    upcoming: {
      icon: 'bi-calendar-event',
      title: __('analytics.action_upcoming_title'),
      body: __('analytics.action_upcoming_body').replace('%item%', title),
      meta: detail ? `${__('analytics.due')} ${detail}` : '',
    },
    stuck_goal: {
      icon: 'bi-signpost-split',
      title: __('analytics.action_stuck_title'),
      body: __('analytics.action_stuck_body').replace('%item%', title),
      meta: detail,
    },
    pending: {
      icon: 'bi-lightning-charge',
      title: __('analytics.action_pending_title'),
      body: __('analytics.action_pending_body'),
      meta: '',
    },
    clear: {
      icon: 'bi-stars',
      title: __('analytics.action_clear_title'),
      body: __('analytics.action_clear_body'),
      meta: '',
    },
  };

  return map[action?.type] || map.clear;
}

function renderNextAction(action) {
  const copy = actionCopy(action);
  nextActionCard.innerHTML = `
    <div class="next-action-icon"><i class="bi ${copy.icon}"></i></div>
    <div>
      <p class="achievement-kicker">${__('analytics.next_best_action')}</p>
      <h2>${copy.title}</h2>
      <p>${copy.body}</p>
      ${copy.meta ? `<span class="next-action-meta">${copy.meta}</span>` : ''}
    </div>
  `;
}

function renderSummary(summary) {
  analyticsSummary.innerHTML = `
    <div class="analytics-stat">
      <strong>${summary.task_completion_rate}%</strong>
      <span>${__('analytics.task_completion')}</span>
    </div>
    <div class="analytics-stat">
      <strong>${summary.goal_completion_rate}%</strong>
      <span>${__('analytics.goal_completion')}</span>
    </div>
    <div class="analytics-stat">
      <strong>${summary.overdue_tasks}</strong>
      <span>${__('analytics.overdue')}</span>
    </div>
    <div class="analytics-stat">
      <strong>${summary.upcoming_tasks}</strong>
      <span>${__('analytics.upcoming')}</span>
    </div>
  `;
}

function renderWeeklyTrend(weeks) {
  const maxTotal = Math.max(1, ...weeks.map((week) => Number(week.total || 0)));
  weeklyBars.innerHTML = weeks.map((week) => {
    const height = Math.max(8, Math.round((Number(week.total || 0) / maxTotal) * 100));
    return `
      <div class="weekly-bar-item">
        <div class="weekly-bar-stack" title="${week.total}">
          <span style="height:${height}%"></span>
        </div>
        <strong>${week.total}</strong>
        <small>${escapeHTML(week.label)}</small>
      </div>
    `;
  }).join('');
}

function renderCategories(categories) {
  const maxActivity = Math.max(1, ...categories.map((cat) =>
    Number(cat.goals || 0) + Number(cat.tasks || 0) + Number(cat.manual_achievements || 0)
  ));

  categoryAnalytics.innerHTML = categories.map((cat) => {
    const total = Number(cat.goals || 0) + Number(cat.tasks || 0) + Number(cat.manual_achievements || 0);
    const width = Math.round((total / maxActivity) * 100);
    return `
      <div class="category-analytic-row">
        <div>
          <span class="category category-${escapeHTML(cat.category)}">${__('achievements.category_' + cat.category) || escapeHTML(cat.category)}</span>
          <small>${cat.completed_tasks}/${cat.tasks} ${__('analytics.tasks_short')} · ${cat.completed_goals}/${cat.goals} ${__('analytics.goals_short')}</small>
        </div>
        <div class="category-analytic-track">
          <span style="width:${width}%"></span>
        </div>
      </div>
    `;
  }).join('');
}

function emptyList(text) {
  return `<div class="analytics-empty"><i class="bi bi-check2-circle"></i>${text}</div>`;
}

function renderAttention(overdue, upcoming) {
  const items = [
    ...overdue.map((task) => ({ ...task, kind: 'overdue' })),
    ...upcoming.map((task) => ({ ...task, kind: 'upcoming' })),
  ];

  if (!items.length) {
    attentionList.innerHTML = emptyList(__('analytics.no_attention'));
    return;
  }

  attentionList.innerHTML = items.map((task) => `
    <article class="analytics-list-item ${task.kind}">
      <i class="bi ${task.kind === 'overdue' ? 'bi-exclamation-triangle' : 'bi-calendar-event'}"></i>
      <div>
        <strong>${escapeHTML(task.task_title)}</strong>
        <span>${task.goal_name ? escapeHTML(task.goal_name) : __('tasks.no_goal')} ${task.due_date ? `· ${formatDate(task.due_date)}` : ''}</span>
      </div>
    </article>
  `).join('');
}

function renderStuckGoals(goals) {
  if (!goals.length) {
    stuckGoals.innerHTML = emptyList(__('analytics.no_stuck_goals'));
    return;
  }

  stuckGoals.innerHTML = goals.map((goal) => `
    <article class="analytics-list-item">
      <i class="bi bi-bullseye"></i>
      <div>
        <strong>${escapeHTML(goal.goal_name)}</strong>
        <span>${goal.tasks_completed}/${goal.tasks_total} ${__('analytics.tasks_short')} · ${goal.progress}%</span>
        <div class="analytics-mini-track"><span style="width:${goal.progress}%"></span></div>
      </div>
    </article>
  `).join('');
}

function renderAnalytics(analytics) {
  renderNextAction(analytics.next_action);
  renderSummary(analytics.summary);
  renderWeeklyTrend(analytics.weekly_trend || []);
  renderCategories(analytics.category_stats || []);
  renderAttention(analytics.overdue_tasks_list || [], analytics.upcoming_tasks_list || []);
  renderStuckGoals(analytics.stuck_goals || []);
}

async function loadAnalytics() {
  const response = await analyticsRequest();

  if (response?.status === 'success') {
    renderAnalytics(response.analytics);
    return;
  }

  nextActionCard.innerHTML = `<div class="alert alert-danger">${__('analytics.error_load')}</div>`;
}

document.addEventListener('DOMContentLoaded', loadAnalytics);
