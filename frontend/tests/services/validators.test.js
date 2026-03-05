import { describe, it, expect } from 'vitest'
import { required, email, minLength, positiveNumber, siretLength, passwordMatch } from '../../src/services/validators'

describe('Validators', () => {
  describe('required', () => {
    it('returns true for non-empty string', () => {
      expect(required('hello')).toBe(true)
    })

    it('returns true for zero', () => {
      expect(required(0)).toBe(true)
    })

    it('returns error message for empty string', () => {
      expect(required('')).toBe('Ce champ est requis.')
    })

    it('returns error message for null', () => {
      expect(required(null)).toBe('Ce champ est requis.')
    })

    it('returns error message for undefined', () => {
      expect(required(undefined)).toBe('Ce champ est requis.')
    })

    it('returns true for truthy values', () => {
      expect(required('abc')).toBe(true)
      expect(required(1)).toBe(true)
      expect(required(true)).toBe(true)
    })
  })

  describe('email', () => {
    it('returns true for valid email', () => {
      expect(email('user@example.com')).toBe(true)
    })

    it('returns true for email with subdomain', () => {
      expect(email('user@sub.example.com')).toBe(true)
    })

    it('returns error for invalid email', () => {
      expect(email('not-an-email')).toBe('Email invalide.')
    })

    it('returns error for email without domain', () => {
      expect(email('user@')).toBe('Email invalide.')
    })

    it('returns error for email without @', () => {
      expect(email('user.example.com')).toBe('Email invalide.')
    })
  })

  describe('minLength', () => {
    it('returns true when string meets minimum', () => {
      const validator = minLength(8)
      expect(validator('password')).toBe(true)
    })

    it('returns true when string exceeds minimum', () => {
      const validator = minLength(3)
      expect(validator('hello')).toBe(true)
    })

    it('returns error when string is too short', () => {
      const validator = minLength(8)
      expect(validator('abc')).toBe('8 caracteres minimum.')
    })

    it('returns error for empty string', () => {
      const validator = minLength(1)
      expect(validator('')).toBe('1 caracteres minimum.')
    })

    it('returns error for null/undefined', () => {
      const validator = minLength(5)
      expect(validator(null)).toBe('5 caracteres minimum.')
    })
  })

  describe('positiveNumber', () => {
    it('returns true for positive number', () => {
      expect(positiveNumber(5)).toBe(true)
    })

    it('returns true for decimal positive', () => {
      expect(positiveNumber(0.5)).toBe(true)
    })

    it('returns error for zero', () => {
      expect(positiveNumber(0)).toBe('La valeur doit etre superieure a 0.')
    })

    it('returns error for negative number', () => {
      expect(positiveNumber(-1)).toBe('La valeur doit etre superieure a 0.')
    })

    it('returns error for null', () => {
      expect(positiveNumber(null)).toBe('La valeur doit etre superieure a 0.')
    })
  })

  describe('siretLength', () => {
    it('returns true for 14-character string', () => {
      expect(siretLength('12345678901234')).toBe(true)
    })

    it('returns error for too short', () => {
      expect(siretLength('123')).toBe('Le SIRET doit faire 14 caracteres.')
    })

    it('returns error for too long', () => {
      expect(siretLength('123456789012345')).toBe('Le SIRET doit faire 14 caracteres.')
    })

    it('returns error for null', () => {
      expect(siretLength(null)).toBe('Le SIRET doit faire 14 caracteres.')
    })
  })

  describe('passwordMatch', () => {
    it('returns true when passwords match', () => {
      const validator = passwordMatch(() => 'mypassword')
      expect(validator('mypassword')).toBe(true)
    })

    it('returns error when passwords differ', () => {
      const validator = passwordMatch(() => 'password1')
      expect(validator('password2')).toBe('Les mots de passe ne correspondent pas.')
    })

    it('returns error for empty vs non-empty', () => {
      const validator = passwordMatch(() => 'password')
      expect(validator('')).toBe('Les mots de passe ne correspondent pas.')
    })
  })
})
