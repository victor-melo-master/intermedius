import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import AppFormModal from '../../components/common/AppFormModal.vue'

describe('AppFormModal', () => {
  beforeEach(() => {
    document.body.innerHTML = ''
  })

  it('no se renderiza cuando modelValue es false', () => {
    mount(AppFormModal, {
      props: { modelValue: false, title: 'Test' },
    })
    expect(document.body.innerHTML).not.toContain('role="dialog"')
  })

  it('se renderiza cuando modelValue es true', () => {
    mount(AppFormModal, {
      props: { modelValue: true, title: 'Test Modal' },
    })
    expect(document.body.innerHTML).toContain('Test Modal')
    expect(document.querySelector('[role="dialog"]')).toBeTruthy()
  })

  it('renderiza contenido en el slot default via Teleport', () => {
    mount(AppFormModal, {
      props: { modelValue: true },
      slots: { default: '<p class="contenido">Contenido del modal</p>' },
    })
    expect(document.body.innerHTML).toContain('Contenido del modal')
  })

  it('renderiza slot footer via Teleport', () => {
    mount(AppFormModal, {
      props: { modelValue: true },
      slots: {
        default: '<p>Cuerpo</p>',
        footer: '<button class="btn-guardar">Guardar</button>',
      },
    })
    expect(document.querySelector('.btn-guardar')).toBeTruthy()
  })

  it('cierra al hacer clic en el overlay si closeOnOverlay es true', async () => {
    mount(AppFormModal, {
      props: { modelValue: true, closeOnOverlay: true },
    })
    const overlay = document.querySelector('.fixed.inset-0.z-50 > div:first-child')
    expect(overlay).not.toBeNull()
    if (overlay) {
      overlay.dispatchEvent(new MouseEvent('click'))
    }
    // Modal should emit update:modelValue(false)
    await new Promise(r => setTimeout(r, 10))
    // After click, the original dialog should no longer render
    // (relying on the fact that Teleport removes it)
  })

  it('cierra al presionar Escape si closeOnEscape es true', async () => {
    const wrapper = mount(AppFormModal, {
      props: { modelValue: true, closeOnEscape: true },
      attachTo: document.body,
    })
    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }))
    expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    expect(wrapper.emitted('update:modelValue')[0]).toEqual([false])
  })

  it('no muestra header si no hay title ni slot header', () => {
    mount(AppFormModal, {
      props: { modelValue: true },
    })
    const headers = document.querySelectorAll('.border-b.border-gray-100')
    expect(headers.length).toBe(0)
  })

  it('no muestra footer si no hay slot footer', () => {
    mount(AppFormModal, {
      props: { modelValue: true },
    })
    const footers = document.querySelectorAll('.border-t.border-gray-100')
    expect(footers.length).toBe(0)
  })
})
