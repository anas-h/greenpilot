export const required = v => !!v || v === 0 || 'Ce champ est requis.'

export const email = v => /.+@.+\..+/.test(v) || 'Email invalide.'

export const minLength = (n) => v => (v && v.length >= n) || `${n} caracteres minimum.`

export const positiveNumber = v => (v && v > 0) || 'La valeur doit etre superieure a 0.'

export const siretLength = v => (v && v.length === 14) || 'Le SIRET doit faire 14 caracteres.'

export const passwordMatch = (getterFn) => v => v === getterFn() || 'Les mots de passe ne correspondent pas.'
