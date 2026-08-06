/**
 * Store de tema de la aplicación.
 * Gestiona el modo claro/oscuro con persistencia en localStorage y
 * seguimiento de la preferencia del sistema cuando el modo es 'auto'.
 */
import { defineStore } from 'pinia'

const STORAGE_KEY = 'intermedius_theme'
const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')

/**
 * Aplica o remueve la clase .dark en <html> según el modo efectivo.
 * @param {boolean} dark
 * @returns {void}
 */
function applyDark(dark) {
  document.documentElement.classList.toggle('dark', dark)
}

export const useThemeStore = defineStore('theme', {
  state: () => ({
    /** @type {'light'|'dark'|'auto'} Modo seleccionado por el usuario */
    mode: localStorage.getItem(STORAGE_KEY) || 'auto',
  }),

  getters: {
    /**
     * Modo efectivo (resuelve 'auto' contra la preferencia del sistema).
     * @returns {'light'|'dark'}
     */
    effective() {
      if (this.mode !== 'auto') return this.mode
      return mediaQuery.matches ? 'dark' : 'light'
    },
    /** @returns {boolean} Indica si el tema efectivo es oscuro */
    isDark() {
      return this.effective === 'dark'
    },
  },

  actions: {
    /** Aplica el tema efectivo en <html>. @returns {void} */
    init() {
      applyDark(this.isDark)
    },
    /**
     * Alterna entre modo claro y oscuro.
     * 'auto' solo se conserva mientras el usuario no haya elegido explícitamente.
     * @returns {void}
     */
    toggle() {
      this.setMode(this.isDark ? 'light' : 'dark')
    },
    /**
     * Establece el modo y lo persiste.
     * @param {'light'|'dark'|'auto'} mode
     * @returns {void}
     */
    setMode(mode) {
      this.mode = mode
      localStorage.setItem(STORAGE_KEY, mode)
      applyDark(this.isDark)
    },
  },
})
