<template>
  <div class="space-y-4">
    <div v-if="toast.msg"
      class="fixed top-4 right-4 z-[70] max-w-sm bg-warning-soft border border-warning-edge text-warning-strong text-sm px-4 py-3 rounded-xl shadow-lg">
      {{ toast.msg }}
    </div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <h2 class="text-xl font-bold text-heading">Usuarios</h2>
      <button @click="openForm()" class="px-4 py-2 bg-gold text-navy rounded-lg text-sm font-medium hover:bg-gold-dark flex items-center gap-1">
        <span>+</span> Nuevo usuario
      </button>
    </div>

    <!-- Filtros -->
    <div class="space-y-2">
      <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="relative flex-1">
          <input v-model="search" @input="onSearch" placeholder="Buscar por nombre o correo..."
            class="w-full pl-10 pr-4 py-2 bg-white dark:bg-surface-muted border border-edge-strong rounded-xl focus:ring-2 focus:ring-gold outline-none text-sm" />
          <Iconoir name="magnifying-glass" class="absolute left-3 top-2.5 w-5 h-5 text-ink-muted" />
          <button v-if="search" @click="search = ''; cargarUsuarios()"
            class="absolute inset-y-0 right-0 flex items-center pr-3 text-ink-muted hover:text-ink-muted" title="Limpiar búsqueda">
            <Iconoir name="x-mark" class="w-5 h-5" />
          </button>
        </div>
        <div class="flex gap-2 overflow-x-auto pb-1">
          <button v-for="estado in estadoOpciones" :key="estado.value" @click="cambiarEstado(estado.value)"
            class="px-3 py-1.5 rounded-full text-xs font-medium border whitespace-nowrap transition-colors"
            :class="estadoFiltro === estado.value ? 'bg-gold text-navy border-gold' : 'bg-white dark:bg-surface-muted text-ink-muted border-edge-strong hover:bg-surface-soft'">
            {{ estado.label }}
          </button>
        </div>
      </div>
      <div class="flex gap-2 overflow-x-auto pb-1">
        <button v-for="rol in rolOpciones" :key="rol.value" @click="cambiarRol(rol.value)"
          class="px-3 py-1.5 rounded-full text-xs font-medium border whitespace-nowrap transition-colors"
          :class="rolFiltro === rol.value ? 'bg-gold text-navy border-gold' : 'bg-white dark:bg-surface-muted text-ink-muted border-edge-strong hover:bg-surface-soft'">
          {{ rol.label }}
        </button>
      </div>
    </div>

    <div v-if="usuarios.loading" class="text-center py-12">
      <div class="w-8 h-8 border-2 border-gold border-t-transparent rounded-full animate-spin mx-auto"></div>
    </div>
    <div v-else-if="usuarios.error" class="bg-danger-soft text-danger p-4 rounded-xl">
      {{ usuarios.error }}
      <button @click="usuarios.fetchAll()" class="underline ml-2">Reintentar</button>
    </div>
    <div v-else-if="usuarios.list.length === 0" class="text-center py-16">
      <Iconoir name="users" class="w-12 h-12 mx-auto mb-4 text-ink-muted" />
      <p class="text-ink-muted">No hay usuarios registrados</p>
    </div>
    <div v-else class="space-y-2">
      <div v-for="u in usuarios.list" :key="u.id"
        class="bg-surface border border-edge rounded-xl p-4 flex items-center gap-3 cursor-pointer hover:border-gold hover:shadow-sm transition"
        :class="{ 'opacity-60': !u.activo }" @click="openDetalle(u)">
        <div class="shrink-0">
          <img v-if="avatarUrl(u)" :src="avatarUrl(u)" :alt="`Avatar de ${u.name}`"
            class="w-10 h-10 rounded-full object-cover border border-edge cursor-pointer"
            title="Ampliar foto" @click.stop="zoomAvatar(avatarUrl(u))" />
          <div v-else class="w-10 h-10 rounded-full bg-gold-soft flex items-center justify-center text-gold-dark font-bold text-sm">
            {{ u.name?.charAt(0).toUpperCase() }}
          </div>
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <p class="font-semibold text-sm">{{ u.name }}</p>
            <span v-for="rol in u.roles" :key="rol"
              class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full"
              :class="roleBadgeClass(rol)">
              <Iconoir :name="roleIcon(rol)" class="w-3 h-3" />
              {{ roleLabel(rol) }}
            </span>
          </div>
          <p class="text-sm text-ink-muted">{{ u.email }}</p>
          <p v-if="u.titular" class="text-sm text-ink-muted">Titular: {{ u.titular.alias }}</p>
          <p v-if="u.last_login_at" class="text-sm text-ink-muted">Último acceso: {{ formatDate(u.last_login_at) }}</p>
        </div>
        <div v-if="!isSuperAdmin(u)" class="flex items-center gap-2">
          <!-- Toggle activo -->
          <button type="button" @click.stop="handleToggle(u)" :title="u.activo ? 'Desactivar' : 'Activar'"
            class="relative w-11 h-6 rounded-full transition-colors shrink-0"
            :class="u.activo ? 'bg-success' : 'bg-surface-muted'">
            <span class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow-sm transition-transform"
              :class="u.activo ? 'translate-x-5' : 'translate-x-0'"></span>
          </button>
          <button type="button" @click.stop="openDetalle(u)" title="Ver detalle"
            class="text-sm text-ink-muted hover:text-heading font-medium px-2 py-1 border border-edge rounded-lg hover:bg-surface-soft flex items-center gap-1">
            <Iconoir name="eye" class="w-3.5 h-3.5" />
            Detalle
          </button>
          <button type="button" @click.stop="openForm(u)" class="text-xs text-gold-dark hover:text-gold-dark font-medium px-2 py-1 border border-gold/40 rounded-lg hover:bg-gold-soft">
            Editar
          </button>
        </div>
      </div>
    </div>

    <AppFormModal v-model="showForm" :title="editing ? 'Editar usuario' : 'Nuevo usuario'" @close="closeForm">
      <form @submit.prevent="submit" novalidate class="space-y-3">
        <!-- Avatar -->
        <div class="flex items-center gap-4">
          <div class="relative shrink-0">
            <div v-if="avatarPreview || avatarUrl(usuarioEnEdicion)"
              class="w-16 h-16 rounded-full overflow-hidden border border-edge cursor-pointer"
              title="Ampliar foto" @click="zoomAvatar(avatarPreview || avatarUrl(usuarioEnEdicion))">
              <img v-if="avatarPreview" :src="avatarPreview" alt="Vista previa del avatar"
                class="w-full h-full object-cover" />
              <img v-else :src="avatarUrl(usuarioEnEdicion)" alt="Avatar del usuario"
                class="w-full h-full object-cover" />
            </div>
            <div v-else
              class="w-16 h-16 rounded-full bg-gold text-navy flex items-center justify-center text-2xl font-semibold">
              {{ (form.name || '?').charAt(0).toUpperCase() }}
            </div>
          </div>
          <div class="flex-1">
            <label class="text-sm text-ink-muted mb-1 block">Foto de perfil</label>
            <input ref="avatarInput" type="file" accept="image/jpeg,image/png,image/gif,image/webp,image/bmp"
              @change="onAvatarSelected"
              class="block w-full text-sm text-ink-muted file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-gold-soft file:text-gold-dark file:font-semibold hover:file:bg-gold/20 cursor-pointer" />
            <div class="flex items-center gap-3 mt-1">
              <p class="text-sm text-ink-muted">JPG, PNG, GIF, WebP o BMP · máx 2MB</p>
              <button v-if="avatarPreview || avatarFile" type="button" @click="limpiarAvatar"
                class="text-xs font-medium text-danger hover:text-danger-strong underline">
                Quitar selección
              </button>
            </div>
            <p v-if="avatarError" class="text-xs text-danger mt-1">{{ avatarError }}</p>
          </div>
        </div>

        <!-- Nombre -->
        <div>
          <label class="text-sm text-ink-muted mb-1 block">Nuevo nombre de usuario *</label>
          <input v-model="form.name" required placeholder="Nombre completo"
            @input="nameError = ''; nameOk = ''"
            @blur="validarDisponible('name')"
            :class="inputClass(!!nameError)"
            class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />
          <p v-if="nameOk" class="text-xs text-success mt-1">{{ nameOk }}</p>
          <p v-if="nameError" class="text-xs text-danger mt-1">{{ nameError }}</p>
        </div>

        <!-- Email -->
        <div>
          <label class="text-sm text-ink-muted mb-1 block">Nuevo correo de usuario *</label>
          <input v-model="form.email" type="email" required placeholder="correo@ejemplo.com"
            @input="emailError = ''; emailOk = ''"
            @blur="validarDisponible('email')"
            class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />
          <p v-if="emailOk" class="text-xs text-success mt-1">{{ emailOk }}</p>
          <p v-if="emailError" class="text-xs text-danger mt-1">{{ emailError }}</p>
        </div>

        <!-- Password -->
        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="text-sm text-ink-muted block">{{ editing ? 'Nueva contraseña de usuario (opcional)' : 'Nueva contraseña de usuario *' }}</label>
            <button type="button" @click="generarPassword"
              class="text-xs font-medium text-gold-dark hover:text-gold-dark underline">
              Generar contraseña segura
            </button>
          </div>
          <div class="relative">
            <input v-model="form.password" :type="mostrarPassword ? 'text' : 'password'"
              placeholder="Mínimo 8 caracteres, con mayúsculas, números y símbolos"
              :required="!editing"
              class="w-full px-3 py-2 pr-10 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />
            <button type="button" @click="mostrarPassword = !mostrarPassword"
              :title="mostrarPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
              class="absolute inset-y-0 right-0 flex items-center pr-3 text-ink-muted hover:text-ink-muted"
              :aria-label="mostrarPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'">
              <Iconoir :name="mostrarPassword ? 'eye-slash' : 'eye'" class="w-5 h-5" />
            </button>
          </div>
          <p v-if="editing" class="text-sm text-ink-muted mt-1">Dejar en blanco para mantener la contraseña actual.</p>
          <ul v-if="!editing || form.password" class="mt-2 space-y-1">
            <li v-for="req in passwordRequisitos" :key="req.label"
              class="flex items-center gap-1.5 text-xs"
              :class="req.cumplido ? 'text-success' : 'text-ink-muted'">
              <span class="w-3.5 text-center">{{ req.cumplido ? '✓' : '•' }}</span>
              {{ req.label }}
            </li>
          </ul>
        </div>

        <!-- Rol -->
        <div ref="rolDropdownRef" class="relative">
          <label class="text-sm text-ink-muted mb-1 block">Rol *</label>
          <button type="button" @click="rolDropdownOpen = !rolDropdownOpen"
            class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none flex items-center justify-between gap-2 text-left text-sm">
            <span v-if="form.rol" class="inline-flex items-center gap-2">
              <Iconoir :name="roleIcon(form.rol)" class="w-4 h-4" />
              {{ roleLabel(form.rol) }}
            </span>
            <span v-else class="text-ink-muted">Seleccionar rol</span>
            <svg class="w-4 h-4 text-ink-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          <div v-if="rolDropdownOpen"
            class="absolute z-10 mt-1 w-full bg-surface border border-edge rounded-xl shadow-lg max-h-56 overflow-y-auto">
            <button v-for="op in rolOptions" :key="op.value" type="button"
              @click="selectRol(op.value)"
              class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left hover:bg-gold-soft"
              :class="form.rol === op.value ? 'bg-gold-soft text-gold-dark font-medium' : 'text-ink'">
              <Iconoir :name="roleIcon(op.value)" class="w-4 h-4" />
              {{ op.label }}
            </button>
          </div>
        </div>

        <!-- Titular (opcional) -->
        <div ref="titularDropdownRef" class="relative">
          <label class="text-sm text-ink-muted mb-1 block">Titular asociado (opcional)</label>
          <button type="button" @click="titularDropdownOpen = !titularDropdownOpen"
            class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none flex items-center justify-between gap-2 text-left text-sm">
            <span v-if="form.titular_id" class="inline-flex items-center gap-2">
              <Iconoir name="identification" class="w-4 h-4 text-ink-muted" />
              {{ titularLabel(form.titular_id) }}
            </span>
            <span v-else class="inline-flex items-center gap-2 text-ink-muted">
              <Iconoir name="identification" class="w-4 h-4" />
              Sin titular
            </span>
            <svg class="w-4 h-4 text-ink-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          <div v-if="titularDropdownOpen"
            class="absolute z-10 mt-1 w-full bg-surface border border-edge rounded-xl shadow-lg max-h-56 overflow-y-auto">
            <button type="button" @click="selectTitular(null)"
              class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left hover:bg-gold-soft text-ink"
              :class="!form.titular_id ? 'bg-gold-soft text-gold-dark font-medium' : ''">
              Sin titular
            </button>
            <button v-for="t in titulares" :key="t.id" type="button"
              @click="selectTitular(t.id)"
              class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left hover:bg-gold-soft"
              :class="form.titular_id === t.id ? 'bg-gold-soft text-gold-dark font-medium' : 'text-ink'">
              {{ t.alias }} — {{ t.nombre }}
            </button>
          </div>
        </div>

        <!-- Activo -->
        <label class="flex items-center gap-2 text-sm text-ink-muted">
          <input v-model="form.activo" type="checkbox" class="w-4 h-4 rounded border-edge-strong text-gold-dark focus:ring-gold" />
          Activo
        </label>

        <div v-if="formError" class="bg-danger-soft text-danger text-sm p-3 rounded-lg whitespace-pre-line">{{ formError }}</div>
      </form>
      <template #footer>
        <button @click="submit" :disabled="!formValido || saving"
          class="w-full bg-gold text-navy font-semibold py-2.5 rounded-lg hover:bg-gold-dark disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center justify-center gap-2">
          <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          {{ saving ? 'Guardando...' : (editing ? 'Guardar cambios' : 'Crear usuario') }}
        </button>
      </template>
    </AppFormModal>

    <!-- Detalle del usuario -->
    <AppFormModal :model-value="mostrarDetalle" title="Detalle del usuario" @close="mostrarDetalle = false">
      <template #header>
        <div class="flex items-center gap-3">
          <img v-if="avatarUrl(usuarioDetalle)" :src="avatarUrl(usuarioDetalle)" :alt="`Avatar de ${usuarioDetalle?.name}`"
            class="w-10 h-10 rounded-full object-cover border border-edge cursor-pointer"
            title="Ampliar foto" @click.stop="zoomAvatar(avatarUrl(usuarioDetalle))" />
          <div v-else class="w-10 h-10 rounded-full bg-gold-soft flex items-center justify-center text-gold-dark font-bold">
            {{ usuarioDetalle?.name?.charAt(0).toUpperCase() }}
          </div>
          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <h3 class="text-lg font-bold text-heading">{{ usuarioDetalle?.name }}</h3>
              <span v-for="rol in usuarioDetalle?.roles ?? []" :key="rol"
                class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full"
                :class="roleBadgeClass(rol)">
                <Iconoir :name="roleIcon(rol)" class="w-3 h-3" />
                {{ roleLabel(rol) }}
              </span>
            </div>
            <p class="text-sm text-ink-muted">{{ usuarioDetalle?.email }}</p>
          </div>
        </div>
      </template>

      <div v-if="usuarioDetalle" class="grid grid-cols-2 gap-3 mb-5">
        <div class="bg-surface-soft rounded-lg p-3">
          <p class="text-sm uppercase tracking-wide text-ink-muted font-semibold mb-1">Estado</p>
          <p class="text-sm font-medium" :class="usuarioDetalle.activo ? 'text-success' : 'text-ink-muted'">
            {{ usuarioDetalle.activo ? 'Activo' : 'Inactivo' }}
          </p>
        </div>
        <div class="bg-surface-soft rounded-lg p-3">
          <p class="text-sm uppercase tracking-wide text-ink-muted font-semibold mb-1">Titular</p>
          <p class="text-sm font-medium text-ink">
            {{ usuarioDetalle.titular ? `${usuarioDetalle.titular.alias} — ${usuarioDetalle.titular.nombre}` : 'Sin titular' }}
          </p>
        </div>
        <div class="bg-surface-soft rounded-lg p-3">
          <p class="text-sm uppercase tracking-wide text-ink-muted font-semibold mb-1">Último acceso</p>
          <p class="text-sm font-medium text-ink">{{ usuarioDetalle.last_login_at ? formatDateTime(usuarioDetalle.last_login_at) : 'Nunca' }}</p>
        </div>
        <div class="bg-surface-soft rounded-lg p-3">
          <p class="text-sm uppercase tracking-wide text-ink-muted font-semibold mb-1">Creado el</p>
          <p class="text-sm font-medium text-ink">{{ formatDateTime(usuarioDetalle.created_at) }}</p>
        </div>
      </div>

      <div class="flex items-center gap-2 mb-2">
        <Iconoir name="clock" class="w-4 h-4 text-ink-muted" />
        <h4 class="text-sm font-bold text-ink">Historial de sesiones</h4>
      </div>

      <div v-if="sesionesLoading" class="text-center py-10">
        <div class="w-8 h-8 border-2 border-gold border-t-transparent rounded-full animate-spin mx-auto"></div>
      </div>
      <div v-else-if="sesionesError" class="bg-danger-soft text-danger p-4 rounded-xl text-sm">
        {{ sesionesError }}
      </div>
      <div v-else-if="sesiones.length === 0" class="text-center py-10">
        <p class="text-ink-muted text-sm">No hay sesiones registradas para este usuario.</p>
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-sm uppercase text-ink-muted border-b border-edge">
              <th class="py-2 pr-3 font-semibold">Inicio</th>
              <th class="py-2 pr-3 font-semibold">IP</th>
              <th class="py-2 pr-3 font-semibold">Dispositivo</th>
              <th class="py-2 pr-3 font-semibold">Duración</th>
              <th class="py-2 font-semibold">Cierre</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in sesiones" :key="s.id" class="border-b border-edge last:border-0">
              <td class="py-2.5 pr-3 whitespace-nowrap text-ink">{{ formatDateTime(s.login_at) }}</td>
              <td class="py-2.5 pr-3 whitespace-nowrap text-ink-muted">{{ s.ip_address || '—' }}</td>
              <td class="py-2.5 pr-3 text-ink-muted">
                {{ dispositivo(s) }}
              </td>
              <td class="py-2.5 pr-3 whitespace-nowrap text-ink-muted">
                {{ duracion(s) }}
              </td>
              <td class="py-2.5">
                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full"
                  :class="cierreBadge(s).clase">
                  {{ cierreBadge(s).texto }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <template #footer>
        <div class="flex gap-2">
          <button v-if="auth.isAdmin && !isSuperAdmin(usuarioDetalle)" @click="editarDesdeDetalle"
            class="flex-1 bg-gold text-navy font-semibold py-2.5 rounded-lg hover:bg-gold-dark transition">
            Editar usuario
          </button>
          <button @click="mostrarDetalle = false"
            class="flex-1 bg-surface-muted text-ink font-semibold py-2.5 rounded-lg hover:bg-surface-muted transition">
            Cerrar
          </button>
        </div>
      </template>
    </AppFormModal>

    <!-- Lightbox: foto de perfil ampliada -->
    <div v-if="avatarZoom" class="fixed inset-0 z-[80] bg-black/70 flex items-center justify-center p-4"
      @click.self="avatarZoom = null">
      <button type="button" @click="avatarZoom = null" aria-label="Cerrar"
        class="absolute top-4 right-4 p-2 rounded-full bg-white/10 text-white hover:bg-white/20 transition">
        <Iconoir name="x-mark" class="w-6 h-6" />
      </button>
      <img :src="avatarZoom" alt="Foto de perfil ampliada"
        class="max-h-[80vh] max-w-full rounded-2xl border border-white/20 shadow-2xl object-contain" />
    </div>
  </div>
</template>

<script setup>
/**
 * UsuariosView — CRUD de usuarios del sistema.
 * Permite listar, crear y editar usuarios con nombre, email, contraseña, rol,
 * titular asociado y estado activo/inactivo. Incluye toggle de activación,
 * badges de roles y selector de titular.
 */
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue'
import { useFormatting } from '@/composables/useFormatting'
import { useApiError } from '@/composables/useApiError'
import api from '../../api/axios.js'
import { useUsuariosStore } from '../../stores/usuarios.js'
import { useAuthStore } from '../../stores/auth.js'
import AppFormModal from '@/components/common/AppFormModal.vue'
import Iconoir from '../../components/common/Iconoir.vue'
import { parseUserAgent } from '../../utils/device.js'

/** Store de usuarios */
const usuarios = useUsuariosStore()
/** Store de autenticación (para condicionar el filtro super_admin) */
const auth = useAuthStore()
const { formatDate, formatDateTime } = useFormatting()
const { parseError } = useApiError()

/**
 * URL autenticada del avatar de un usuario (imagen WebP servida por la API).
 * @param {Object} u - Usuario con { id, avatar_path }
 * @returns {string|null} URL con token, o null si no tiene avatar
 */
function avatarUrl(u) {
  if (!u?.avatar_path) return null
  const token = localStorage.getItem('token')
  return `${import.meta.env.VITE_API_URL}/usuarios/${u.id}/avatar?token=${token}`
}
/** Lista de titulares para el selector del formulario */
const titulares = ref([])
/** Controla visibilidad del modal */
const showForm = ref(false)
/** Indica guardado en curso */
const saving = ref(false)
/** Mensaje de error del formulario */
const formError = ref('')
/** Indica si se está editando un usuario existente */
const editing = ref(false)
/** ID del usuario en edición */
const editId = ref(null)
/** Error de disponibilidad del nombre de usuario */
const nameError = ref('')
/** Confirmación verde de disponibilidad del nombre de usuario */
const nameOk = ref('')
/** Error de disponibilidad del correo */
const emailError = ref('')
/** Confirmación verde de disponibilidad del correo */
const emailOk = ref('')
/** Controla la visibilidad de la contraseña en el formulario */
const mostrarPassword = ref(false)
/** Archivo de avatar seleccionado en el formulario (aún sin subir) */
const avatarFile = ref(null)
/** Vista previa local del avatar seleccionado (URL de objeto) */
const avatarPreview = ref('')
/** Error de validación del archivo de avatar */
const avatarError = ref('')
/** Usuario que se está editando (para mostrar su avatar actual) */
const usuarioEnEdicion = computed(() => (editing.value ? usuarios.list.find((u) => u.id === editId.value) || null : null))
/** Toast de advertencia (contraseña comprometida) */
const toast = ref({ msg: '' })
let toastTimer = null

/**
 * Muestra un toast de advertencia por unos segundos.
 * @param {string} msg
 */
function mostrarToast(msg) {
  toast.value = { msg }
  clearTimeout(toastTimer)
  toastTimer = setTimeout(() => { toast.value = { msg: '' } }, 6000)
}

/**
 * Devuelve un índice aleatorio seguro (sin sesgo de módulo) en [0, max).
 * @param {number} max
 * @returns {number}
 */
function indiceAleatorio(max) {
  const limite = Math.floor(0x100000000 / max) * max
  const arr = new Uint32Array(1)
  do {
    crypto.getRandomValues(arr)
  } while (arr[0] >= limite)
  return arr[0] % max
}

/**
 * Genera una contraseña segura aleatoria que cumple los requisitos del
 * formulario y la coloca en el campo (visible para copiarla).
 */
function generarPassword() {
  const mayusculas = 'ABCDEFGHJKLMNPQRSTUVWXYZ'
  const minusculas = 'abcdefghijkmnopqrstuvwxyz'
  const numeros = '23456789'
  const simbolos = '!@#$%&*_+?-='
  const todas = mayusculas + minusculas + numeros + simbolos

  const pass = [
    mayusculas[indiceAleatorio(mayusculas.length)],
    minusculas[indiceAleatorio(minusculas.length)],
    numeros[indiceAleatorio(numeros.length)],
    simbolos[indiceAleatorio(simbolos.length)],
  ]

  for (let i = 0; i < 12; i++) {
    pass.push(todas[indiceAleatorio(todas.length)])
  }

  for (let i = pass.length - 1; i > 0; i--) {
    const j = indiceAleatorio(i + 1)
    ;[pass[i], pass[j]] = [pass[j], pass[i]]
  }

  form.password = pass.join('')
  mostrarPassword.value = true
}

/** Tipos de imagen aceptados para el avatar (se convierten a WebP en el backend) */
const avatarTiposAceptados = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp']

/**
 * Maneja la selección de un archivo de avatar: valida tipo y tamaño,
 * y genera una vista previa local.
 * @param {Event} evt - Evento change del input file
 */
function onAvatarSelected(evt) {
  const file = evt.target.files?.[0]
  avatarError.value = ''
  if (!file) return
  if (!avatarTiposAceptados.includes(file.type)) {
    avatarError.value = 'Formato no permitido. Usa JPG, PNG, GIF, WebP o BMP.'
    avatarFile.value = null
    avatarPreview.value = ''
    evt.target.value = ''
    return
  }
  if (file.size > 2 * 1024 * 1024) {
    avatarError.value = 'La imagen supera los 2MB. Elige una más pequeña.'
    avatarFile.value = null
    avatarPreview.value = ''
    evt.target.value = ''
    return
  }
  avatarFile.value = file
  avatarPreview.value = URL.createObjectURL(file)
}

/** Limpia la selección de avatar del formulario. */
function limpiarAvatar() {
  if (avatarPreview.value) URL.revokeObjectURL(avatarPreview.value)
  avatarFile.value = null
  avatarPreview.value = ''
  avatarError.value = ''
}

/**
 * Clase CSS del input según si tiene error de disponibilidad.
 * @param {boolean} hasError
 * @returns {string}
 */
const inputClass = (hasError) => (hasError ? 'border-danger' : '')

/** Datos del formulario */
const form = reactive({
  name: '',
  email: '',
  password: '',
  rol: '',
  titular_id: null,
  activo: true,
})

/**
 * Verifica que un correo tenga formato válido.
 * @param {string} email
 * @returns {boolean}
 */
const esEmailValido = (email) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(email).trim())

/**
 * Indica si los campos obligatorios del formulario están completos.
 * La contraseña solo es obligatoria al crear; el titular es opcional.
 * @type {import('vue').ComputedRef<boolean>}
 */
const formValido = computed(() => {
  const camposBase = !!form.name.trim() && esEmailValido(form.email) && !!form.rol
  return editing.value ? camposBase : camposBase && !!form.password
})

/**
 * Requisitos de la contraseña (coinciden con Password::min(8)->mixedCase()->numbers()->symbols()
 * del backend) con estado de cumplimiento en vivo.
 * @type {import('vue').ComputedRef<Array<{label: string, cumplido: boolean}>>}
 */
const passwordRequisitos = computed(() => {
  const p = form.password
  return [
    { label: 'Mínimo 8 caracteres',              cumplido: p.length >= 8 },
    { label: 'Mayúsculas y minúsculas',          cumplido: /[a-z]/.test(p) && /[A-Z]/.test(p) },
    { label: 'Al menos un número',               cumplido: /\d/.test(p) },
    { label: 'Al menos un símbolo',              cumplido: /[^A-Za-z0-9]/.test(p) },
  ]
})

/**
 * Devuelve la clase CSS del badge según el rol.
 * @param {string} rol - Nombre del rol
 * @returns {string}
 */
const roleBadgeClass = (rol) => ({
  'super_admin': 'bg-danger-soft text-danger-strong',
  'admin':       'bg-warning-soft text-warning-strong',
  'operador':    'bg-info-soft text-info-strong',
  'contador':    'bg-success-soft text-success-strong',
  'lectura':     'bg-surface-muted text-ink-muted',
}[rol] || 'bg-surface-muted text-ink-muted')

/**
 * Devuelve el nombre del icono correspondiente al rol.
 * @param {string} rol - Nombre del rol
 * @returns {string}
 */
const roleIcon = (rol) => ({
  'super_admin': 'key',
  'admin':       'shield-check',
  'operador':    'arrows-right-left',
  'contador':    'calculator',
  'lectura':     'eye',
}[rol] || 'users')

/**
 * Etiqueta legible de cada rol (capitalizada y completa).
 * @param {string} rol - Nombre del rol
 * @returns {string}
 */
const roleLabels = {
  'super_admin': 'Super Administrador',
  'admin':       'Administrador',
  'operador':    'Operador',
  'pagador':     'Pagador',
  'contador':    'Contador',
  'lectura':     'Lectura',
}
const roleLabel = (rol) => roleLabels[rol] || rol

/** Opciones de rol disponibles para crear/editar (sin super_admin) */
const rolOptions = ['admin', 'operador', 'contador', 'lectura']
  .map((value) => ({ value, label: roleLabels[value] }))

// ── Filtros del listado ────────────────────────────────────────────────
/** Búsqueda por nombre/correo */
const search = ref('')
/** Filtro de estado: todos | activos | inactivos */
const estadoFiltro = ref('todos')
/** Filtro por rol: '' = todos */
const rolFiltro = ref('')
/** Timer del debounce de búsqueda */
let debounce = null

/** Opciones del filtro de estado */
const estadoOpciones = [
  { value: 'todos', label: 'Todos' },
  { value: 'activos', label: 'Activos' },
  { value: 'inactivos', label: 'Inactivos' },
]

/** Opciones del filtro por rol (incluye pagador aunque no se cree desde la UI).
 * El filtro super_admin solo aparece para usuarios con ese rol. */
const rolOpciones = computed(() => {
  const roles = ['admin', 'operador', 'pagador', 'contador', 'lectura']
  if (auth.isSuperAdmin) roles.unshift('super_admin')
  return [
    { value: '', label: 'Todos los roles' },
    ...roles.map((value) => ({ value, label: roleLabels[value] })),
  ]
})

/**
 * Recarga la lista de usuarios aplicando los filtros activos.
 * @returns {void}
 */
function cargarUsuarios() {
  const params = {}
  const q = search.value.trim()
  if (q) params.q = q
  if (estadoFiltro.value !== 'todos') params.activo = estadoFiltro.value === 'activos'
  if (rolFiltro.value) params.rol = rolFiltro.value
  usuarios.fetchAll(params)
}

/** Busca con debounce de 400 ms al escribir */
function onSearch() {
  clearTimeout(debounce)
  debounce = setTimeout(cargarUsuarios, 400)
}

/** Cambia el filtro de estado y recarga */
function cambiarEstado(value) {
  estadoFiltro.value = value
  cargarUsuarios()
}

/** Cambia el filtro de rol y recarga */
function cambiarRol(value) {
  rolFiltro.value = value
  cargarUsuarios()
}

// ── Detalle del usuario y sesiones ─────────────────────────────────────
/** Controla la visibilidad del modal de detalle */
const mostrarDetalle = ref(false)
/** Usuario cuyo detalle se muestra */
const usuarioDetalle = ref(null)
/** Sesiones del usuario (login/logout) */
const sesiones = ref([])
/** Carga de sesiones en curso */
const sesionesLoading = ref(false)
/** Error al cargar sesiones */
const sesionesError = ref('')
/** URL de la foto de perfil ampliada en el lightbox (null = cerrado) */
const avatarZoom = ref(null)

/**
 * Abre el lightbox con la foto de perfil ampliada.
 * @param {string|null} url - URL del avatar a ampliar
 */
function zoomAvatar(url) {
  if (url) avatarZoom.value = url
}

/**
 * Abre el modal con el detalle del usuario y su historial de sesiones.
 * @param {Object} u - Usuario
 * @returns {Promise<void>}
 */
async function openDetalle(u) {
  usuarioDetalle.value = u
  mostrarDetalle.value = true
  sesiones.value = []
  sesionesError.value = ''
  sesionesLoading.value = true
  try {
    const { data } = await api.get(`/usuarios/${u.id}/sesiones`)
    sesiones.value = Array.isArray(data) ? data : (data.data || [])
  } catch (err) {
    sesionesError.value = parseError(err)
  } finally {
    sesionesLoading.value = false
  }
}

/**
 * Abre el formulario de edición desde el modal de detalle.
 * @returns {void}
 */
function editarDesdeDetalle() {
  openForm(usuarioDetalle.value)
  mostrarDetalle.value = false
}

/**
 * Etiqueta legible de navegador y SO de una sesión.
 * @param {Object} s - Sesión
 * @returns {string}
 */
function dispositivo(s) {
  const { navegador, so } = parseUserAgent(s.user_agent)
  const texto = `${navegador} · ${so}`
  return texto === '— · —' ? '—' : texto
}

/**
 * Duración de una sesión (entre inicio y cierre). 'Activa' si sigue vigente.
 * @param {Object} s - Sesión
 * @returns {string}
 */
function duracion(s) {
  if (s.tipo_cierre === 'vigente') return 'Activa'
  if (!s.login_at) return '—'
  const fin = s.logout_at || s.login_at
  const ms = new Date(fin) - new Date(s.login_at)
  if (isNaN(ms) || ms < 0) return '—'
  const mins = Math.round(ms / 60000)
  const h = Math.floor(mins / 60)
  const m = mins % 60
  return h === 0 ? `${m} min` : `${h}h ${m}m`
}

/**
 * Clase y texto del badge de cierre de sesión.
 * @param {Object} s - Sesión
 * @returns {{texto: string, clase: string}}
 */
function cierreBadge(s) {
  if (s.tipo_cierre === 'manual') return { texto: 'Manual', clase: 'bg-success-soft text-success-strong' }
  if (s.tipo_cierre === 'expirada') return { texto: 'Expirada', clase: 'bg-warning-soft text-warning-strong' }
  return { texto: 'Vigente', clase: 'bg-info-soft text-info-strong' }
}

/** Controla apertura del dropdown de rol */
const rolDropdownOpen = ref(false)
/** Referencia al contenedor del dropdown para cerrar al hacer clic fuera */
const rolDropdownRef = ref(null)
/** Controla apertura del dropdown de titular */
const titularDropdownOpen = ref(false)
/** Referencia al contenedor del dropdown de titular */
const titularDropdownRef = ref(null)

/**
 * Etiqueta legible de un titular por su id.
 * @param {number|null} id - ID del titular
 * @returns {string}
 */
const titularLabel = (id) => {
  const t = titulares.value.find((x) => x.id === id)
  return t ? `${t.alias} — ${t.nombre}` : ''
}

/** Selecciona un titular y cierra el dropdown */
function selectTitular(id) {
  form.titular_id = id
  titularDropdownOpen.value = false
}

/** Cierra los dropdowns si el clic ocurre fuera de sus contenedores */
function onDocClick(e) {
  const rolEl = rolDropdownRef.value
  const titEl = titularDropdownRef.value
  if (rolEl && !rolEl.contains(e.target)) rolDropdownOpen.value = false
  if (titEl && !titEl.contains(e.target)) titularDropdownOpen.value = false
}

/** Selecciona un rol y cierra el dropdown */
function selectRol(value) {
  form.rol = value
  rolDropdownOpen.value = false
}

/**
 * Indica si un usuario tiene el rol super_admin (no editable desde esta vista).
 * @param {Object} u - Usuario
 * @returns {boolean}
 */
const isSuperAdmin = (u) => Array.isArray(u?.roles) && u.roles.includes('super_admin')

/**
 * Abre el modal en modo creación o edición.
 * @param {Object|null} u - Usuario a editar (null para creación)
 */
function openForm(u = null) {
  rolDropdownOpen.value = false
  titularDropdownOpen.value = false
  nameError.value = ''
  nameOk.value = ''
  emailError.value = ''
  emailOk.value = ''
  if (u) {
    editing.value = true
    editId.value = u.id
    Object.assign(form, {
      name: u.name || '',
      email: u.email || '',
      password: '',
      rol: u.roles?.[0] || '',
      titular_id: u.titular_id ?? null,
      activo: u.activo ?? true,
    })
  } else {
    editing.value = false
    editId.value = null
    Object.assign(form, { name: '', email: '', password: '', rol: '', titular_id: null, activo: true })
  }
  formError.value = ''
  limpiarAvatar()
  showForm.value = true
}

/** Cierra el modal */
function closeForm() {
  showForm.value = false
}

/**
 * Alterna el estado activo/inactivo de un usuario.
 * Actualiza la fila en su lugar (sin recargar ni refetchear la lista).
 * @param {Object} u - Usuario a togglear
 * @returns {Promise<void>}
 */
async function handleToggle(u) {
  try {
    await usuarios.toggleActivo(u)
  } catch (err) {
    alert(parseError(err))
  }
}

/**
 * Verifica contra la API si un nombre de usuario o correo ya está en uso.
 * Si lo está, muestra el error correspondiente junto al campo.
 * @param {'name'|'email'} campo - Campo a verificar
 * @returns {Promise<boolean>} true si está disponible
 */
async function validarDisponible(campo) {
  const valor = (campo === 'name' ? form.name.trim() : form.email.trim()).toLowerCase()
  if (!valor) return true
  if (campo === 'email' && !esEmailValido(valor)) {
    emailError.value = 'Ingresa un correo electrónico válido.'
    emailOk.value = ''
    return false
  }
  try {
    const { data } = await api.get('/usuarios/disponible', {
      params: { campo, valor, exclude_id: editing.value ? editId.value : undefined },
    })
    if (data.disponible) {
      if (campo === 'name') {
        nameError.value = ''
        nameOk.value = 'Nombre de usuario disponible.'
      } else {
        emailError.value = ''
        emailOk.value = 'Correo disponible.'
      }
    } else if (campo === 'name') {
      nameError.value = 'Este nombre de usuario ya está en uso.'
      nameOk.value = ''
    } else {
      emailError.value = 'Este correo ya está en uso.'
      emailOk.value = ''
    }
    return !!data.disponible
  } catch {
    return true
  }
}

/**
 * Envía el formulario para crear o actualizar un usuario.
 * @returns {Promise<void>}
 */
async function submit() {
  formError.value = ''
  if (!form.rol) {
    formError.value = 'Selecciona un rol para el usuario.'
    return
  }
  if (!esEmailValido(form.email)) {
    emailError.value = 'Ingresa un correo electrónico válido.'
    emailOk.value = ''
    return
  }
  const [nameOk, emailOk] = await Promise.all([validarDisponible('name'), validarDisponible('email')])
  if (!nameOk || !emailOk) return
  saving.value = true
  try {
    let body
    if (avatarFile.value) {
      body = new FormData()
      body.append('name', form.name.trim().toLowerCase())
      body.append('email', form.email.trim().toLowerCase())
      body.append('rol', form.rol)
      if (form.titular_id) body.append('titular_id', form.titular_id)
      body.append('activo', form.activo ? '1' : '0')
      if (form.password) body.append('password', form.password)
      body.append('avatar', avatarFile.value)
    } else {
      body = {
        name: form.name.trim().toLowerCase(),
        email: form.email.trim().toLowerCase(),
        rol: form.rol,
        titular_id: form.titular_id || null,
        activo: form.activo,
      }
      if (form.password) body.password = form.password
    }
    const res = editing.value
      ? await usuarios.update(editId.value, body)
      : await usuarios.create(body)
    if (res?.advertencias?.length) {
      mostrarToast(res.advertencias.join(' '))
    }
    closeForm()
    cargarUsuarios()
  } catch (err) {
    formError.value = parseError(err)
  } finally {
    saving.value = false
  }
}

/** Carga usuarios y lista de titulares al montar */
onMounted(async () => {
  cargarUsuarios()
  try {
    const { data } = await api.get('/titulares')
    titulares.value = Array.isArray(data) ? data : (data.data || [])
  } catch {
    titulares.value = []
  }
  document.addEventListener('click', onDocClick)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocClick)
  clearTimeout(toastTimer)
})
</script>
