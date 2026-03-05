<template>
  <div>
    <!-- Grid view -->
    <v-row v-if="viewMode === 'grid'">
      <v-col
        v-for="conteneur in conteneurs"
        :key="conteneur.id"
        cols="12"
        sm="6"
        md="4"
        lg="3"
      >
        <v-card
          class="h-100 d-flex flex-column"
          hover
          @click="$emit('show', conteneur)"
        >
          <v-card-text class="flex-grow-1">
            <!-- Header -->
            <div class="d-flex align-center justify-space-between mb-3">
              <div class="d-flex align-center ga-2">
                <v-icon
                  :color="conteneur.type_dechet?.dangereux ? 'error' : 'primary'"
                  size="small"
                >
                  {{ conteneur.type_dechet?.dangereux ? 'mdi-alert-circle' : 'mdi-recycle' }}
                </v-icon>
                <span
                  class="text-subtitle-2 font-weight-bold text-truncate"
                  style="max-width: 70%;"
                >
                  {{ conteneur.nom }}
                </span>
              </div>
              <v-btn
                icon="mdi-qrcode"
                size="x-small"
                variant="text"
                color="grey"
                title="Telecharger QR code"
                @click.stop="$emit('download-qr', conteneur)"
              />
            </div>

            <!-- Type dechet chip -->
            <v-chip
              size="small"
              :color="conteneur.type_dechet?.dangereux ? 'error' : 'success'"
              variant="tonal"
              class="mb-3"
            >
              {{ conteneur.type_dechet?.denomination || 'Non defini' }}
            </v-chip>

            <!-- Jauge visuelle -->
            <ConteneurJauge
              :niveau="Number(conteneur.niveau_actuel)"
              :capacite="Number(conteneur.capacite)"
              :unite="conteneur.unite"
              height="80"
              compact
            />

            <!-- Emplacement -->
            <div
              v-if="conteneur.emplacement"
              class="text-caption text-grey mt-2 d-flex align-center ga-1"
            >
              <v-icon size="x-small">
                mdi-map-marker
              </v-icon>
              {{ conteneur.emplacement }}
            </div>
          </v-card-text>

          <!-- Actions -->
          <v-card-actions class="pt-0">
            <v-spacer />
            <v-btn
              icon="mdi-pencil"
              size="small"
              variant="text"
              title="Modifier"
              @click.stop="$emit('edit', conteneur)"
            />
            <v-btn
              icon="mdi-delete"
              size="small"
              variant="text"
              color="error"
              title="Supprimer"
              @click.stop="$emit('delete', conteneur)"
            />
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>

    <!-- List view -->
    <v-table
      v-else
      hover
    >
      <thead>
        <tr>
          <th>Nom</th>
          <th>Type de dechet</th>
          <th>Remplissage</th>
          <th>Emplacement</th>
          <th>QR Code</th>
          <th class="text-right">
            Actions
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="conteneur in conteneurs"
          :key="conteneur.id"
          class="cursor-pointer"
          @click="$emit('show', conteneur)"
        >
          <td>
            <div class="d-flex align-center ga-2">
              <v-icon
                :color="conteneur.type_dechet?.dangereux ? 'error' : 'primary'"
                size="small"
              >
                {{ conteneur.type_dechet?.dangereux ? 'mdi-alert-circle' : 'mdi-recycle' }}
              </v-icon>
              {{ conteneur.nom }}
            </div>
          </td>
          <td>
            <v-chip
              size="small"
              :color="conteneur.type_dechet?.dangereux ? 'error' : 'success'"
              variant="tonal"
            >
              {{ conteneur.type_dechet?.denomination || '-' }}
            </v-chip>
          </td>
          <td style="min-width: 150px;">
            <ConteneurJauge
              :niveau="Number(conteneur.niveau_actuel)"
              :capacite="Number(conteneur.capacite)"
              :unite="conteneur.unite"
              height="30"
              inline
            />
          </td>
          <td>{{ conteneur.emplacement || '-' }}</td>
          <td>
            <v-btn
              icon="mdi-qrcode"
              size="x-small"
              variant="text"
              title="Telecharger QR code"
              @click.stop="$emit('download-qr', conteneur)"
            />
          </td>
          <td class="text-right">
            <v-btn
              icon="mdi-pencil"
              size="x-small"
              variant="text"
              title="Modifier"
              @click.stop="$emit('edit', conteneur)"
            />
            <v-btn
              icon="mdi-delete"
              size="x-small"
              variant="text"
              color="error"
              title="Supprimer"
              @click.stop="$emit('delete', conteneur)"
            />
          </td>
        </tr>
      </tbody>
    </v-table>
  </div>
</template>

<script setup>
import ConteneurJauge from './ConteneurJauge.vue'

defineProps({
  conteneurs: {
    type: Array,
    required: true,
  },
  viewMode: {
    type: String,
    default: 'grid',
  },
})

defineEmits(['edit', 'delete', 'show', 'download-qr'])
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}
</style>
