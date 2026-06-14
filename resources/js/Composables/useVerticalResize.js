import { onBeforeUnmount, ref } from 'vue'

export function useVerticalResize({
  storageKey = null,
  defaultHeight = 225,
  minHeight = 180,
  maxHeight = 560,
} = {}) {
  const height = ref(defaultHeight)

  let startY = 0
  let startHeight = 0

  const clamp = (value) => Math.min(maxHeight, Math.max(minHeight, value))

  const persist = () => {
    if (!storageKey || typeof window === 'undefined') return

    window.localStorage.setItem(storageKey, String(height.value))
  }

  const load = () => {
    if (!storageKey || typeof window === 'undefined') return

    const stored = window.localStorage.getItem(storageKey)
    const parsed = Number(stored)

    if (!Number.isNaN(parsed)) {
      height.value = clamp(parsed)
    }
  }

  const onMove = (event) => {
    const clientY = event.touches?.[0]?.clientY ?? event.clientY
    height.value = clamp(startHeight + (clientY - startY))
  }

  const stop = () => {
    document.removeEventListener('mousemove', onMove)
    document.removeEventListener('mouseup', stop)
    document.removeEventListener('touchmove', onMove)
    document.removeEventListener('touchend', stop)
    document.body.style.cursor = ''
    document.body.style.userSelect = ''
    persist()
  }

  const start = (event) => {
    startY = event.touches?.[0]?.clientY ?? event.clientY
    startHeight = height.value

    document.addEventListener('mousemove', onMove)
    document.addEventListener('mouseup', stop)
    document.addEventListener('touchmove', onMove, { passive: true })
    document.addEventListener('touchend', stop)
    document.body.style.cursor = 'ns-resize'
    document.body.style.userSelect = 'none'
  }

  onBeforeUnmount(stop)

  return { height, start, load }
}
