<template>
  <div class="col-md-3 mt-3">
    <button class="btn btn-secondary mb-2" @click="mostrarModal = true">
      <i class="bi bi-building-fill-add"></i> Añadir
    </button>

    <Buscador :tipo="'Buscar Empresa'" @search="onSearch"></Buscador>

    <EmpresaForm
      :show="mostrarModal"
      :errorMessage="errores"
      @close="mostrarModal = false"
      @crear="crearEmpresa"
    />

    <ul v-if="empresas.length" class="list-group mt-3">
      <button
        type="button"
        class="list-group-item list-group-item-action text-white bg-indigo"
        @click="emit('seleccionarEmpresa', null)"
      >
        <h5 class="mb-0">Empresas</h5>
      </button>

      <button
        v-for="empresa in empresas"
        :key="empresa.id"
        type="button"
        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
        @click="emit('seleccionarEmpresa', empresa)"
      >
        {{ empresa.Nombre }}
        <small class="text-muted">{{ empresa.CIF }}</small>
      </button>
    </ul>

    <div v-else-if="!cargando" class="alert alert-light mt-3 text-center border">
      No hay empresas disponibles.
    </div>

    <p v-if="cargando" class="text-center mt-3">
      <span
        class="spinner-border spinner-border-sm text-primary"
        role="status"
        aria-hidden="true"
      ></span>
      Cargando...
    </p>

    <nav v-if="totalPages > 1 && !cargando" class="mt-3">
      <ul class="pagination justify-content-center pagination-sm">
        <li class="page-item" :class="{ disabled: currentPage === 1 }">
          <button class="page-link" @click="cargarEmpresas(currentPage - 1)">&laquo;</button>
        </li>

        <li
          v-for="page in totalPages"
          :key="page"
          class="page-item"
          :class="{ active: currentPage === page }"
        >
          <button class="page-link" @click="cargarEmpresas(page)">{{ page }}</button>
        </li>

        <li class="page-item" :class="{ disabled: currentPage === totalPages }">
          <button class="page-link" @click="cargarEmpresas(currentPage + 1)">&raquo;</button>
        </li>
      </ul>
    </nav>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import EmpresaForm from './EmpresaForm.vue';
import Buscador from '../Buscador.vue';
import api from '@/services/api.js';

const empresas = ref([]);
const mostrarModal = ref(false);
const errores = ref(null);

// Paginación
const currentPage = ref(1);
const totalPages = ref(1);
const perPage = ref(5);

const cargando = ref(false);
const q = ref('');
let searchTimeout = null;

const emit = defineEmits(['seleccionarEmpresa']);

// Cargar al inicio
onMounted(() => {
  cargarEmpresas(1);
});

async function cargarEmpresas(page = 1) {
  if (page < 1 || (totalPages.value > 1 && page > totalPages.value)) return;

  cargando.value = true;
  currentPage.value = page;

  try {
    // Hacemos la petición con 'page' y 'q' (si está vacío, traerá todas)
    const response = await api.get('/api/allempresas', {
      params: {
        q: q.value,
        page: page,
        per_page: perPage.value,
      },
    });

    // Con paginate(), los datos reales están en .data.data
    empresas.value = response.data.data;
    totalPages.value = response.data.last_page;
  } catch (error) {
    console.error('Error al cargar empresas:', error);
  } finally {
    cargando.value = false;
  }
}

const crearEmpresa = async (empresa) => {
  try {
    const response = await api.post('/api/empresa/create', { ...empresa });
    mostrarModal.value = false;
    errores.value = null;
    q.value = response.data.Nombre;
    // Recargar para ver la nueva
    cargarEmpresas(1);
  } catch (e) {
    if (e.response?.status === 422) {
      errores.value = e.response.data.errors;
    }
  }
};

function onSearch(texto) {
  q.value = (texto || '').trim();
}

watch(q, () => {
  if (searchTimeout) clearTimeout(searchTimeout);

  // Esperar un poco para no saturar al servidor
  searchTimeout = setTimeout(() => {
    // Al buscar, siempre volvemos a la página 1
    cargarEmpresas(1);
  }, 400);
});
</script>

<style scoped></style>
