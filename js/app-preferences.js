(function () {
  const LANG_KEY = 'nafsi_lang';
  const THEME_KEY = 'nafsi_theme';
  const allowedLangs = ['en', 'ar'];
  const allowedThemes = ['light', 'dark'];

  function getStored(key) {
    try {
      return localStorage.getItem(key);
    } catch {
      return null;
    }
  }

  function setStored(key, value) {
    try {
      localStorage.setItem(key, value);
    } catch {
      // Storage can be unavailable in private or locked-down browser contexts.
    }
  }

  function applyTheme(theme) {
    const nextTheme = allowedThemes.includes(theme) ? theme : 'light';
    document.documentElement.setAttribute('data-bs-theme', nextTheme);
    document.documentElement.setAttribute('data-theme', nextTheme);
  }

  function syncStoredLanguage() {
    const storedLang = getStored(LANG_KEY);
    const currentLang = document.documentElement.lang || 'en';

    if (allowedLangs.includes(storedLang) && storedLang !== currentLang) {
      window.location.href = `lang_switch.php?lang=${encodeURIComponent(storedLang)}`;
    }
  }

  applyTheme(getStored(THEME_KEY));

  document.addEventListener('DOMContentLoaded', () => {
    syncStoredLanguage();

    document.querySelectorAll('[data-lang]').forEach((item) => {
      item.addEventListener('click', () => {
        const lang = item.getAttribute('data-lang');
        if (allowedLangs.includes(lang)) {
          setStored(LANG_KEY, lang);
        }
      });
    });

    document.querySelectorAll('[data-theme-value]').forEach((item) => {
      item.addEventListener('click', (event) => {
        event.preventDefault();
        const theme = item.getAttribute('data-theme-value');

        if (allowedThemes.includes(theme)) {
          setStored(THEME_KEY, theme);
          applyTheme(theme);
        }
      });
    });
  });
})();
