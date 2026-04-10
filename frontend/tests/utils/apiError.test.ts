import { describe, it, expect } from 'vitest'
import { getApiErrorMessage } from '~/utils/apiError'

describe('getApiErrorMessage', () => {
  const fallback = 'Something went wrong.'

  it('returns fallback for null', () => {
    expect(getApiErrorMessage(null, fallback)).toBe(fallback)
  })

  it('returns fallback for undefined', () => {
    expect(getApiErrorMessage(undefined, fallback)).toBe(fallback)
  })

  it('returns fallback for non-object', () => {
    expect(getApiErrorMessage('string-error', fallback)).toBe(fallback)
  })

  it('extracts first validation error from errors object', () => {
    const err = { data: { errors: { email: ['Email is required'] } } }
    expect(getApiErrorMessage(err, fallback)).toBe('Email is required')
  })

  it('handles string values in errors (non-array)', () => {
    const err = { data: { errors: { name: 'Name is required' } } }
    expect(getApiErrorMessage(err, fallback)).toBe('Name is required')
  })

  it('prefers errors over message', () => {
    const err = {
      data: {
        message: 'General error',
        errors: { field: ['Specific validation error'] },
      },
    }
    expect(getApiErrorMessage(err, fallback)).toBe('Specific validation error')
  })

  it('falls back to data.message if no errors', () => {
    const err = { data: { message: 'Not found' } }
    expect(getApiErrorMessage(err, fallback)).toBe('Not found')
  })

  it('falls back to statusMessage', () => {
    const err = { statusMessage: 'Bad Gateway' }
    expect(getApiErrorMessage(err, fallback)).toBe('Bad Gateway')
  })

  it('ignores "Unauthorized" statusMessage', () => {
    const err = { statusMessage: 'Unauthorized' }
    expect(getApiErrorMessage(err, fallback)).toBe(fallback)
  })

  it('returns default fallback when no fallback provided', () => {
    expect(getApiErrorMessage(null)).toBe('Could not complete the operation.')
  })
})
