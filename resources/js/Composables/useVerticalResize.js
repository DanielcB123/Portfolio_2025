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
  let isResizing = false

  const clamp = (value) => Math.min(maxHeight, Math.max(minHeight, value))

  const persist = () => {
    if (!storageKey || typeof window === 'undefined') return

    window.localStorage.setItem(storageKey, String(height.value))
  }

  const clearBodyStyles = () => {
    document.body.style.cursor = ''
    document.body.style.userSelect = ''
    document.body.style.touchAction = ''
  }

  const onMove = (event) => {
    if (!isResizing) return

    if (event.cancelable) {
      event.preventDefault()
    }

    const clientY = event.touches?.[0]?.clientY ?? event.clientY
    height.value = clamp(startHeight + (clientY - startY))
  }

  const stop = () => {
    isResizing = false

    document.removeEventListener('mousemove', onMove)
    document.removeEventListener('mouseup', stop)
    document.removeEventListener('touchmove', onMove)
    document.removeEventListener('touchend', stop)
    document.removeEventListener('touchcancel', stop)

    clearBodyStyles()
    persist()
  }

  const start = (event) => {
    if (isResizing) return

    isResizing = true
    startY = event.touches?.[0]?.clientY ?? event.clientY
    startHeight = height.value

    document.addEventListener('mousemove', onMove)
    document.addEventListener('mouseup', stop)
    document.addEventListener('touchmove', onMove, { passive: false })
    document.addEventListener('touchend', stop)
    document.addEventListener('touchcancel', stop)

    document.body.style.cursor = 'ns-resize'
    document.body.style.userSelect = 'none'
    document.body.style.touchAction = 'none'
  }

  onBeforeUnmount(stop)

  return { height, start, load }
}
