import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import CalculadoraBidireccional from '../CalculadoraBidireccional.vue'

function mountCalc(props = {}) {
  return mount(CalculadoraBidireccional, {
    props: { monto: '', bolivares: '', tasa: '', ...props },
    global: { stubs: { Iconoir: true } },
  })
}

const SEL = {
  monto: '[placeholder="100.00"]',
  tasa: '[placeholder="36.50"]',
  bolivares: '[placeholder="0.00"]',
}

async function typeInto(wrapper, campo, valor) {
  await wrapper.find(SEL[campo]).setValue(valor)
  const updated = {}
  for (const key of ['monto', 'bolivares', 'tasa']) {
    const events = wrapper.emitted(`update:${key}`)
    const last = events?.[events.length - 1]
    if (last && last[0] !== undefined) updated[key] = last[0]
  }
  if (Object.keys(updated).length) await wrapper.setProps(updated)
}

function lastEmit(wrapper, ev) {
  const events = wrapper.emitted(ev)
  return events?.[events.length - 1]
}

describe('CalculadoraBidireccional', () => {
  it('editar bolívares no pisa la tasa cuando el monto está desactualizado', async () => {
    const wrapper = mountCalc({ monto: '500' })
    await typeInto(wrapper, 'tasa', '36.5')
    await typeInto(wrapper, 'bolivares', '3650')

    const tasaEvents = wrapper.emitted('update:tasa')
    expect(tasaEvents).toHaveLength(1)
    expect(tasaEvents[0]).toEqual(['36.5'])
    expect(lastEmit(wrapper, 'update:monto')).toEqual(['100'])
    expect(wrapper.props('monto')).toBe('100')
    expect(wrapper.props('tasa')).toBe('36.5')
  })

  it('monto + tasa → calcula bolívares', async () => {
    const wrapper = mountCalc()
    await typeInto(wrapper, 'monto', '100')
    await typeInto(wrapper, 'tasa', '36.5')
    expect(lastEmit(wrapper, 'update:bolivares')).toEqual(['3650'])
    expect(wrapper.props('bolivares')).toBe('3650')
  })

  it('monto + bolívares → calcula tasa', async () => {
    const wrapper = mountCalc()
    await typeInto(wrapper, 'monto', '100')
    await typeInto(wrapper, 'bolivares', '3650')
    expect(lastEmit(wrapper, 'update:tasa')).toEqual(['36.5'])
    expect(wrapper.props('tasa')).toBe('36.5')
  })

  it('tasa + bolívares → calcula monto', async () => {
    const wrapper = mountCalc()
    await typeInto(wrapper, 'tasa', '36.5')
    await typeInto(wrapper, 'bolivares', '3650')
    expect(lastEmit(wrapper, 'update:monto')).toEqual(['100'])
    expect(wrapper.props('monto')).toBe('100')
  })

  it('borrar un campo no recalcula el tercero', async () => {
    const wrapper = mountCalc()
    await typeInto(wrapper, 'monto', '100')
    await typeInto(wrapper, 'tasa', '36.5')
    expect(lastEmit(wrapper, 'update:bolivares')).toEqual(['3650'])

    await typeInto(wrapper, 'monto', '')
    expect(lastEmit(wrapper, 'update:bolivares')).toEqual(['3650'])
    expect(lastEmit(wrapper, 'update:tasa')).toEqual(['36.5'])
    expect(wrapper.props('bolivares')).toBe('3650')
    expect(wrapper.props('tasa')).toBe('36.5')
  })
})
