import React, { useState } from 'react'
import { Link } from 'react-router-dom'
import api from '../services/api'
import ErrorAlert from '../components/ErrorAlert'

export default function ForgotPasswordPage() {
  const [email, setEmail]     = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError]     = useState(null)
  const [sent, setSent]       = useState(false)

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError(null)
    setLoading(true)
    try {
      await api.post('/auth/forgot-password', { email })
      setSent(true)
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
          <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/10 mb-4">
            <span className="text-3xl">🔑</span>
          </div>
          <h1 className="text-2xl font-bold text-white">Reset Password</h1>
          <p className="text-brand-200 text-sm mt-1">We'll send a reset link to your email</p>
        </div>

        <div className="card">
          {sent ? (
            <div className="text-center py-4">
              <div className="text-4xl mb-3">✉️</div>
              <p className="font-medium text-gray-900">Check your email</p>
              <p className="text-sm text-gray-600 mt-1">If an account exists for {email}, a reset link has been sent.</p>
              <Link to="/login" className="btn-primary mt-4 inline-block">Back to Login</Link>
            </div>
          ) : (
            <>
              <ErrorAlert error={error} />
              <form onSubmit={handleSubmit} className="space-y-4 mt-2">
                <div>
                  <label className="label">Email address</label>
                  <input
                    type="email"
                    className="input"
                    value={email}
                    onChange={e => setEmail(e.target.value)}
                    required
                    autoFocus
                  />
                </div>
                <button type="submit" className="btn-primary w-full" disabled={loading}>
                  {loading ? 'Sending…' : 'Send Reset Link'}
                </button>
                <div className="text-center">
                  <Link to="/login" className="text-sm text-brand-600 hover:text-brand-700">Back to Login</Link>
                </div>
              </form>
            </>
          )}
        </div>
      </div>
    </div>
  )
}
