export function canUseWebStorage(): boolean {
  if (typeof window === 'undefined') {
    return false
  }

  try {
    const probeKey = '__fwber_storage_probe__'
    window.localStorage.setItem(probeKey, '1')
    window.localStorage.removeItem(probeKey)
    return true
  } catch {
    return false
  }
}

export function safeLocalStorageKeys(): string[] {
  if (typeof window === 'undefined') {
    return []
  }

  try {
    return Object.keys(window.localStorage)
  } catch {
    return []
  }
}

export function safeLocalStorageGet(key: string): string | null {
  if (typeof window === 'undefined') {
    return null
  }

  try {
    return window.localStorage.getItem(key)
  } catch {
    return null
  }
}

export function safeLocalStorageSet(key: string, value: string): boolean {
  if (typeof window === 'undefined') {
    return false
  }

  try {
    window.localStorage.setItem(key, value)
    return true
  } catch {
    return false
  }
}

export function safeLocalStorageRemove(key: string): void {
  if (typeof window === 'undefined') {
    return
  }

  try {
    window.localStorage.removeItem(key)
  } catch {
    // Swallow storage access failures so restricted browser contexts
    // do not generate noisy runtime errors in core auth flows.
  }
}

export function safeSessionStorageGet(key: string): string | null {
  if (typeof window === 'undefined') {
    return null
  }

  try {
    return window.sessionStorage.getItem(key)
  } catch {
    return null
  }
}

export function safeSessionStorageSet(key: string, value: string): boolean {
  if (typeof window === 'undefined') {
    return false
  }

  try {
    window.sessionStorage.setItem(key, value)
    return true
  } catch {
    return false
  }
}

export function safeSessionStorageRemove(key: string): void {
  if (typeof window === 'undefined') {
    return
  }

  try {
    window.sessionStorage.removeItem(key)
  } catch {
    // Ignore blocked session storage contexts.
  }
}

export function canUseIndexedDB(): boolean {
  if (typeof window === 'undefined' || typeof indexedDB === 'undefined') {
    return false
  }

  try {
    return typeof indexedDB.open === 'function'
  } catch {
    return false
  }
}
