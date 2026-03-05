<template>
  <div>
    <h1 class="text-h5 font-weight-bold mb-6">
      Parametres
    </h1>
    <v-card>
      <v-tabs
        v-model="tab"
        color="primary"
      >
        <v-tab value="garage">
          Garage
        </v-tab>
        <v-tab
          v-if="authStore.hasPermission('users.view')"
          value="users"
        >
          Utilisateurs
        </v-tab>
        <v-tab
          v-if="authStore.hasPermission('mappings.view')"
          value="mappings"
        >
          Mappings
        </v-tab>
      </v-tabs>
      <v-card-text>
        <v-tabs-window v-model="tab">
          <v-tabs-window-item value="garage">
            <GarageConfig />
          </v-tabs-window-item>
          <v-tabs-window-item value="users">
            <UsersConfig />
          </v-tabs-window-item>
          <v-tabs-window-item value="mappings">
            <MappingsConfig />
          </v-tabs-window-item>
        </v-tabs-window>
      </v-card-text>
    </v-card>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import { useAuthStore } from '../../stores/auth'
import GarageConfig from './components/GarageConfig.vue'
import UsersConfig from './components/UsersConfig.vue'
import MappingsConfig from './components/MappingsConfig.vue'

const authStore = useAuthStore()
const tab = ref('garage')
</script>
