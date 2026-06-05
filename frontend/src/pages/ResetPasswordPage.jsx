import React, { useState } from 'react'
import { Link, useSearchParams, useNavigate } from 'react-router-dom'
import api from '../services/api'
import ErrorAlert from '../components/ErrorAlert'

export default function ResetPasswordPage() {
  const [params]   = useSearchParams()
  const navigate   = useNavigate()
  const [form, setForm]     = useState({ password: '', password_confirmation: '' })
  const [loading, setLoading] = useState(false)
  const [error, setError]   = useState(null)

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError(null)
    setLoading(true)
    try {
      await api.post('/auth/reset-password', {
        token: params.get('token'),
        email: params.get('email'),
        password: form.password,
        password_confirmation: form.password_confirmation,
      })
      navigate('/login', { state: { message: 'Password reset successfully. Please sign in.' } })
    } catch (err) {
      setError(err)
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-brand-950 to-brand-800 px-4">
      <div className="w-full max-w-md">
        <div className="text-center mb-8">
          <h1 className="text-2xl font-bold text-white">Set New Password</h1>
        </div>
        <div className="card">
          <div className="rounded-lg bg-blue-50 border border-blue-200 p-3 text-xs text-blue-800 mb-4">
            Password must be at least 12 characters with uppercase, lowercase, number, and symbol.
          </div>
          <ErrorAlert error={error} />
          <form onSubmit={handleSubmit} className="space-y-4 mt-2">
            <div>
              <label className="label">New Password</label>
              <input type="password" className="input" value={form.password}
                onChange={e => setForm(p => ({ ...p, password: e.target.value }))} required />
            </div>
            <div>
              <label className="label">Confirm Password</label>
              <input type="password" className="input" value={form.password_confirmation}
                onChange={e => setForm(p => ({ ...p, password_confirmation: e.target.value }))} required />
            </div>
            <button type="submit" className="btn-primary w-full" disabled={loading}>
              {loading ? 'Resetting…' : 'Reset Password'}
            </button>
          </form>
        </div>
      </div>
    </div>
  )
}
