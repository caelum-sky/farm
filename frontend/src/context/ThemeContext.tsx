import { createContext, useContext, useEffect, useState } from 'react';

export type ThemeMode = 'system' | 'light' | 'dark';

interface ThemeContextType {
  mode: ThemeMode;
  setMode: (mode: ThemeMode) => void;
  systemMode: 'light' | 'dark';
}

const ThemeContext = createContext<ThemeContextType | undefined>(undefined);

export function useTheme() {
  const context = useContext(ThemeContext);
  if (!context) {
    throw new Error('useTheme must be used within a ThemeProvider');
  }
  return context;
}

export function ThemeProvider({ children }: { children: React.ReactNode }) {
  const [mode, setMode] = useState<ThemeMode>(() => {
    const saved = window.localStorage.getItem('theme-mode');
    if (saved) {
      return saved as ThemeMode;
    }
    return 'system';
  });

  const systemMode = window.matchMedia('(prefers-color-scheme: dark)').matches
    ? 'dark'
    : 'light';

  useEffect(() => {
    const root = window.document.documentElement;

    const applyDarkClass = (useDark: boolean) => {
      if (useDark) {
        root.classList.add('dark');
      } else {
        root.classList.remove('dark');
      }
    };

    const updateTheme = () => {
      let isDark = false;
      if (mode === 'system') {
        isDark = systemMode === 'dark';
      } else if (mode === 'dark') {
        isDark = true;
      } else {
        isDark = false;
      }
      applyDarkClass(isDark);
    };

    updateTheme();

    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    const handler = () => updateTheme();
    mediaQuery.addEventListener('change', handler);

    return () => mediaQuery.removeEventListener('change', handler);
  }, [mode, systemMode]);

  return (
    <ThemeContext.Provider value={{ mode, setMode, systemMode }}>
      {children}
    </ThemeContext.Provider>
  );
}