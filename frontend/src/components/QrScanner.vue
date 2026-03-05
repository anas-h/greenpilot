<template>
  <div class="qr-scanner">
    <div
      v-if="!cameraActive"
      class="text-center py-6"
    >
      <v-icon
        size="64"
        color="grey-lighten-1"
      >
        mdi-qrcode-scan
      </v-icon>
      <p class="text-body-2 text-grey mt-3">
        Cliquez sur le bouton ci-dessous pour activer la camera et scanner un QR code.
      </p>
      <v-btn
        color="primary"
        class="mt-4"
        prepend-icon="mdi-camera"
        :loading="starting"
        @click="startCamera"
      >
        Activer la camera
      </v-btn>
    </div>

    <div v-else>
      <div class="scanner-viewport">
        <qrcode-stream
          :constraints="{ facingMode: 'environment' }"
          @detect="onDetect"
          @error="onError"
        />
        <div class="scanner-overlay">
          <div class="scanner-frame" />
        </div>
      </div>

      <v-alert
        v-if="error"
        type="error"
        variant="tonal"
        class="mt-3"
        density="compact"
      >
        {{ error }}
      </v-alert>

      <div class="text-center mt-3">
        <v-btn
          color="error"
          variant="outlined"
          prepend-icon="mdi-camera-off"
          @click="stopCamera"
        >
          Arreter la camera
        </v-btn>
      </div>
    </div>

    <!-- Manual input fallback -->
    <v-divider class="my-4" />
    <v-text-field
      v-model="manualCode"
      label="Ou saisir le code manuellement"
      prepend-inner-icon="mdi-keyboard"
      append-inner-icon="mdi-send"
      hide-details
      @click:append-inner="submitManual"
      @keyup.enter="submitManual"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { QrcodeStream } from 'vue-qrcode-reader'

const emit = defineEmits(['decoded'])

const cameraActive = ref(false)
const starting = ref(false)
const error = ref('')
const manualCode = ref('')
const isNative = ref(false)

onMounted(async () => {
  try {
    const { Capacitor } = await import('@capacitor/core')
    isNative.value = Capacitor.isNativePlatform()
  } catch {
    isNative.value = false
  }
})

async function startCamera() {
  starting.value = true
  error.value = ''

  if (isNative.value) {
    try {
      const { Camera } = await import('@capacitor/camera')
      await Camera.requestPermissions()
    } catch {
      // Permission handling - fall through to web scanner
    }
  }

  cameraActive.value = true
  starting.value = false
}

function stopCamera() {
  cameraActive.value = false
}

function onDetect(detectedCodes) {
  if (detectedCodes && detectedCodes.length > 0) {
    const code = detectedCodes[0].rawValue
    if (code) {
      stopCamera()
      emit('decoded', code)
    }
  }
}

function onError(err) {
  if (err.name === 'NotAllowedError') {
    error.value = "L'acces a la camera a ete refuse. Veuillez autoriser l'acces dans les parametres du navigateur."
  } else if (err.name === 'NotFoundError') {
    error.value = 'Aucune camera detectee sur cet appareil.'
  } else if (err.name === 'NotReadableError') {
    error.value = 'La camera est deja utilisee par une autre application.'
  } else {
    error.value = `Erreur camera: ${err.message}`
  }
}

function submitManual() {
  const code = manualCode.value.trim()
  if (code) {
    emit('decoded', code)
    manualCode.value = ''
  }
}
</script>

<style scoped>
.qr-scanner {
  width: 100%;
}

.scanner-viewport {
  position: relative;
  width: 100%;
  max-width: 400px;
  margin: 0 auto;
  border-radius: 12px;
  overflow: hidden;
}

.scanner-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: none;
}

.scanner-frame {
  width: 200px;
  height: 200px;
  border: 3px solid #4CAF50;
  border-radius: 12px;
  box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.3);
}
</style>
