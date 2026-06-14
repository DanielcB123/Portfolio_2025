import { route as ziggyRoute } from 'ziggy-js'

let ziggyConfig = null

export function configureAppRoute(config) {
  ziggyConfig = config
}

/** Relative paths for same-origin Inertia navigation */
export function appRoute(name, params = {}) {
  return ziggyRoute(name, params, false, ziggyConfig)
}
