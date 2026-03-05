<template>
  <v-card>
    <v-data-table
      :headers="headers"
      :items="collecteurs"
      :loading="loading"
      :search="search"
      items-per-page="15"
      hover
    >
      <template #item.raison_sociale="{ item }">
        <div>
          <span class="font-weight-medium">{{ item.raison_sociale }}</span>
          <div class="text-caption text-grey">
            {{ item.siret }}
          </div>
        </div>
      </template>

      <template #item.ville="{ item }">
        <span>{{ item.code_postal }} {{ item.ville }}</span>
      </template>

      <template #item.autorisation="{ item }">
        <v-chip
          :color="getAutorisationColor(item)"
          size="small"
          variant="flat"
        >
          <v-icon
            start
            size="small"
          >
            {{ getAutorisationIcon(item) }}
          </v-icon>
          {{ getAutorisationLabel(item) }}
        </v-chip>
      </template>

      <template #item.types_dechets="{ item }">
        <div class="d-flex flex-wrap ga-1">
          <v-chip
            v-for="type in (item.types_dechets || []).slice(0, 3)"
            :key="type.id"
            size="x-small"
            :color="type.dangereux ? 'error' : 'primary'"
            variant="outlined"
          >
            {{ type.denomination }}
          </v-chip>
          <v-chip
            v-if="(item.types_dechets || []).length > 3"
            size="x-small"
            color="grey"
            variant="outlined"
          >
            +{{ item.types_dechets.length - 3 }}
          </v-chip>
        </div>
      </template>

      <template #item.actif="{ item }">
        <v-chip
          :color="item.actif ? 'success' : 'grey'"
          size="small"
          variant="flat"
        >
          {{ item.actif ? 'Actif' : 'Inactif' }}
        </v-chip>
      </template>

      <template #item.actions="{ item }">
        <v-btn
          icon="mdi-pencil"
          variant="text"
          size="small"
          title="Modifier"
          @click="$emit('edit', item)"
        />
        <v-btn
          icon="mdi-delete"
          variant="text"
          size="small"
          color="error"
          title="Supprimer"
          @click="$emit('delete', item)"
        />
      </template>

      <template #no-data>
        <div class="text-center pa-6">
          <v-icon
            size="48"
            color="grey-lighten-1"
            class="mb-3"
          >
            mdi-truck-outline
          </v-icon>
          <p class="text-body-1 text-grey">
            Aucun collecteur enregistre
          </p>
        </div>
      </template>
    </v-data-table>
  </v-card>
</template>

<script setup>
defineProps({
  collecteurs: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  search: { type: String, default: '' },
  filterActif: { type: [Boolean, null], default: null },
  filterAutorisation: { type: [Boolean, null], default: null },
})

defineEmits(['edit', 'delete'])

const headers = [
  { title: 'Raison sociale', key: 'raison_sociale', sortable: true },
  { title: 'Ville', key: 'ville', sortable: true },
  { title: 'Autorisation', key: 'autorisation', sortable: false },
  { title: 'Types de dechets', key: 'types_dechets', sortable: false },
  { title: 'Statut', key: 'actif', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' },
]

function getAutorisationColor(item) {
  if (!item.date_validite_autorisation) return 'grey'
  const expiry = new Date(item.date_validite_autorisation)
  const now = new Date()
  const daysUntilExpiry = Math.ceil((expiry - now) / (1000 * 60 * 60 * 24))
  if (daysUntilExpiry < 0) return 'error'
  if (daysUntilExpiry <= 30) return 'warning'
  return 'success'
}

function getAutorisationIcon(item) {
  if (!item.date_validite_autorisation) return 'mdi-help-circle'
  const color = getAutorisationColor(item)
  if (color === 'error') return 'mdi-alert-circle'
  if (color === 'warning') return 'mdi-alert'
  return 'mdi-check-circle'
}

function getAutorisationLabel(item) {
  if (!item.date_validite_autorisation) return 'Non renseignee'
  const expiry = new Date(item.date_validite_autorisation)
  const now = new Date()
  const daysUntilExpiry = Math.ceil((expiry - now) / (1000 * 60 * 60 * 24))
  if (daysUntilExpiry < 0) return 'Expiree'
  if (daysUntilExpiry <= 30) return `Expire dans ${daysUntilExpiry}j`
  return 'Valide'
}
</script>
