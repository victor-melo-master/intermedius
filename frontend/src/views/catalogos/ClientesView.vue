<template>
  <div class="space-y-4">
    <AppPageHeader
      title="Clientes"
      :action-label="auth.canWrite ? 'Nuevo cliente' : ''"
      @action="openCreate"
    />

    <div class="relative">
      <input v-model="search" @input="debounceSearch" placeholder="Buscar por nombre o alias..."
        class="w-full pl-10 pr-4 py-2.5 bg-white dark:bg-surface-muted border border-edge-strong rounded-xl focus:ring-2 focus:ring-gold outline-none" />
      <Iconoir name="magnifying-glass" class="absolute left-3 top-2.5 w-5 h-5 text-ink-muted" />
      <button v-if="search" @click="search = ''; clientes.fetchAll()" class="absolute right-3 top-2.5 text-ink-muted hover:text-ink-muted"><Iconoir name="x-mark" class="w-5 h-5" /></button>
    </div>

    <AppLoadingSpinner v-if="clientes.loading" />
    <div class="flex gap-2" v-if="auth.canConfig">
      <button @click="mostrarPapelera = false; cargarLista()" class="text-sm px-3 py-1.5 rounded-lg transition active:scale-[0.98]" :class="mostrarPapelera ? 'bg-surface-muted text-ink-muted' : 'bg-gold text-white'">Activos</button>
      <button @click="mostrarPapelera = true; cargarLista()" class="text-sm px-3 py-1.5 rounded-lg transition active:scale-[0.98] inline-flex items-center gap-1" :class="mostrarPapelera ? 'bg-danger text-white dark:text-navy' : 'bg-surface-muted text-ink-muted'"><Iconoir name="trash" class="w-4 h-4" /> Papelera</button>
    </div>

    <AppLoadingSpinner v-if="clientes.loading" />
    <AppErrorState v-else-if="clientes.error" :message="clientes.error" @retry="cargarLista()" />
    <template v-else-if="clientes.list.length === 0">
      <div class="text-center py-16">
        <Iconoir name="users" class="w-12 h-12 mx-auto mb-4 text-ink-muted" />
        <p class="text-ink-muted">{{ search ? 'Sin resultados' : (mostrarPapelera ? 'No hay clientes eliminados' : 'No hay clientes') }}</p>
      </div>
    </template>
    <div v-else class="space-y-2">
      <div v-for="c in clientes.list" :key="c.id" @click="openDetail(c)" class="bg-surface border border-edge rounded-xl p-4 flex items-center gap-3 cursor-pointer hover:shadow-md transition" :class="c.deleted_at ? 'opacity-70 border-danger-edge' : ''">
        <div class="w-10 h-10 rounded-full bg-gold-soft flex items-center justify-center text-gold-dark font-bold text-sm overflow-hidden">
          <img v-if="avatarUrl(c)" :src="avatarUrl(c)" alt="" class="w-full h-full object-cover" />
          <template v-else>{{ c.nombre.charAt(0).toUpperCase() }}</template>
        </div>
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-sm truncate">{{ c.nombre }}</p>
          <p v-if="c.alias" class="text-sm text-ink-muted truncate">{{ c.alias }}</p>
          <p v-if="c.telefono" class="text-sm text-ink-muted">{{ c.telefono }}</p>
        </div>
        <div class="text-right shrink-0">
          <p class="text-sm font-bold" :class="(c.saldo_cache_usd || 0) >= 0 ? 'text-success' : 'text-danger'">${{ formatMoney(c.saldo_cache_usd) }}</p>
          <span v-if="c.deleted_at" class="text-[10px] bg-danger-soft text-danger px-2 py-0.5 rounded-full">Eliminado</span>
          <span v-else-if="!c.activo" class="text-[10px] bg-danger-soft text-danger px-2 py-0.5 rounded-full">Inactivo</span>
          <div class="mt-1 flex gap-2 justify-end">
            <button v-if="c.deleted_at && (auth.canConfig)" @click.stop="restaurarCliente(c)" class="text-xs text-success hover:text-success-strong underline inline-flex items-center gap-1"><Iconoir name="arrow-uturn-left" class="w-3 h-3" /> Recuperar</button>
            <button v-else-if="auth.canWrite" @click.stop="openEdit(c)" class="text-xs text-gold-dark hover:text-gold-dark underline inline-flex items-center gap-1"><Iconoir name="pencil-square" class="w-3 h-3" /> Editar</button>
          </div>
        </div>
      </div>
    </div>

    <AppFormModal v-model="showForm" :title="editingId ? 'Editar cliente' : 'Nuevo cliente'">
      <form @submit.prevent="submit" class="space-y-3">
        <input v-model="form.nombre" required placeholder="Nombre *" class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />
        <input v-model="form.alias" placeholder="Alias" class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />
        <input v-model="form.telefono" placeholder="Teléfono" class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />
        <input v-model="form.email" type="email" placeholder="Email" class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />
        <textarea v-model="form.notas" rows="2" placeholder="Notas" class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none resize-none"></textarea>
        <AppErrorState v-if="formError" :message="formError" :retry="false" />
      </form>
      <template #footer>
        <button @click="submit" :disabled="saving" class="w-full bg-gold text-white font-semibold py-2.5 rounded-lg hover:bg-gold-dark disabled:opacity-50 transition active:scale-[0.98] flex items-center justify-center gap-2">
          <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          {{ saving ? 'Guardando...' : (editingId ? 'Guardar cambios' : 'Crear cliente') }}
        </button>
      </template>
    </AppFormModal>

    <!-- Modal detalle del cliente -->
    <div v-if="showDetail" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center" @click.self="showDetail = false">
      <div class="absolute inset-0 bg-black/40"></div>
      <div class="bg-surface rounded-t-2xl sm:rounded-2xl w-full max-w-2xl p-6 relative z-10 max-h-[90vh] overflow-y-auto flex flex-col">
        <div class="flex items-start justify-between gap-3 mb-5">
          <div class="flex items-center gap-3 min-w-0">
            <div class="relative shrink-0">
              <div class="w-12 h-12 rounded-full bg-gold-soft flex items-center justify-center text-gold-dark font-bold text-lg overflow-hidden border-2 border-edge-strong">
                <img v-if="clienteAvatarUrl" :src="clienteAvatarUrl" alt="" class="w-full h-full object-cover" />
                <template v-else>{{ (detailCliente?.nombre || '?').charAt(0).toUpperCase() }}</template>
              </div>
              <label v-if="auth.canWrite && !detailCliente?.deleted_at" class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-gold text-white flex items-center justify-center cursor-pointer hover:bg-gold-dark shadow transition" title="Cambiar foto">
                <Iconoir name="camera" class="w-3 h-3" />
                <input type="file" accept="image/jpeg,image/png,image/gif,image/webp,image/bmp" class="hidden" @change="onClienteAvatarSelected" :disabled="subiendoAvatar" />
              </label>
            </div>
            <div class="min-w-0">
              <h3 class="font-bold text-lg truncate">{{ detailCliente?.nombre }}</h3>
              <p class="text-sm text-ink-muted truncate">{{ (detailCliente?.alias || detailCliente?.telefono || 'Cliente') }}</p>
            </div>
          </div>
          <div class="flex items-center gap-2 shrink-0">
            <span v-if="!detailCliente?.deleted_at" class="text-sm font-semibold px-2.5 py-1 rounded-lg bg-surface-soft border border-edge-strong" :class="clienteSaldo >= 0 ? 'text-success' : 'text-danger'">${{ formatMoney(detailCliente?.saldo_cache_usd) }}</span>
            <button v-if="detailCliente?.deleted_at && (auth.canConfig)" @click="restaurarCliente(detailCliente)" class="text-xs bg-success hover:bg-success-strong text-white dark:text-navy px-2 py-1 rounded-lg inline-flex items-center gap-1"><Iconoir name="arrow-uturn-left" class="w-3 h-3" /> Recuperar</button>
            <button @click="showDetail = false" class="text-ink-muted hover:text-ink-muted"><Iconoir name="x-mark" class="w-5 h-5" /></button>
          </div>
        </div>

        <p v-if="subiendoAvatar" class="mb-2 text-xs text-ink-muted">Subiendo foto...</p>
        <p v-if="avatarError" class="mb-2 text-xs text-danger">{{ avatarError }}</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-5">
          <div class="bg-surface-soft border border-edge rounded-lg p-3">
            <p class="text-xs text-ink-muted mb-0.5">Alias</p>
            <p class="text-sm font-medium text-ink truncate">{{ detailCliente?.alias || '—' }}</p>
          </div>
          <div class="bg-surface-soft border border-edge rounded-lg p-3">
            <p class="text-xs text-ink-muted mb-0.5">Teléfono</p>
            <p class="text-sm font-medium text-ink truncate">{{ detailCliente?.telefono || '—' }}</p>
          </div>
          <div class="bg-surface-soft border border-edge rounded-lg p-3 sm:col-span-2">
            <p class="text-xs text-ink-muted mb-0.5">Email</p>
            <p class="text-sm font-medium text-ink truncate">{{ detailCliente?.email || '—' }}</p>
          </div>
          <div v-if="detailCliente?.notas" class="bg-surface-soft border border-edge rounded-lg p-3 sm:col-span-2">
            <p class="text-xs text-ink-muted mb-0.5">Notas</p>
            <p class="text-sm font-medium text-ink whitespace-pre-wrap">{{ detailCliente.notas }}</p>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 border-t-2 border-edge-strong pt-4 mb-2">
          <button v-if="!detailCliente?.deleted_at && (auth.canWrite)" @click="openEdit(detailCliente)" class="text-xs bg-gold text-white px-2 py-1 rounded-lg hover:bg-gold-dark inline-flex items-center gap-1"><Iconoir name="pencil-square" class="w-3 h-3" /> Editar</button>
          <button v-if="auth.canWrite && detailCliente?.avatar_path && !detailCliente?.deleted_at" @click="quitarAvatar" :disabled="subiendoAvatar" class="text-xs text-ink-muted hover:text-danger-strong inline-flex items-center gap-1 disabled:opacity-50">
            <Iconoir name="trash" class="w-3 h-3" /> Quitar foto
          </button>
          <button v-if="!detailCliente?.deleted_at && (auth.canWrite)" @click="eliminarCliente(detailCliente)" class="text-xs bg-danger text-white dark:text-navy px-2 py-1 rounded-lg hover:bg-danger-strong inline-flex items-center gap-1"><Iconoir name="trash" class="w-3 h-3" /> Eliminar cliente</button>
        </div>

        <!-- Cuentas bancarias -->
        <AccordionSection title="Cuentas bancarias" :count="clienteCuentas.length" :default-open="false" :key="'cuentas-' + detailKey">
          <template #header-actions>
            <button v-if="auth.canWrite" @click="openCuentaForm" class="text-xs bg-gold text-white px-2 py-1 rounded-lg hover:bg-gold-dark">+ Agregar cuenta</button>
          </template>

          <AppLoadingSpinner v-if="loadingCuentas" />
          <template v-else-if="clienteCuentas.length === 0">
            <div class="text-center py-8">
              <Iconoir name="building-library" class="w-10 h-10 mx-auto mb-3 text-ink-muted" />
              <p class="text-ink-muted">No hay cuentas registradas.</p>
            </div>
          </template>
          <div v-else class="space-y-3 pb-4">
            <div v-for="cu in clienteCuentas" :key="cu.id" class="bg-surface-soft border border-edge rounded-lg p-3">
              <p class="font-medium text-sm">{{ cu.alias }}</p>
              <p class="text-sm text-ink-muted">{{ cu.banco?.nombre }} — {{ cu.moneda?.codigo }}</p>
              <p v-if="cu.numero_cuenta" class="text-sm text-ink-muted">{{ cu.numero_cuenta }}</p>
            </div>
          </div>
        </AccordionSection>

        <!-- Documentos -->
        <AccordionSection title="Documentos" :count="documentosCargados ? documentos.length : null" :default-open="false" :key="'documentos-' + detailKey" @toggle-open="onDocumentosToggle">
          <template #header-actions>
            <label class="text-xs bg-gold text-white px-2 py-1 rounded-lg hover:bg-gold-dark cursor-pointer">
              + Subir documento
              <input type="file" accept="image/*,.pdf" class="hidden" @change="subirDocumento" />
            </label>
          </template>
          <AppLoadingSpinner v-if="loadingDocumentos" />
          <template v-else-if="documentos.length === 0">
            <div class="text-center py-8">
              <Iconoir name="document-text" class="w-10 h-10 mx-auto mb-3 text-ink-muted" />
              <p class="text-ink-muted">No hay documentos.</p>
            </div>
          </template>
          <div v-else class="space-y-3 pb-4">
            <div v-for="doc in documentos" :key="doc.id" class="bg-surface-soft border border-edge rounded-lg p-3 flex items-center justify-between">
              <div class="flex items-center gap-2 cursor-pointer" @click="abrirDocumento(doc)">
                <span class="text-lg inline-flex items-center">
                  <Iconoir v-if="doc.tipo === 'cedula'" name="identification" class="w-5 h-5" />
                  <Iconoir v-else-if="doc.tipo === 'rif'" name="clipboard" class="w-5 h-5" />
                  <Iconoir v-else name="document-text" class="w-5 h-5" />
                </span>
                <div>
                  <p class="font-medium text-sm truncate max-w-[200px] hover:text-gold-dark underline">{{ doc.nombre_archivo }}</p>
                  <p class="text-sm text-ink-muted">{{ formatTamano(doc.tamano) }} · {{ doc.tipo }}</p>
                </div>
              </div>
              <div class="flex flex-col items-end gap-1">
                <a :href="documentoDownloadUrlById(doc)" class="text-xs text-gold-dark hover:text-gold-dark">⬇ Descargar</a>
                <button @click="eliminarDocumento(doc)" class="text-xs text-danger hover:text-danger-strong inline-flex items-center gap-1"><Iconoir name="trash" class="w-3 h-3" /> Eliminar</button>
              </div>
            </div>
          </div>
        </AccordionSection>

        <!-- Historial de transacciones -->
        <AccordionSection title="Historial de transacciones" :default-open="false" :key="'historial-' + detailKey" @toggle-open="onHistorialToggle">
          <template #title-icon>
            <IntermediusSymbol :size="18" class="text-navy dark:text-gold" />
          </template>
          <template #header-actions>
            <button @click="exportarPDF" :disabled="exportando" class="text-xs bg-danger text-white dark:text-navy px-2.5 py-1 rounded-lg hover:bg-danger-strong inline-flex items-center gap-1.5 disabled:opacity-50 transition">
              <Iconoir v-if="!exportando" name="document-text" class="w-4 h-4" />
              {{ exportando ? 'Generando...' : 'PDF' }}
            </button>
          </template>

          <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mb-3 p-3 bg-surface-soft border border-edge rounded-lg">
            <div>
              <label class="text-xs font-semibold text-ink-muted uppercase tracking-wider mb-1 block">Desde</label>
              <input v-model="historialFiltros.fecha_desde" type="date" class="w-full px-2 py-1.5 text-xs border border-edge-strong rounded-md bg-surface focus:ring-2 focus:ring-gold outline-none" />
            </div>
            <div>
              <label class="text-xs font-semibold text-ink-muted uppercase tracking-wider mb-1 block">Hasta</label>
              <input v-model="historialFiltros.fecha_hasta" type="date" class="w-full px-2 py-1.5 text-xs border border-edge-strong rounded-md bg-surface focus:ring-2 focus:ring-gold outline-none" />
            </div>
            <div class="col-span-2 sm:col-span-1">
              <label class="text-xs font-semibold text-ink-muted uppercase tracking-wider mb-1 block">Tipo</label>
              <select v-model="historialFiltros.tipo_codigo" class="w-full px-2 py-1.5 text-xs border border-edge-strong rounded-md bg-surface focus:ring-2 focus:ring-gold outline-none">
                <option value="">Todos</option>
                <option value="compra_usd">Compra USD</option>
                <option value="venta_usd">Venta USD</option>
                <option value="intermediada">Intermediada</option>
              </select>
            </div>
          </div>
          <button @click="cargarHistorial(1)" :disabled="loadingHistorial" class="w-full text-xs font-semibold bg-gold text-white py-2 rounded-md hover:bg-gold-dark mb-4 transition active:scale-[0.99] disabled:opacity-50">
            {{ loadingHistorial ? 'Cargando...' : 'Buscar' }}
          </button>

          <div v-if="historial.length > 0" class="overflow-x-auto pb-4">
            <table class="w-full text-xs">
              <thead>
                <tr class="text-left text-white bg-navy dark:bg-navy-dark">
                  <th class="py-2 px-3 font-semibold uppercase tracking-wider rounded-l-md">ID</th>
                  <th class="py-2 px-3 font-semibold uppercase tracking-wider">Fecha</th>
                  <th class="py-2 px-3 font-semibold uppercase tracking-wider">Tipo</th>
                  <th class="py-2 px-3 font-semibold uppercase tracking-wider text-right">USD</th>
                  <th class="py-2 px-3 font-semibold uppercase tracking-wider text-right">VES</th>
                  <th class="py-2 px-3 font-semibold uppercase tracking-wider text-right rounded-r-md">Tasa</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="op in historial" :key="op.id" class="border-b border-edge hover:bg-surface-muted/60 transition-colors">
                  <td class="py-2 px-3 text-ink-muted tabular-nums">#{{ op.id }}</td>
                  <td class="py-2 px-3 tabular-nums">{{ formatFecha(op.fecha) }}</td>
                  <td class="py-2 px-3">
                    <span :class="clasesBadgeTipo(op)" class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold whitespace-nowrap">{{ op.tipo_operacion?.nombre || '—' }}</span>
                  </td>
                  <td class="py-2 px-3 text-right font-semibold text-gold dark:text-gold-dark tabular-nums">{{ formatMonto(op, 'USD') }}</td>
                  <td class="py-2 px-3 text-right text-navy dark:text-ink-muted tabular-nums">{{ formatMonto(op, 'VES') }}</td>
                  <td class="py-2 px-3 text-right text-ink-muted tabular-nums">{{ op.tasa_aplicada ? parseFloat(op.tasa_aplicada).toFixed(2) : '—' }}</td>
                </tr>
              </tbody>
            </table>
            <div v-if="historialPaginacion.last_page > 1" class="flex justify-between items-center mt-3 text-xs">
              <button @click="cargarHistorial(historialPaginacion.current_page - 1)" :disabled="!historialPaginacion.prev_page_url" class="px-2.5 py-1 rounded-md border border-edge-strong text-gold-dark dark:text-gold hover:bg-gold-soft disabled:text-ink-muted disabled:hover:bg-transparent disabled:border-edge disabled:cursor-not-allowed transition">Anterior</button>
              <span class="text-ink-muted">Pág <span class="font-semibold text-navy dark:text-ink">{{ historialPaginacion.current_page }}</span> / {{ historialPaginacion.last_page }}</span>
              <button @click="cargarHistorial(historialPaginacion.current_page + 1)" :disabled="!historialPaginacion.next_page_url" class="px-2.5 py-1 rounded-md border border-edge-strong text-gold-dark dark:text-gold hover:bg-gold-soft disabled:text-ink-muted disabled:hover:bg-transparent disabled:border-edge disabled:cursor-not-allowed transition">Siguiente</button>
            </div>
          </div>
          <div v-else-if="!loadingHistorial && historialCargado" class="flex flex-col items-center py-8">
            <IntermediusSymbol :size="44" class="text-[#93A1A5] mb-3" />
            <p class="text-sm text-ink-muted">Sin operaciones.</p>
          </div>
        </AccordionSection>
      </div>
    </div>

    <AppFormModal v-model="showCuentaForm" :title="'Agregar cuenta para ' + (detailCliente?.nombre || '')">
      <form @submit.prevent="submitCuenta" class="space-y-3">
        <div>
          <label class="text-sm text-ink-muted mb-1 block">Tipo de cuenta *</label>
          <select v-model="cuentaForm.tipo" required class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none">
            <option value="">Seleccionar tipo</option>
            <option value="banco">Banco</option>
            <option value="plataforma">Plataforma</option>
            <option value="cash">Cash</option>
            <option value="zelle">Zelle</option>
            <option value="wallet">Wallet</option>
            <option value="efectivo">Efectivo</option>
            <option value="otro">Otro</option>
          </select>
        </div>

        <div v-if="cuentaForm.tipo !== 'efectivo'">
          <label class="text-sm text-ink-muted mb-1 block">Banco</label>
          <select v-model="cuentaForm.banco_id" required class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none">
            <option value="">Seleccionar banco</option>
            <option v-for="b in bancos.list" :key="b.id" :value="b.id">{{ b.nombre }} ({{ b.codigo }})</option>
          </select>
        </div>

        <div>
          <label class="text-sm text-ink-muted mb-1 block">Moneda</label>
          <select v-model="cuentaForm.moneda_id" required class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none">
            <option value="">Seleccionar moneda</option>
            <option v-for="m in tasas.monedas" :key="m.id" :value="m.id">{{ m.codigo }} — {{ m.nombre }}</option>
          </select>
        </div>

        <input v-model="cuentaForm.alias" required placeholder="Alias * (ej: Banesco USD)" class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />

        <input v-if="cuentaForm.tipo !== 'efectivo'" v-model="cuentaForm.numero_cuenta" placeholder="Número de cuenta (opcional)" class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />
        <textarea v-model="cuentaForm.notas" rows="2" placeholder="Notas (opcional)" class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none resize-none"></textarea>
        <label class="flex items-center gap-2 text-sm text-ink-muted">
          <input v-model="cuentaForm.activa" type="checkbox" class="w-4 h-4 rounded border-edge-strong text-gold-dark focus:ring-gold" />
          Activa
        </label>
        <AppErrorState v-if="cuentaFormError" :message="cuentaFormError" :retry="false" />
      </form>
      <template #footer>
        <button @click="submitCuenta" :disabled="savingCuenta" class="w-full bg-gold text-white font-semibold py-2.5 rounded-lg hover:bg-gold-dark disabled:opacity-50 transition active:scale-[0.98] flex items-center justify-center gap-2">
          <span v-if="savingCuenta" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          {{ savingCuenta ? 'Guardando...' : 'Crear cuenta' }}
        </button>
      </template>
    </AppFormModal>

    <!-- Modal de previsualización de documento -->
    <!-- Modal de previsualización de documento -->
<div v-if="showDocumentoModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDocumentoModal = false">
  <div class="absolute inset-0 bg-black/60"></div>
  <div class="bg-surface rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6 relative z-10">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-bold text-lg">{{ documentoPreview?.nombre_archivo }}</h3>
      <button @click="showDocumentoModal = false" class="text-ink-muted hover:text-ink-muted"><Iconoir name="x-mark" class="w-5 h-5" /></button>
    </div>
    <div class="flex flex-col items-center justify-center">
      <!-- Spinner mientras carga la URL -->
      <div v-if="loadingPreviewUrl" class="text-center py-8">
        <div class="w-8 h-8 border-2 border-gold border-t-transparent rounded-full animate-spin mx-auto"></div>
        <p class="text-ink-muted text-sm mt-2">Cargando previsualización...</p>
      </div>
      <!-- Imagen -->
      <img v-else-if="esImagen(documentoPreview) && documentoPreviewUrl"
           :src="documentoPreviewUrl"
           alt="Documento"
           class="max-w-full max-h-[70vh] rounded-lg shadow-md" />
      <!-- No imagen -->
      <div v-else-if="!loadingPreviewUrl" class="text-center py-8">
        <Iconoir name="document-text" class="w-12 h-12 mx-auto mb-4 text-ink-muted" />
        <p class="text-ink-muted">No se puede previsualizar este tipo de archivo.</p>
      </div>
      <!-- Botón de descarga -->
      <a v-if="documentoDownloadUrl"
         :href="documentoDownloadUrl"
         class="mt-4 inline-block px-4 py-2 bg-gold text-white rounded-lg text-sm hover:bg-gold-dark">
        Descargar archivo
      </a>
    </div>
  </div>
</div>
  </div>
</template>

<script setup>
/**
 * ClientesView — CRUD de clientes con soft-delete y papelera.
 * Permite crear, editar, eliminar (archivar) y restaurar clientes.
 * Incluye detalle con cuentas bancarias asociadas, historial de transacciones
 * con filtros por fecha/tipo, paginación, exportación a PDF, y gestión de documentos (subir, listar, eliminar, previsualizar).
 */
import { ref, reactive, computed, onMounted } from 'vue'
import { useClientesStore } from '../../stores/clientes.js'
import { useAuthStore } from '../../stores/auth.js'
import { useBancosStore } from '../../stores/bancos.js'
import { useTasasStore } from '../../stores/tasas.js'
import { useFormatting } from '@/composables/useFormatting'
import { useApiError } from '@/composables/useApiError'
import api from '../../api/axios.js'
import AppPageHeader from '../../components/common/AppPageHeader.vue'
import AppLoadingSpinner from '../../components/common/AppLoadingSpinner.vue'
import AppErrorState from '../../components/common/AppErrorState.vue'
import AppEmptyState from '../../components/common/AppEmptyState.vue'
import AppFormModal from '@/components/common/AppFormModal.vue'
import AccordionSection from '@/components/common/AccordionSection.vue'
import Iconoir from '../../components/common/Iconoir.vue'
import IntermediusSymbol from '@/components/common/IntermediusSymbol.vue'

/** Store de clientes */
const clientes = useClientesStore()
/** Store de autenticación (para permisos) */
const auth = useAuthStore()
/** Store de bancos */
const bancos = useBancosStore()
/** Store de tasas (para monedas) */
const tasas = useTasasStore()
const { formatTamano, formatMoney } = useFormatting()
const { parseError } = useApiError()

/** Término de búsqueda por nombre o alias */
const search = ref('')
/** Controla visibilidad del modal crear/editar cliente */
const showForm = ref(false)
/** Indica si se está guardando el formulario de cliente */
const saving = ref(false)
/** Mensaje de error del formulario de cliente */
const formError = ref('')
/** ID del cliente en edición (null si es creación) */
const editingId = ref(null)
/** Alterna entre vista de activos y papelera */
const mostrarPapelera = ref(false)
/** Timeout para debounce de búsqueda */
let debounce = null

/** Datos del formulario de cliente */
const form = reactive({ nombre: '', alias: '', telefono: '', email: '', notas: '' })

/** Controla visibilidad del modal de detalle */
const showDetail = ref(false)
/** Cliente actualmente visible en el detalle */
const detailCliente = ref(null)
/** Clave que cambia en cada apertura del detalle para reiniciar los acordeones */
const detailKey = ref(0)
/** Cuentas bancarias del cliente mostrado */
const clienteCuentas = ref([])
/** Indica carga de cuentas del cliente */
const loadingCuentas = ref(false)

/** Saldo del cliente en USD (numérico) */
const clienteSaldo = computed(() => parseFloat(detailCliente.value?.saldo_cache_usd || 0))

// Avatar del cliente
/** Indica si se está subiendo/quitanendo la foto */
const subiendoAvatar = ref(false)
/** Error de validación de la foto del cliente */
const avatarError = ref('')
/** Tipos de imagen aceptados para el avatar (se convierten a WebP en el backend) */
const avatarTiposAceptados = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp']

/**
 * URL autenticada del avatar del cliente (imagen WebP servida por la API).
 * @param {Object} c - Cliente con { id, avatar_path }
 * @returns {string|null}
 */
function avatarUrl(c) {
  if (!c?.avatar_path) return null
  const token = localStorage.getItem('token')
  return `${import.meta.env.VITE_API_URL}/clientes/${c.id}/avatar?token=${token}`
}

/** URL del avatar del cliente visible en el detalle */
const clienteAvatarUrl = computed(() => avatarUrl(detailCliente.value))

/**
 * Maneja la selección de una foto de cliente: valida tipo y tamaño y sube automáticamente.
 * @param {Event} evt - Evento change del input file
 */
async function onClienteAvatarSelected(evt) {
  const file = evt.target.files?.[0]
  avatarError.value = ''
  evt.target.value = ''
  if (!file || !detailCliente.value) return
  if (!avatarTiposAceptados.includes(file.type)) {
    avatarError.value = 'Formato no permitido. Usa JPG, PNG, GIF, WebP o BMP.'
    return
  }
  if (file.size > 2 * 1024 * 1024) {
    avatarError.value = 'La imagen supera los 2MB. Elige una más pequeña.'
    return
  }

  subiendoAvatar.value = true
  try {
    const formData = new FormData()
    formData.append('avatar', file)
    const { data } = await api.put(`/clientes/${detailCliente.value.id}`, formData)
    const avatarPath = data?.avatar_path || data?.data?.avatar_path
    if (avatarPath) {
      detailCliente.value.avatar_path = avatarPath
      actualizarClienteEnLista(detailCliente.value.id, { avatar_path: avatarPath })
    }
  } catch (err) {
    avatarError.value = parseError(err)
  } finally {
    subiendoAvatar.value = false
  }
}

/** Actualiza un cliente dentro de la lista actual (sin recargar). */
function actualizarClienteEnLista(id, patch) {
  const idx = clientes.list.findIndex(c => c.id === id)
  if (idx !== -1) Object.assign(clientes.list[idx], patch)
}

/** Elimina la foto del cliente actual. */
async function quitarAvatar() {
  if (!detailCliente.value) return
  if (!confirm(`¿Quitar la foto de "${detailCliente.value.nombre}"?`)) return
  avatarError.value = ''
  subiendoAvatar.value = true
  try {
    const { data } = await api.delete(`/clientes/${detailCliente.value.id}/avatar`)
    detailCliente.value.avatar_path = null
    actualizarClienteEnLista(detailCliente.value.id, { avatar_path: null })
  } catch (err) {
    avatarError.value = parseError(err)
  } finally {
    subiendoAvatar.value = false
  }
}

/** Controla visibilidad del modal de crear cuenta para cliente */
const showCuentaForm = ref(false)
/** Indica si se está guardando la cuenta */
const savingCuenta = ref(false)
/** Mensaje de error del formulario de cuenta */
const cuentaFormError = ref('')
/** Datos del formulario de creación de cuenta */
const cuentaForm = reactive({
  cliente_id: '',
  banco_id: '',
  moneda_id: '',
  alias: '',
  tipo: '',
  numero_cuenta: '',
  notas: '',
  activa: true,
})

// Historial
const historial = ref([])
const historialPaginacion = ref({})
const historialCargado = ref(false)
const loadingHistorial = ref(false)
const exportando = ref(false)
const historialFiltros = reactive({
  fecha_desde: '',
  fecha_hasta: '',
  tipo_codigo: '',
})

// Documentos
const documentos = ref([])
const loadingDocumentos = ref(false)
const documentosCargados = ref(false)
const showDocumentoModal = ref(false)
const documentoPreview = ref(null)
// Agrega estas dos líneas junto a las otras variables de documentos
const documentoPreviewUrl = ref('')
const loadingPreviewUrl = ref(false)

const documentoDownloadUrl = computed(() => {
  if (!documentoPreview.value) return null
  const token = localStorage.getItem('token')
  return `${import.meta.env.VITE_API_URL}/documentos/${documentoPreview.value.id}/download?token=${token}`
})

function abrirDocumento(doc) {
  documentoPreview.value = doc
  const token = localStorage.getItem('token')
  documentoPreviewUrl.value = `${import.meta.env.VITE_API_URL}/documentos/${doc.id}/preview?token=${token}`
  showDocumentoModal.value = true
  loadingPreviewUrl.value = false
}

function documentoDownloadUrlById(doc) {
  const token = localStorage.getItem('token')
  return `${import.meta.env.VITE_API_URL}/documentos/${doc.id}/download?token=${token}`
}

/**
 * Carga la lista de clientes según la pestalla activa (activos o papelera).
 * Aplica el filtro de búsqueda si existe.
 */
function cargarLista() {
  if (mostrarPapelera.value) {
    clientes.fetchTrashed(search.value)
  } else {
    clientes.fetchAll(search.value)
  }
}

/** Ejecuta la búsqueda con debounce de 400ms */
function debounceSearch() {
  clearTimeout(debounce)
  debounce = setTimeout(() => cargarLista(), 400)
}

/** Abre el modal en modo creación */
function openCreate() {
  editingId.value = null
  Object.assign(form, { nombre: '', alias: '', telefono: '', email: '', notas: '' })
  formError.value = ''
  showForm.value = true
}

/**
 * Abre el modal en modo edición con datos del cliente.
 * @param {Object} c - Objeto del cliente
 */
function openEdit(c) {
  editingId.value = c.id
  Object.assign(form, {
    nombre: c.nombre || '',
    alias: c.alias || '',
    telefono: c.telefono || '',
    email: c.email || '',
    notas: c.notas || '',
  })
  formError.value = ''
  showForm.value = true
}

/**
 * Envía el formulario de creación/edición de cliente.
 * @returns {Promise<void>}
 */
async function submit() {
  formError.value = ''
  saving.value = true
  try {
    const body = { nombre: form.nombre }
    if (form.alias) body.alias = form.alias
    if (form.telefono) body.telefono = form.telefono
    if (form.email) body.email = form.email
    if (form.notas) body.notas = form.notas
    if (editingId.value) {
      await clientes.update(editingId.value, body)
    } else {
      await clientes.create(body)
    }
      showForm.value = false
      cargarLista()
      Object.assign(form, { nombre: '', alias: '', telefono: '', email: '', notas: '' })
  } catch (err) {
    formError.value = parseError(err)
  } finally {
    saving.value = false
  }
}

/**
 * Abre el detalle del cliente, cargando sus cuentas.
 * @param {Object} c - Objeto del cliente
 */
async function openDetail(c) {
  detailKey.value += 1
  detailCliente.value = c
  historial.value = []
  historialPaginacion.value = {}
  historialCargado.value = false
  documentos.value = []
  documentosCargados.value = false
  showDetail.value = true
  loadingCuentas.value = true
  try {
    const { data } = await api.get(`/clientes/${c.id}/cuentas`)
    clienteCuentas.value = Array.isArray(data) ? data : (data.data || [])
  } catch {
    clienteCuentas.value = []
  } finally {
    loadingCuentas.value = false
  }
}

/** Carga los documentos la primera vez que se expande la sección. */
function onDocumentosToggle(open) {
  if (open && !documentosCargados.value) cargarDocumentos()
}

/** Carga el historial la primera vez que se expande la sección. */
function onHistorialToggle(open) {
  if (open && !historialCargado.value) cargarHistorial()
}

/** Abre el formulario para agregar una cuenta al cliente actual */
function openCuentaForm() {
  cuentaFormError.value = ''
  Object.assign(cuentaForm, {
    cliente_id: detailCliente.value.id,
    banco_id: '',
    moneda_id: '',
    alias: '',
    tipo: '',
    numero_cuenta: '',
    notas: '',
    activa: true,
  })
  bancos.fetchAll()
  tasas.fetchMonedas()
  showCuentaForm.value = true
}

/**
 * Envía el formulario de creación de cuenta para el cliente.
 * @returns {Promise<void>}
 */
async function submitCuenta() {
  cuentaFormError.value = ''
  savingCuenta.value = true
  try {
    const body = {
      cliente_id: Number(cuentaForm.cliente_id),
      moneda_id: Number(cuentaForm.moneda_id),
      alias: cuentaForm.alias,
      tipo: cuentaForm.tipo,
      activa: cuentaForm.activa,
    }
    if (cuentaForm.tipo !== 'efectivo') {
      body.banco_id = Number(cuentaForm.banco_id)
    }
    if (cuentaForm.numero_cuenta && cuentaForm.tipo !== 'efectivo') body.numero_cuenta = cuentaForm.numero_cuenta
    if (cuentaForm.notas) body.notas = cuentaForm.notas
    await api.post('/cuentas', body)
    showCuentaForm.value = false
    const { data } = await api.get(`/clientes/${detailCliente.value.id}/cuentas`)
    clienteCuentas.value = Array.isArray(data) ? data : (data.data || [])
  } catch (err) {
    cuentaFormError.value = parseError(err)
  } finally {
    savingCuenta.value = false
  }
}

/**
 * Carga el historial paginado de operaciones del cliente en detalle.
 * @param {number} [page=1] - Número de página
 * @returns {Promise<void>}
 */
async function cargarHistorial(page = 1) {
  if (!detailCliente.value) return
  loadingHistorial.value = true
  try {
    const params = { page }
    if (historialFiltros.fecha_desde) params.fecha_desde = historialFiltros.fecha_desde
    if (historialFiltros.fecha_hasta) params.fecha_hasta = historialFiltros.fecha_hasta
    if (historialFiltros.tipo_codigo) params.tipo_codigo = historialFiltros.tipo_codigo

    const { data } = await api.get(`/clientes/${detailCliente.value.id}/operaciones`, { params })
    historial.value = data.data || []
    historialPaginacion.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      prev_page_url: data.prev_page_url,
      next_page_url: data.next_page_url,
    }
    historialCargado.value = true
  } catch {
    historial.value = []
  } finally {
    loadingHistorial.value = false
  }
}

/**
 * Formatea una fecha ISO a dd/mm/aaaa.
 * @param {string} fecha - Fecha ISO
 * @returns {string}
 */
function formatFecha(fecha) {
  if (!fecha) return '—'
  const d = new Date(fecha)
  return d.toLocaleDateString('es-VE', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

/**
 * Obtiene el monto absoluto formateado de un movimiento en una moneda específica.
 * @param {Object} op - Operación con movimientos
 * @param {string} moneda - Código de moneda (USD, VES)
 * @returns {string}
 */
function formatMonto(op, moneda) {
  const mov = op.movimientos?.find(m => m.moneda?.codigo === moneda)
  if (!mov) return '—'
  return formatMoney(Math.abs(parseFloat(mov.monto)))
}

/**
 * Clases de badge según el tipo de operación, alineadas con la paleta corporativa.
 * @param {Object} op - Operación con tipo_operacion
 * @returns {string}
 */
function clasesBadgeTipo(op) {
  const codigo = op?.tipo_operacion?.codigo
  switch (codigo) {
    case 'compra_usd':
      return 'bg-gold-soft text-gold dark:text-gold-dark'
    case 'venta_usd':
      return 'bg-danger-soft text-danger'
    case 'intermediada':
      return 'bg-[#93A1A5]/20 text-navy dark:text-[#93A1A5]'
    case 'comision':
      return 'bg-[#D9C79E]/30 text-[#6b5d36] dark:text-[#D9C79E]'
    default:
      return 'bg-surface-muted text-ink-muted'
  }
}

/**
 * Exporta el historial de operaciones del cliente a PDF.
 * @returns {Promise<void>}
 */
async function exportarPDF() {
  if (!detailCliente.value) return
  exportando.value = true
  try {
    const token = localStorage.getItem('token')
    const params = {}
    if (historialFiltros.fecha_desde) params.fecha_desde = historialFiltros.fecha_desde
    if (historialFiltros.fecha_hasta) params.fecha_hasta = historialFiltros.fecha_hasta
    if (historialFiltros.tipo_codigo) params.tipo_codigo = historialFiltros.tipo_codigo

    const axios = (await import('axios')).default
    const response = await axios.post(
      `${import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1'}/clientes/${detailCliente.value.id}/operaciones/exportar`,
      params,
      {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/pdf',
        },
        responseType: 'blob',
      }
    )

    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `operaciones_${detailCliente.value.nombre}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Error al exportar PDF:', err)
    alert('Error al generar el PDF. Intenta de nuevo.')
  } finally {
    exportando.value = false
  }
}

// ── Funciones de Documentos ──

/** Carga la lista de documentos del cliente actual. */
async function cargarDocumentos() {
  if (!detailCliente.value) return
  loadingDocumentos.value = true
  try {
    const { data } = await api.get(`/clientes/${detailCliente.value.id}/documentos`)
    documentos.value = Array.isArray(data) ? data : (data.data || [])
    documentosCargados.value = true
  } catch {
    documentos.value = []
    documentosCargados.value = true
  } finally {
    loadingDocumentos.value = false
  }
}

/** Sube un documento al servidor. */
async function subirDocumento(event) {
  const file = event.target.files[0]
  if (!file || !detailCliente.value) return

  let tipo = 'otro'
  if (/cedula|ced/i.test(file.name)) tipo = 'cedula'
  if (/rif/i.test(file.name)) tipo = 'rif'

  const formData = new FormData()
  formData.append('archivo', file)
  formData.append('tipo', tipo)

  try {
    await api.post(`/clientes/${detailCliente.value.id}/documentos`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    cargarDocumentos()
  } catch (err) {
    alert(parseError(err))
  }
  event.target.value = ''
}

/** Elimina un documento del servidor. */
async function eliminarDocumento(doc) {
  if (!confirm(`¿Eliminar "${doc.nombre_archivo}"?`)) return
  try {
    await api.delete(`/documentos/${doc.id}`)
    cargarDocumentos()
  } catch (err) {
    alert(parseError(err))
  }
}

/** Verifica si el documento es una imagen por su tipo MIME. */
function esImagen(doc) {
  return doc?.mime_type?.startsWith('image/')
}

/**
 * Elimina (soft-delete) un cliente.
 * @param {Object} c - Objeto del cliente
 * @returns {Promise<void>}
 */
async function eliminarCliente(c) {
  if (!confirm(`¿Eliminar cliente "${c.nombre}"? Se archivará y podrá recuperarse después.`)) return
  try {
    await api.delete(`/clientes/${c.id}`)
    showDetail.value = false
    cargarLista()
  } catch (err) {
    alert(parseError(err))
  }
}

/**
 * Restaura un cliente eliminado.
 * @param {Object} c - Objeto del cliente
 * @returns {Promise<void>}
 */
async function restaurarCliente(c) {
  if (!confirm(`¿Recuperar cliente "${c.nombre}"?`)) return
  try {
    await clientes.restore(c.id)
    cargarLista()
  } catch (err) {
    alert(parseError(err))
  }
}

/** Carga la lista de clientes al montar el componente */
onMounted(() => cargarLista())
</script>
