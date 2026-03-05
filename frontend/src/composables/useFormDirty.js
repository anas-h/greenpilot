import { ref, watch } from 'vue'

export function useFormDirty(formRef) {
  const isDirty = ref(false)
  let initialSnapshot = null

  function snapshot() {
    initialSnapshot = JSON.stringify(formRef.value)
    isDirty.value = false
  }

  watch(formRef, () => {
    if (initialSnapshot === null) return
    isDirty.value = JSON.stringify(formRef.value) !== initialSnapshot
  }, { deep: true })

  return { isDirty, snapshot }
}
