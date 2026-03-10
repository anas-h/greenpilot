<template>
  <div>
    <div class="d-flex flex-wrap align-center justify-space-between mb-6 ga-3">
      <div>
        <h1 class="text-h5 text-sm-h4 font-weight-bold text-primary">
          Productions
        </h1>
        <p class="text-body-2 text-grey mt-1">
          Enregistrement et suivi des dechets produits
        </p>
      </div>
      <v-btn
        color="primary"
        prepend-icon="mdi-plus"
        @click="openProductionDialog()"
      >
        Nouvelle production
      </v-btn>
    </div>

    <v-tabs
      v-model="activeTab"
      color="primary"
      class="mb-4"
    >
      <v-tab value="liste">
        <v-icon start>
          mdi-format-list-bulleted
        </v-icon>
        Liste
      </v-tab>
      <v-tab value="scan-barcode">
        <v-icon start>
          mdi-barcode-scan
        </v-icon>
        <span class="d-none d-sm-inline">Scan Produit</span>
      </v-tab>
      <v-tab value="batch">
        <v-icon start>
          mdi-table-plus
        </v-icon>
        Saisie groupee
      </v-tab>
    </v-tabs>

    <v-tabs-window v-model="activeTab">
      <v-tabs-window-item value="liste">
        <ProductionsList
          ref="listRef"
          @edit="openProductionDialog"
        />
      </v-tabs-window-item>

      <v-tabs-window-item value="scan-barcode">
        <ProductionScanBarcode @saved="refreshList" />
      </v-tabs-window-item>

      <v-tabs-window-item value="batch">
        <ProductionBatch @saved="refreshList" />
      </v-tabs-window-item>
    </v-tabs-window>

    <ProductionDialog
      v-model="dialogOpen"
      :production="editingProduction"
      @saved="refreshList"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import ProductionsList from './components/ProductionsList.vue'
import ProductionDialog from './components/ProductionDialog.vue'
import ProductionScanBarcode from './components/ProductionScanBarcode.vue'
import ProductionBatch from './components/ProductionBatch.vue'

const activeTab = ref('liste')
const dialogOpen = ref(false)
const editingProduction = ref(null)
const listRef = ref(null)

function openProductionDialog(production = null) {
  editingProduction.value = production
  dialogOpen.value = true
}

function refreshList() {
  if (listRef.value?.fetchProductions) {
    listRef.value.fetchProductions()
  }
}
</script>
